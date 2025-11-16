NETCONF
-------
This is a vendor-agnostic PHP implementation of NETCONF. It was originally developed by Lamoni Finlayson, so he could
extend a Junos (Juniper) specific NETCONF API off of it.

As the used phpseclib library was outdated, I decided to implement the support for phpseclib3 and also refactor the code
to be more oriented towards modern PHP versions.


Targeted RFCs
-------------

- RFC6241: Network Configuration Protocol (NETCONF) - https://tools.ietf.org/html/rfc6241
- RFC6242: Using the NETCONF Protocol over Secure Shell (SSH) - https://tools.ietf.org/html/rfc6242

Dependencies
-------------

- PHP >= 8.3
- phpseclib3 (https://github.com/phpseclib/phpseclib)

To Do
----------

- Elements with attribute naming for subtree filters need to be implemented ("6.4.8. Elements with Attribute Naming")
- Parse capabilities based on IANA
  list: http://www.iana.org/assignments/netconf-capability-urns/netconf-capability-urns.xhtml
- Check Necessary for Yang-Compatibility: https://tools.ietf.org/html/rfc6243#section-4.1.1

Examples
------------

Initializing NETCONF using password authentication and then sending a custom RPC call
---------------------------

```php
$netConf = new NetConf(
    "192.168.0.100",
    new NetConfAuthPassword(
        [
            "username" => "lamoni",
            "password" => "phpsux"
        ]
    )
);

echo $netConf->sendRPC(
    "<get-config>".
        "<source>".
            "<running/>".
        "</source>".
    "</get-config>"
);
```

---------------------------

Editing the configuration of a Junos device and committing the changes
---------------------------

```php
use CisBv\Netconf\NetConfConfigClient;

$netConf = new NetConfConfigClient(
    "192.168.0.100",
    new NetConfAuthPassword(
        [
            "username" => "lamoni",
            "password" => "phpsux"
        ]
    )
);

$editConfig = $netConf->editConfig(
    configString: "<configuration>
        <interfaces>
            <interface>
                <name>fe-0/0/0</name>
                <description>Testing netconf</description>
            </interface>
        </interfaces>
    </configuration>",
    dataStore: 'candidate',
    customParameters: ['custom-param' => 'custom-value']
);


if ($editConfig === false) {
    echo "Something went completely wrong. The config could not be edited."
} else {
    $commit = $netConf->commit();
    if ($commit === false || !$commit->isRpcReplyOk()) {
        echo "Commit failed.";
        if ($commit instanceof NetConfMessageReceiveRpc) {
            var_dump($commit->getRpcReplyError());
        }
    } else {
        echo "Successfully committed, dude!";
    }
}
```

---------------------------
Using NETCONF's subtree filters to get a certain config
---------------------------

```php
$getUsersNames = $netConf->getConfig(
    [
       "configuration/system/login/user" => [
           [
               "name"=>"user"
           ]
       ]
    ]
);
```

---------------------------
Considerations
---------------------------

- test-option: The <test-option> element MAY be specified only if the device advertises the :validate:1.1 capability (
  Section 8.6).
- Should I be implicitly locking/unlocking the config for editConfig() (<edit-config>) and commit() (<commit>) calls?
- XPath capability in filter?
