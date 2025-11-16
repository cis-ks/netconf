<?php

namespace CisBv\Netconf\Junos;

use CisBv\Netconf\Junos\Enum\MessageType;
use CisBv\Netconf\Junos\Interfaces\JunOsNetConfMessageReceiveRpcInterface;
use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use SimpleXMLElement;

class JunOsNetConfMessageReceiveRpc extends NetConfMessageReceiveRpc implements JunOsNetConfMessageReceiveRpcInterface
{
    public function __construct(
        SimpleXMLElement|string $response,
        protected string $namespace = 'nc:',
        public readonly MessageType $messageType = MessageType::UNDEFINED
    ) {
        parent::__construct($response, $this->namespace);
    }

    public function getData(string $namespace = 'nc'): string|SimpleXMLElement
    {
        $originalResponse = $this->getResponse('');

        return match ($this->messageType) {
            MessageType::COMPARE_MESSAGE_TEXT => trim(
                (string)$originalResponse->{'configuration-information'}->{'configuration-output'},
                "\n"
            ),
            MessageType::COMPARE_MESSAGE_JSON => trim(
                (string)$originalResponse->{'configuration-information'}->{'json-output'},
                "\n"
            ),
            MessageType::COMPARE_MESSAGE_XML => $originalResponse->{'configuration'},
            default => $this->getResponse($namespace),
        };
    }
}
