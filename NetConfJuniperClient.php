<?php

namespace CisBv\Netconf;

use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use InvalidArgumentException;

class NetConfJuniperClient extends NetConfConfigClient implements Interfaces\NetConfJuniperClient
{
    const array VALID_COMPARE_DATABASES = ['candidate', 'committed'];

    public function compareConfiguration(
        string $rollbackOrRevisionId,
        string $compare = 'rollback',
        string $database = 'candidate',
        string $format = 'text'
    ): NetConfMessageReceiveRpc|false {
        if (!in_array($database, self::VALID_COMPARE_DATABASES)) {
            throw new InvalidArgumentException(
                "Invalid database specified: $database, must be one of: " . implode(', ', self::VALID_COMPARE_DATABASES)
            );
        }

        if (!in_array($compare, ['rollback', 'revision-id'])) {
            throw new InvalidArgumentException("Invalid compare parameter: $compare, must be either 'rollback' or 'revision-id'");
        }

        if ($compare === 'rollback' && !in_array($rollbackOrRevisionId, range(0, 49))) {
            throw new InvalidArgumentException("Invalid rollback number: $rollbackOrRevisionId, must be between 0 and 49");
        }

        return $this->sendRPC(
            "<get-configuration compare=\"$compare\" database=\"$database\" $compare=\"$rollbackOrRevisionId\" format=\"$format\" />"
        );
    }
}
