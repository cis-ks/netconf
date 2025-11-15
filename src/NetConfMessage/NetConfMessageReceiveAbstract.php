<?php /** @noinspection PhpUnused */

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

    public function getResponse(string|null $namespace = null): SimpleXMLElement|null
    {
        if ($this->response instanceof SimpleXMLElement) {
            $selectedNameSpace = $this->getSelectedNameSpace($namespace);
            return !empty($selectedNameSpace) ? $this->response->children($selectedNameSpace, true) : $this->response;
        } else {
            return null;
        }
    }

    public function getResponseData(string $dataEndpoint = 'data', string $namespace = ''): SimpleXMLElement|false
    {
        if (property_exists($this->getResponse($namespace), $dataEndpoint)) {
            return $this->getResponse($namespace)->$dataEndpoint;
        } else {
            return false;
        }
    }

    protected function setResponse(SimpleXMLElement|string $response): void
    {
        $this->response = $response instanceof SimpleXMLElement ? $response : simplexml_load_string($response);
    }

    protected function getSelectedNameSpace(string|null $namespace): string|null
    {
        $selectedNameSpace = match (true) {
            !is_null($namespace) => $namespace,
            !empty($this->namespace) => $this->namespace,
            default => null
        };

        return is_string($selectedNameSpace) ? rtrim($selectedNameSpace, ':') : $selectedNameSpace;
    }
}
