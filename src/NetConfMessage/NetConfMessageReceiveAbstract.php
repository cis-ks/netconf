<?php

namespace CisBv\Netconf\NetConfMessage;

use CisBv\Netconf\Interfaces\NetConfMessage;
use SimpleXMLElement;

/**
 * Class NetConfMessageReceiveAbstract
 * @package CisBv\Netconf\NetConfMessage
 */
abstract class NetConfMessageReceiveAbstract implements NetConfMessage
{

    /**
     * Holds the SimpleXMLElement'd response from the server
     */
    protected SimpleXMLElement|null|false $response = null;

    /**
     * Build our NetConfMessageReceive* instance
     */
    public function __construct(
        SimpleXMLElement|string $response,
        protected string $namespace = 'nc:'
    ) {
        if (!empty($this->namespace) && !str_ends_with($this->namespace, ':')) {
            $this->namespace .= ':';
        }
        $this->setResponse($response);
    }

    public function __toString(): string
    {
        return (string)$this->response->asXML();
    }

    public function getResponse(string $namespace = ''): SimpleXMLElement|null
    {
        if ($this->response instanceof SimpleXMLElement) {
            $selectedNameSpace = $this->getSelectedNameSpace($namespace);
            return !empty($selectedNameSpace) ? $this->response->children($selectedNameSpace, true) : $this->response;
        } else {
            return null;
        }
    }

    protected function setResponse(SimpleXMLElement|string $response): void
    {
        $this->response = $response instanceof SimpleXMLElement ? $response : simplexml_load_string($response);
    }

    protected function getSelectedNameSpace(string $namespace): string
    {
        return rtrim(
            match (true) {
                !empty($namespace) => $namespace,
                !empty($this->namespace) => $this->namespace,
                default => ''
            },
            ':'
        );
    }
}
