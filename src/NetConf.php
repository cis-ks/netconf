<?php
namespace CisBv\Netconf;

use BadMethodCallException;
use CisBv\Netconf\Interfaces\NetConfAuth;
use CisBv\Netconf\NetConfAuth\NetConfAuthAbstract;
use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveHello;
use CisBv\Netconf\NetConfMessage\NetConfMessageReceiveRpc;
use Exception;
use InvalidArgumentException;
use phpseclib3\Net\SSH2;
use SimpleXMLElement;
use ValueError;

/**
 * Class NetConf
 * @package CisBv\Netconf
 * @author Lamoni Finlayson
 * @author Kay Schroeder
 */
class NetConf
{
    const string NETCONF_BASE_NAMESPACE_REFERENCE = "urn:ietf:params:netconf:base:1.0";

    /**
     * SSH2 interface
     */
    protected SSH2 $ssh;

    /**
     * NETCONF Session ID sent by server during Hello Exchange
     */
    protected int $sessionId = 0;

    /**
     * Our capabilities (auto-includes urn:ietf:params:netconf:base:1.0)
     */
    protected array $myCapabilities;


    /**
     * Their capabilities (informational only... no logic that uses these, yet...)
     */
    protected array $theirCapabilities = [];


    /**
     * "message-id" for <rpc> requests.  Auto-increments whenever sendRPC() is called
     */
    protected int $messageId = 0;

    /**
     * Tracks all XML sent through sendRaw()
     */
    protected array $sendHistory = [];

    public function __construct(
        string $hostname,
        NetConfAuthAbstract $netconfAuth,
        array $options = [],
        protected string $namespace = 'nc:',
    ) {
        /**
         * Defaults
         * - I declare the variables and then compact() them to appease the IDE Gods so they'll smite
         *    the red squiggly lines under variables created by extract().. I hate that I'll
         *    still have to add to the compact() call if I ever want to use a new variable...
         *    But flexibility in options is too sexy.
         */
        $myCapabilities = [];

        /**
         * Default: 830 (NETCONF default)
         */
        $port = 830;

        /**
         * Default: 120 seconds
         */
        $timeout = 120;


        /**
         * Options
         *  - Merge our options, if any, with the defaults, and then extract()
         */
        $options = array_merge(compact('myCapabilities', 'port', 'timeout'), $options);
        extract($options, EXTR_IF_EXISTS);


        $this->setupMyCapabilities($myCapabilities);
        $this->setupSSH($hostname, $port, $timeout, $auth);
        $this->exchangeHellos();
    }

    /**
     * Builds the RPC calls (wraps with <rpc> and increases the message ID counter
     */
    public function sendRPC(string|SimpleXMLElement $rpc): NetConfMessageReceiveRpc|false
    {
        if (!is_string($rpc)) {
            $rpc = $rpc->asXML();
        }

        $this->messageId++;
        $response = $this->sendRaw(
            $rpc, "rpc", "</{$this->namespace}rpc-reply>",
            ["message-id"=>$this->messageID]
        );

        return is_string($response) ? new NetConfMessageReceiveRpc($response) : false;
    }

    public function getTheirCapabilities(): array
    {
        return $this->theirCapabilities;
    }

    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    public function setSessionId(int $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getSendHistory(): array
    {
        return $this->sendHistory;
    }

    public function clearSendHistory(): void
    {
        $this->sendHistory = [];
    }

    public function closeSession(): NetConfMessageReceiveRpc|false
    {
        return $this->sendRPC("<{$this->namespace}close-session/>");
    }

    public function killSession(int $sessionId): NetConfMessageReceiveRpc|false
    {
        return $this->sendRPC(sprintf(
            '<%skill-session><%1$ssession-id>%d</%1$ssession-id></%1$skill-session>',
            $this->namespace,
            $sessionId
        ));
    }

    protected function setupMyCapabilities(array $myCapabilities): void
    {
        $this->myCapabilities = array_merge(
            [self::NETCONF_BASE_NAMESPACE_REFERENCE],
            $myCapabilities
        );
    }

    /**
     * @throws ValueError
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    protected function setupSSH(string $hostname, int $port, int $timeout, NetConfAuth $auth): void
    {
        $this->ssh = new SSH2($hostname, $port, $timeout);
        $this->ssh->setWindowSize(-1, -1);
        $auth->login($this->ssh);

        $this->ssh->startSubsystem("netconf");
    }

    /**
     * @throws Exception
     */
    protected function exchangeHellos(): void
    {
        $theirHello = new NetconfMessageReceiveHello($this->readReply("</{$this->namespace}hello>"));
        $this->theirCapabilities = $theirHello->getTheirCapabilities();
        $this->sessionId = $theirHello->getSessionId();
        $this->sendHello();
    }

    /**
     * After write()'ing input to the server, this handles the waiting and returning of that data
     */
    protected function readReply($endOfMessageDelimiter): array|bool|string|null
    {
        return str_replace(
            "$endOfMessageDelimiter\n]]>]]>",
            "$endOfMessageDelimiter",
            $this->ssh->read("$endOfMessageDelimiter\n]]>]]>")
        );
    }

     protected function sendHello(): void
    {
        $helloXML = $this->getBaseXmlElement("<capabilities> </capabilities>");

        foreach ($this->myCapabilities as $capability) {
            $helloXML->addChild("capability", $capability);
        }

        $this->sendRaw($helloXML->asXML(), "hello", null, [], false);
    }

    /**
     * Handles the actual building and sending of XML to the server
     */
    protected function sendRaw(
        string $data,
        string $rootNode,
        string|null $endOfMessageDelimiter,
        array $attributes = [],
        bool $waitForReply = true
    ): bool|array|string|null {
        $data = str_replace('<?xml version="1.0"?>', '', $data);
        $data = new SimpleXMLElement("<$rootNode>$data</$rootNode>");

        foreach ($attributes as $attribute_name => $attribute_value) {
            $data->addAttribute($attribute_name, $attribute_value);
        }

        $data = str_replace('<?xml version="1.0"?>', '', $data->asXML());

        $this->sendHistory[] = $data;

        $this->ssh->write($data . "]]>]]>\n");

        if (!$waitForReply) {
            return true;
        }

        return $this->readReply($endOfMessageDelimiter);
    }

    /**
     * @throws Exception
     */
    protected function getBaseXmlElement(string $xml): SimpleXMLElement
    {
        $element = new SimpleXMLElement($xml);
        if (!empty($this->namespace)) {
            $element->registerXPathNamespace($this->namespace, $this->myCapabilities[0]);
        }

        return $element;
    }
}
