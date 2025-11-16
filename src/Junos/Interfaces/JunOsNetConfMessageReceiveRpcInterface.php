<?php

namespace CisBv\Netconf\Junos\Interfaces;

use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use SimpleXMLElement;

interface JunOsNetConfMessageReceiveRpcInterface
{
    public function getData(): string|SimpleXMLElement;
}
