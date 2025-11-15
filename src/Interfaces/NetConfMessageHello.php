<?php

namespace CisBv\Netconf\Interfaces;

interface NetConfMessageHello
{
    /** @return string[] */
    public function getTheirCapabilities(): array;
    public function getSessionId(): int;
}
