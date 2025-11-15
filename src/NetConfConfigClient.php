<?php

namespace CisBv\Netconf;

use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use Exception;
use InvalidArgumentException;
use SimpleXMLElement;

class NetConfConfigClient extends NetConf
{
    const array VALID_CONFIG_OPERATIONS = ['merge', 'replace', 'delete', 'create', 'remove'];

    /**
     * @throws Exception
     */
    public function getConfig(
        array $filterPaths = [],
        string $filterType = "",
        string $dataStore = "running"
    ): false|NetConfMessage\NetConfMessageReceiveRpc {
        $filterRoot = $this->getBaseXmlElement("<get-config><source><$dataStore/></source></get-config>");

        if (!empty($filterPaths)) {
            $filterRoot = $this->addFilterElements($filterRoot, $filterType, $filterPaths);
        }

        return $this->sendRPC($filterRoot);
    }

    private function addFilterElements(
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

    private function getFilterLevels(int|string $filterPath): array
    {
        $levelSplit = explode("/", trim($filterPath, "/"));
        $lastLevel = end($levelSplit);
        return array($levelSplit, $lastLevel);
    }

    private function addChildToLastElement(mixed $elements, mixed $deepestNode, mixed $lastLevel): mixed
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
        string $target = "running",
        string $configRootNode = "",
        string $configOperation = "merge",
        array $customParameters = [],
        bool $lockConfig = true
    ): NetConfMessageReceiveRpc|false {
        if ($lockConfig) {
            $lockConfigCheck = $this->lockConfig($target);

            if (!$lockConfigCheck->isRpcReplyOk()) {
                return $lockConfigCheck;
            }
        }

        if (!in_array($configOperation, self::VALID_CONFIG_OPERATIONS)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Given config operation is not valid. Valid values are: %s. Given: %s",
                    implode(", ", self::VALID_CONFIG_OPERATIONS),
                    $configOperation
                )
            );
        }

        $configStringWithRootNode = empty($configRootNode) ? $configString : "<$configRootNode>$configString</$configRootNode>";

        $editConfig = $this->getBaseXmlElement(
            "<edit-config><target><$target></target><config operation='$configOperation'>$configStringWithRootNode</config></edit-config>"
        );

        foreach ($customParameters as $parameterName => $parameterValue) {
            $editConfig->addChild($parameterName, $parameterValue);
        }

        return $this->sendRPC($editConfig->asXML());
    }

    /**
     * @throws Exception
     */
    public function lockConfig(string $target = "running"): NetConfMessageReceiveRpc|false
    {
        $lockConfig = $this->getBaseXmlElement("<lock><target><$target/></target></lock>");
        return $this->sendRPC($lockConfig);
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

    private function formatConfigStatement(string $dataStore): string
    {
        return str_starts_with($dataStore, 'url:') ? "<url>" . substr($dataStore, 4) . "</url>" : "<$dataStore/>";
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
        string $target = 'candidate',
        bool $unlockConfig = true,
        bool $requiresConfirm = false,
        int $confirmTimeout = 600,
        string $persistId = ""
    ): NetConfMessageReceiveRpc|false {
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
            $unlockConfigCheck = $this->unlockConfig($target);

            if (!$unlockConfigCheck->isRPCReplyOK()) {
                return $unlockConfigCheck;
            }
        }

        return $commitResponse;
    }

    /**
     * @throws Exception
     */
    public function unlockConfig(string $target): NetConfMessageReceiveRpc|false
    {
        $unlockConfig = $this->getBaseXmlElement("<unlock><target><$target/></target></unlock>");
        return $this->sendRPC($unlockConfig);
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
