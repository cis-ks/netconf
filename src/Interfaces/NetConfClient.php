<?php

namespace CisBv\Netconf\Interfaces;

use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use SimpleXMLElement;

interface NetConfClient
{
    public function sendRpc(string|SimpleXMLElement $rpc): NetConfMessageReceiveRpc|false;
    /** @return string[] */
    public function getTheirCapabilities(): array;
    public function getSessionId(): int;
    public function setSessionId(int $sessionId): void;
    public function getSendHistory(): array;
    public function clearSendHistory(): void;
    public function closeSession(): NetConfMessageReceiveRpc|false;
    public function killSession(int $sessionId): NetConfMessageReceiveRpc|false;
}
