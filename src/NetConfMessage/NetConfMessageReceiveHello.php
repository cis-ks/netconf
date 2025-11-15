<?php
namespace CisBv\Netconf\NetConfMessage;

use SimpleXMLElement;

/**
 * Class NetConfMessageReceiveHello
 * @package CisBv\Netconf\NetConfMessage
 */
class NetConfMessageReceiveHello extends NetConfMessageReceiveAbstract
{

    /**
     * What NETCONF capabilities is the server... capable of
     */
    protected array $theirCapabilities = [];

    /**
     * Assuming the server is following protocol, they should reply with a session ID
     */
    protected int $sessionId = 0;

    /**
     * Build our NetConfMessageReceiveHello instance
     */
    public function __construct(
        SimpleXMLElement|string $response,
        protected string $namespace = 'nc:'
    ) {
        parent::__construct($response, $this->namespace);

        $this->setSessionId();
        $this->setTheirCapabilities();
    }

     public function getSessionId(): int
    {
        return $this->sessionId;
    }

    protected function setSessionId(): void
    {
        $this->sessionId = (int)$this->getResponse()?->{'session-id'} ?? 0;
    }

    public function getTheirCapabilities(): array
    {
        return $this->theirCapabilities;
    }

    protected function setTheirCapabilities(): void
    {
        $this->theirCapabilities = (array)$this->getResponse()?->{"capabilities"}?->{"capability"} ?? [];
    }
}
