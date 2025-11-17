<?php

namespace CisBv\Netconf;

use CisBv\Netconf\Junos\Enum\CompareDatabase;
use CisBv\Netconf\Junos\Enum\CompareFormat;
use CisBv\Netconf\Junos\Enum\CompareType;
use CisBv\Netconf\Junos\Enum\MessageType;
use CisBv\Netconf\Junos\JunOsNetConfMessageReceiveRpc;
use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use InvalidArgumentException;

class NetConfJunOsClient extends NetConfConfigClient implements Interfaces\NetConfJunOsClient
{
    public function compareConfiguration(
        string $rollbackOrRevisionId,
        CompareType $compare = CompareType::ROLLBACK,
        CompareDatabase $database = CompareDatabase::CANDIDATE,
        CompareFormat $format = CompareFormat::PATCH_TEXT,
    ): NetConfMessageReceiveRpc|false {
        if ($compare === CompareType::ROLLBACK && !in_array($rollbackOrRevisionId, range(0, 49))) {
            throw new InvalidArgumentException(
                "Invalid rollback number: $rollbackOrRevisionId, must be between 0 and 49"
            );
        }

        $response = $this->sendRPC(
            sprintf(
                '<get-configuration compare="%s" database="%s" %1$s="%s" format="%s" />',
                $compare->value,
                $database->value,
                $rollbackOrRevisionId,
                $format->value,
            )
        );

        $messageType = match ($format) {
            CompareFormat::JSON => MessageType::COMPARE_MESSAGE_JSON,
            CompareFormat::PATCH_TEXT => MessageType::COMPARE_MESSAGE_TEXT,
            CompareFormat::XML => MessageType::COMPARE_MESSAGE_XML,
        };

        return $response instanceof NetConfMessageReceiveRpc
            ? new JunOsNetConfMessageReceiveRpc($response->getResponse(''), $this->namespace, $messageType)
            : $response;
    }

    public function editConfig(
        string $configString,
        string $dataStore = "candidate",
        string $configRootNode = "config",
        string $configOperation = "merge",
        array $customParameters = [],
        bool $lockConfig = true
    ): NetConfMessageReceiveRpc|false {
        if (!str_starts_with($configString, "<")) {
            $configString = "<configuration-text>\n\n$configString\n\n</configuration-text>";
            if ($configRootNode !== 'config-text') {
                $configRootNode = 'config-text';
            }
        }

        $response = parent::editConfig(
            $configString,
            'candidate',
            $configRootNode,
            $configOperation,
            $customParameters,
            $lockConfig
        );
        if ($response === false) {
            return false;
        }
        return new JunOsNetConfMessageReceiveRpc(
            $response->getResponse(''),
            $this->namespace,
            MessageType::EDIT_CONFIG
        );
    }
}
