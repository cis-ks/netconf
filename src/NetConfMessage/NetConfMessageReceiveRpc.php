<?php

namespace CisBv\Netconf\NetConfMessage;

use CisBv\Netconf\Data\NetConfRpcError;
use CisBv\Netconf\Interfaces\NetConfMessageRpc;
use SimpleXMLElement;

/**
 * Class NetConfMessageReceiveRpc
 * @package CisBv\Netconf\NetConfMessage
 */
class NetConfMessageReceiveRpc extends NetConfMessageReceiveAbstract implements NetConfMessageRpc
{

    /**
     * Send it with every RPC call
     */
    protected int $messageId = 0;

    /**
     * If rpc-error exists in output, save it to instance.
     */
    protected NetConfRpcError|null $rpcError = null;


    /**
     * Build our NetConfMessageReceiveRPC instance
     */
    public function __construct(SimpleXMLElement|string $response)
    {
        parent::__construct($response);
        $this->setMessageId();
        $this->setRpcError();
    }

    protected function setRpcError(): void
    {
        if (isset($this->getResponse()?->{'rpc-error'}) && $this->getResponse()?->{'rpc-error'}->hasChildren()) {
            $this->rpcError = NetConfRpcError::fromResponse($this->getResponse()?->{'rpc-error'});
        }
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }

    protected function setMessageId(): void
    {
        if ($this->getResponse() instanceof SimpleXMLElement) {
            $this->messageId = (int)$this->getResponse()->attributes()->{'message-id'};
        }
    }

    public function getError(): NetConfRpcError|false
    {
        return $this->rpcError ?? false;
    }

    /**
     * Returns <rpc-reply> data
     */
    public function getRpcReply(): SimpleXMLElement|false
    {
        if (!$this->isRpcReplyOk()) {
            return false;
        }

        return $this->getResponse();
    }

    /**
     * If the response contains either an <ok/> or doesn't have any errors, consider it "OK"
     */
    public function isRpcReplyOk(): bool
    {
        return isset($this->getResponse()?->ok) || !$this->hasError();
    }

    public function hasError(): bool
    {
        return $this->rpcError !== null;
    }
}
