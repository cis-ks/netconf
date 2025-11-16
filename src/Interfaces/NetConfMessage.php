<?php

namespace CisBv\Netconf\Interfaces;

use SimpleXMLElement;

interface NetConfMessage
{
    public function getResponse(string $namespace = ''): SimpleXMLElement|null;
}
