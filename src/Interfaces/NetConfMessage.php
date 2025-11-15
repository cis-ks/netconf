<?php

namespace CisBv\Netconf\Interfaces;

use SimpleXMLElement;

interface NetConfMessage
{
    public function getResponse(): SimpleXMLElement|null;
}
