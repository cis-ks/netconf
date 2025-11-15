<?php

namespace CisBv\Netconf\Interfaces;

use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;

interface NetConfJuniperClient
{
    public function compareConfiguration(
        string $rollbackOrRevisionId,
        string $compare = 'rollback',
        string $database = 'candidate',
        string $format = 'text'
    ): NetConfMessageReceiveRpc|false;
}
