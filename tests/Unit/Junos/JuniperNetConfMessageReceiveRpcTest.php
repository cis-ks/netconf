<?php /** @noinspection XmlUnusedNamespaceDeclaration */

namespace CisBv\Netconf\Junos;

use CisBv\Netconf\Junos\Enum\MessageType;
use SimpleXMLElement;

test('Get Compare/Text Reply', function () {
    $rpcReply = <<<XML
<?xml version="1.0"?>
<nc:rpc-reply xmlns:nc="urn:ietf:params:xml:ns:netconf:base:1.0" xmlns:junos="http://xml.juniper.net/junos/25.2R1.9/junos" message-id="3">
<configuration-information compare="rollback" database="candidate" rollback="0" format="text">
<configuration-output>
[edit system]
+  host-name juniper1;
</configuration-output>
</configuration-information>
</nc:rpc-reply>
XML;

    $diffOutput = <<<DIFF
[edit system]
+  host-name juniper1;
DIFF;


    $message = new JuniperNetConfMessageReceiveRpc($rpcReply, messageType: MessageType::COMPARE_MESSAGE_TEXT);

    expect($message)->toBeInstanceOf(JuniperNetConfMessageReceiveRpc::class)
        ->and($message->getResponse(''))->toBeInstanceOf(SimpleXMLElement::class)
        ->and($message->messageType)->toEqual(MessageType::COMPARE_MESSAGE_TEXT)
        ->and($message->getData())->toBeString()->toEqual($diffOutput);
});

test('Get Compare/JSON Reply', function () {
    $rpcReply = <<<XML
<?xml version="1.0"?>
<nc:rpc-reply xmlns:nc="urn:ietf:params:xml:ns:netconf:base:1.0" xmlns:junos="http://xml.juniper.net/junos/25.2R1.9/junos" message-id="5">
<configuration-information>
<json-output>
{
    "configuration" : {
        "system" : {
            "host-name" : "juniper1", 
            "@host-name" : {
                "operation" : "create"
            }
         }
    }
}
</json-output>
</configuration-information>
</nc:rpc-reply>
XML;

    $message = new JuniperNetConfMessageReceiveRpc($rpcReply, messageType: MessageType::COMPARE_MESSAGE_JSON);
    expect($message)->toBeInstanceOf(JuniperNetConfMessageReceiveRpc::class)
        ->and($message->getData())->toBeString()->toEqual("{\n    \"configuration\" : {\n        \"system\" : {\n            \"host-name\" : \"juniper1\", \n            \"@host-name\" : {\n                \"operation\" : \"create\"\n            }\n         }\n    }\n}");
});

test('Get Compare/XML Reply', function () {
    $rpcReply = <<<XML
<?xml version="1.0"?>
<nc:rpc-reply xmlns:nc="urn:ietf:params:xml:ns:netconf:base:1.0" xmlns:junos="http://xml.juniper.net/junos/25.2R1.9/junos" message-id="4">
<configuration xmlns="http://xml.juniper.net/xnm/1.1/xnm" xmlns:nc="urn:ietf:params:xml:ns:netconf:base:1.0" xmlns:yang="urn:ietf:params:xml:ns:yang:1">
    <system>
        <host-name nc:operation="create">juniper1</host-name>
    </system>
</configuration>
</nc:rpc-reply>
XML;

    $message = new JuniperNetConfMessageReceiveRpc($rpcReply, messageType: MessageType::COMPARE_MESSAGE_XML);

    expect($message)->toBeInstanceOf(JuniperNetConfMessageReceiveRpc::class)
        ->and($message->getData())->toBeInstanceOf(SimpleXMLElement::class)
        ->toHaveProperty('system')
        ->and($message->getData()->system)->toHaveProperty('host-name');
});
