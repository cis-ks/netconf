<?php
/** @noinspection PhpUnused */

namespace CisBv\Netconf;

use CisBv\Netconf\Exceptions\InvalidDataStoreException;
use CisBv\Netconf\Exceptions\InvalidParameterException;
use CisBv\Netconf\NetConfConstants\NetConfConstants;
use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use Exception;
use InvalidArgumentException;
use SimpleXMLElement;

class NetConfConfigClient extends NetConf
{

    protected array $lockState = [];

    /**
     * @throws Exception
     */
    public function getConfig(
        array $filterPaths = [],
        string $filterType = "",
        string $dataStore = "running"
    ): false|NetConfMessageReceiveRpc {
        $this->validateDataStore($dataStore);
        $filterRoot = $this->getBaseXmlElement("<get-config><source><$dataStore/></source></get-config>");

        if (!empty($filterPaths)) {
            $filterRoot = $this->addFilterElements($filterRoot, $filterType, $filterPaths);
        }

        return $this->sendRPC($filterRoot);
    }

    /**
     * @throws InvalidDataStoreException
     */
    protected function validateDataStore(string $dataStore): void
    {
        $supportedDataStores = ['running'];

        if ($this->capabilitySupported(NetConfConstants::CAPABILITY_DATASTORE_CANDIDATE_1_0)) {
            $supportedDataStores[] = 'candidate';
        }

        if ($this->capabilitySupported(
            NetConfConstants::CAPABILITY_DATASTORE_STARTUP_1_0
        )) {
            $supportedDataStores[] = 'startup';
        }

        if (!in_array($dataStore, $supportedDataStores)) {
            throw new InvalidDataStoreException(
                "The data store $dataStore is not supported. Supported stores are: " . implode(
                    ", ",
                    $supportedDataStores
                )
            );
        }
    }

    protected function addFilterElements(
        SimpleXMLElement $filterRoot,
        string $filterType,
        array $filterPaths
    ): array|SimpleXMLElement {
        $addFilter = $filterRoot->addChild("filter");

        if (!empty($filterType)) {
            $addFilter->addAttribute("type", $filterType);
        }

        foreach ($filterPaths as $filterPath => $elements) {
            list($levelSplit, $lastLevel) = $this->getFilterLevels($filterPath);

            $deepestNode = $addFilter;

            foreach ($levelSplit as $level) {
                if ($level != $lastLevel) {
                    $deepestNode = $deepestNode->addChild($level);
                } else {
                    $deepestNode = $this->addChildToLastElement($elements, $deepestNode, $lastLevel);
                }
            }
        }
        return $filterRoot;
    }

    protected function getFilterLevels(int|string $filterPath): array
    {
        $levelSplit = explode("/", trim($filterPath, "/"));
        $lastLevel = end($levelSplit);
        return array($levelSplit, $lastLevel);
    }

    protected function addChildToLastElement(mixed $elements, mixed $deepestNode, mixed $lastLevel): mixed
    {
        foreach ($elements as $element) {
            $deepestNode = $deepestNode->addChild($lastLevel);
            foreach ($element as $elementName => $elementValue) {
                $deepestNode->addChild($elementName, $elementValue);
            }

            $deepestNode = $deepestNode->xpath("..")[0];
        }
        return $deepestNode;
    }

    /**
     * @throws Exception
     */
    public function editConfig(
        string $configString,
        string $dataStore = "running",
        string $configRootNode = "",
        string $configOperation = "merge",
        array $customParameters = [],
        bool $lockConfig = true
    ): NetConfMessageReceiveRpc|false {
        $this->validateDataStore($dataStore);
        $this->validateConfigOperation($configOperation);

        if ($lockConfig) {
            $lockConfigCheck = $this->lockConfig($dataStore);

            if (!$lockConfigCheck->isRpcReplyOk()) {
                return $lockConfigCheck;
            }
        }

        $configStringWithRootNode = empty($configRootNode)
            ? $configString
            : "<$configRootNode>$configString</$configRootNode>";

        $editConfig = $this->getBaseXmlElement("<edit-config></edit-config>");

        $parameters = array_merge(
            $customParameters,
            ['target' => "$dataStore", 'config' => [$configStringWithRootNode, ['operation' => $configOperation]]]
        );

        foreach ($this->getEditConfigParameters($parameters) as $name => $value) {
            if (is_array($value)) {
                $child = $editConfig->addChild($name, $value[0]);
                foreach ($value[1] as $attributeName => $attributeValue) {
                    $child->addAttribute($attributeName, $attributeValue);
                }
            } else {
                $editConfig->addChild($name, $value);
            }
        }

        return $this->sendRPC($editConfig->asXML());
    }

    protected function validateConfigOperation(
        string $configOperation,
        array $validOperations = NetConfConstants::EDIT_CONFIG_OPERATIONS
    ): void {
        if (!in_array($configOperation, $validOperations)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Given config operation is not valid. Valid values are: %s. Given: %s",
                    implode(", ", $validOperations),
                    $configOperation
                )
            );
        }
    }

    /**
     * @throws Exception
     */
    public function lockConfig(string $dataStore = "running"): NetConfMessageReceiveRpc|false
    {
        $this->validateDataStore($dataStore);
        $lockConfig = $this->getBaseXmlElement("<lock><target><$dataStore/></target></lock>");
        $lockResponse = $this->sendRPC($lockConfig);

        if ($lockResponse->isRpcReplyOk()) {
            $this->lockState[$dataStore] = true;
        }

        return $lockResponse;
    }

    /**
     * @throws InvalidParameterException
     */
    protected function getEditConfigParameters(array $parameters = []): array
    {
        $outputParameters = [];

        $validations = [
            'target' => fn ($parameter) => $this->validateDataStore($parameter),
            'default-operation' => NetConfConstants::EDIT_CONFIG_DEFAULT_OPERATIONS,
            'test-option' => NetConfConstants::EDIT_CONFIG_TEST_OPTIONS,
            'error-option' => NetConfConstants::EDIT_CONFIG_ERROR_OPTIONS,
            'config' => null,
        ];

        foreach ($validations as $parameterName => $validation) {
            if (array_key_exists($parameterName, $parameters)) {
                if (is_callable($validation)) {
                    call_user_func($validation, $parameters[$parameterName]);
                } elseif (!is_null($validation)) {
                    $this->validateParameter($parameters[$parameterName], $validation);
                }
                $outputParameters[] = $parameterName;
            }
        }

        return array_intersect_key($parameters, array_flip($outputParameters));
    }

    /**
     * @throws InvalidParameterException
     */
    protected function validateParameter(string $parameter, array $validParameters): void
    {
        if (!in_array($parameter, $validParameters)) {
            throw new InvalidParameterException(
                "Parameter $parameter is not valid. Valid parameters are: " . implode(",", $validParameters)
            );
        }
    }

    /**
     * @throws Exception
     */
    public function copyConfig(string $source, string $target): NetConfMessageReceiveRpc|false
    {
        $source = $this->formatConfigStatement($source);
        $target = $this->formatConfigStatement($target);

        $copyConfig = $this->getBaseXmlElement(
            "<copy-config><source>$source</source><target>$target</target></copy-config>"
        );

        return $this->sendRPC($copyConfig);
    }

    protected function formatConfigStatement(string $target): string
    {
        return str_starts_with($target, 'url:') ? "<url>" . substr($target, 4) . "</url>" : "<$target/>";
    }

    /**
     * @throws Exception
     */
    public function deleteConfig(string $target): NetConfMessageReceiveRpc|false
    {
        $target = $this->formatConfigStatement($target);
        $deleteConfig = $this->getBaseXmlElement("<delete-config><target>$target</target></delete-config>");
        return $this->sendRPC($deleteConfig);
    }

    /**
     * @throws Exception
     */
    public function commit(
        string $dataStore = 'candidate',
        bool $unlockConfig = true,
        bool $requiresConfirm = false,
        int $confirmTimeout = 600,
        string $persistId = ""
    ): NetConfMessageReceiveRpc|false {
        $this->validateDataStore($dataStore);

        $commit = $this->getBaseXmlElement("<commit></commit>");

        if ($requiresConfirm) {
            $commit->addChild("confirmed", "");
            $commit->addChild("confirm-timeout", $confirmTimeout);
            if (!empty($persistId)) {
                $commit->addChild("persist-id", $persistId);
            }
        }

        $commitResponse = $this->sendRPC($commit);

        if (!$commitResponse->isRpcReplyOk()) {
            return $commitResponse;
        }

        if ($unlockConfig) {
            $unlockConfigCheck = $this->unlockConfig($dataStore);

            if (!$unlockConfigCheck->isRPCReplyOK()) {
                return $unlockConfigCheck;
            }
        }

        return $commitResponse;
    }

    /**
     * @throws Exception
     */
    public function unlockConfig(string $dataStore): NetConfMessageReceiveRpc|false
    {
        $this->validateDataStore($dataStore);
        $unlockConfig = $this->getBaseXmlElement("<unlock><target><$dataStore/></target></unlock>");
        $unlockResponse = $this->sendRPC($unlockConfig);

        if ($unlockResponse->isRpcReplyOk()) {
            $this->lockState[$dataStore] = false;
        }

        return $unlockResponse;
    }

    public function getLockState(string $dataStore): bool
    {
        return $this->lockState[$dataStore] ?? false;
    }

    /**
     * @throws Exception
     */
    public function cancelCommit(): NetConfMessageReceiveRpc|false
    {
        $cancelCommit = $this->getBaseXmlElement("<cancel-commit/>");
        return $this->sendRPC($cancelCommit);
    }
}
