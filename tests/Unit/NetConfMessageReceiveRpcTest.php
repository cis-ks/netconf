<?php /** @noinspection XmlUnusedNamespaceDeclaration */

namespace CisBv\Netconf\NetConfMessage;


use CisBv\Netconf\Data\Enum\NetConfRpcErrorType;
use CisBv\Netconf\Data\NetConfRpcError;
use SimpleXMLElement;

test('Error got\'s correctly parsed', function () {
    $rpcReply = <<<XML
<?xml version="1.0"?>
<nc:rpc-reply xmlns:nc="urn:ietf:params:xml:ns:netconf:base:1.0" xmlns:junos="http://xml.juniper.net/junos/25.2R1.9/junos" message-id="1">
<nc:rpc-error>
<nc:error-type>protocol</nc:error-type>
<nc:error-tag>operation-failed</nc:error-tag>
<nc:error-severity>error</nc:error-severity>
<nc:error-message>syntax error</nc:error-message>
<nc:error-info>
<nc:bad-element>running</nc:bad-element>
</nc:error-info>
</nc:rpc-error>
</nc:rpc-reply>
XML;

    $message = new NetConfMessageReceiveRpc($rpcReply);
    expect($message)->toBeInstanceOf(NetConfMessageReceiveRpc::class)
        ->and($message->hasError())->toBeTrue()
        ->and($message->isRpcReplyOk())->toBeFalse()
        ->and($message->getError())->toBeInstanceOf(NetConfRpcError::class)
        ->and($message->getError()->type)->toEqual(NetConfRpcErrorType::PROTOCOL_ERROR)
        ->and($message->getError()->tag)->toEqual('operation-failed')
        ->and($message->getError()->message)->toEqual('syntax error')
        ->and($message->getError()->info)->toBeInstanceOf(SimpleXMLElement::class)
        ->and($message->getError()->info->{'bad-element'})->toEqual('running');

});

test('Simple OK Reply parsed correctly', function () {
    $rpcReply = <<<XML
<?xml version="1.0"?>
<nc:rpc-reply xmlns:nc="urn:ietf:params:xml:ns:netconf:base:1.0" xmlns:junos="http://xml.juniper.net/junos/25.2R1.9/junos" message-id="2">
<nc:ok/>
</nc:rpc-reply>
XML;

    $message = new NetConfMessageReceiveRpc($rpcReply);
    expect($message)->toBeInstanceOf(NetConfMessageReceiveRpc::class)
        ->and($message->hasError())->toBeFalse()
        ->and($message->isRpcReplyOk())->toBeTrue()
        ->and($message->getMessageId())->toBe(2);

});
