<?php

namespace CisBv\Netconf\Interfaces;

use CisBv\Netconf\Data\NetConfRpcError;
use SimpleXMLElement;

interface NetConfMessageRpc
{
    public function getMessageId(): int;
    public function hasError(): bool;
    public function getError(): NetConfRpcError|false;
    public function getRpcReply(): SimpleXMLElement|false;
    public function isRpcReplyOk(): bool;
}
