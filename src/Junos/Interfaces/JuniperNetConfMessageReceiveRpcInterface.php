<?php

namespace CisBv\Netconf\Junos\Interfaces;

use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use SimpleXMLElement;

interface JuniperNetConfMessageReceiveRpcInterface
{
    public function getData(): string|SimpleXMLElement;
}
