<?php

namespace CisBv\Netconf\Data;

use CisBv\Netconf\Data\Enum\NetConfRpcErrorSeverity;
use CisBv\Netconf\Data\Enum\NetConfRpcErrorType;
use SimpleXMLElement;

final readonly class NetConfRpcError
{
    public function __construct(
        public NetConfRpcErrorType $type,
        public string $tag,
        public NetConfRpcErrorSeverity $severity,
        public string $appTag,
        public string $path,
        public string $message,
        public string|SimpleXMLElement $info
    ) {
    }

    public static function fromResponse(
        SimpleXMLElement|string $response
    ): NetConfRpcError {
        $data = $response instanceof SimpleXMLElement ? $response : simplexml_load_string($response);

        if (isset($data->{'rpc-error'})) {
            $data = $data->{'rpc-error'};
        }

        return new NetConfRpcError(
            NetConfRpcErrorType::tryFrom($data->{'error-type'} ?? 'unknown'),
            $data->{'error-tag'},
            NetConfRpcErrorSeverity::tryFrom($data->{'error-severity'} ?? 'unknown'),
            $data->{'error-app-tag'} ?? '',
            $data->{'error-path'} ?? '',
            $data->{'error-message'} ?? '',
            $data->info ?? ''
        );
    }
}
