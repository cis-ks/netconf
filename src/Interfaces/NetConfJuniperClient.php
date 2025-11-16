<?php

namespace CisBv\Netconf\Interfaces;

use CisBv\Netconf\Junos\Enum\CompareDatabase;
use CisBv\Netconf\Junos\Enum\CompareFormat;
use CisBv\Netconf\Junos\Enum\CompareType;
use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;

interface NetConfJuniperClient
{
    public function compareConfiguration(
        string $rollbackOrRevisionId,
        CompareType $compare = CompareType::ROLLBACK,
        CompareDatabase $database = CompareDatabase::CANDIDATE,
        CompareFormat $format = CompareFormat::PATCH_TEXT,
    ): NetConfMessageReceiveRpc|false;
}
