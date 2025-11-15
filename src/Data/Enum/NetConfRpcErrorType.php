<?php

namespace CisBv\Netconf\Data\Enum;

enum NetConfRpcErrorType: string
{
    case TRANSPORT_ERROR = 'transport';
    case RPC_ERROR = 'rpc';
    case PROTOCOL_ERROR = 'protocol';
    case APPLICATION_ERROR = 'application';

    case UNKNOWN_ERROR = 'unknown';
}
