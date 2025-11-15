<?php

namespace CisBv\Netconf\Interfaces;

use SimpleXMLElement;

interface NetConfMessageHello
{
    public function getCapabilities(): array;
    public function getSessionId(): int;
}
