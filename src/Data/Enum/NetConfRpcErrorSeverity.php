<?php

namespace CisBv\Netconf\Data\Enum;

enum NetConfRpcErrorSeverity: string
{
    case ERROR = 'error';
    case WARNING = 'warning';

    case UNKNOWN = 'unknown';
}
