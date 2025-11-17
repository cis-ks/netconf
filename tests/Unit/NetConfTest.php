<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

namespace Tests\Unit;

use CisBv\Netconf\NetConf;
use CisBv\Netconf\Interfaces\NetConfAuth;
use CisBv\Netconf\NetConfAuth\NetConfAuthPassword;
use CisBv\Netconf\NetConfConstants\NetConfConstants;
use Exception;

class TestNetConf extends NetConf
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(string $hostname, NetConfAuth $auth, array $options = [], string $namespace = 'nc:')
    {
        $this->theirCapabilities = [
            'urn:ietf:params:netconf:base:1.0',
            'urn:ietf:params:netconf:capability:confirmed-commit:1.0',
            'urn:ietf:params:netconf:capability:validate:1.0',
        ];
    }

    public function capabilitySupported(string $capability): bool
    {
        return parent::capabilitySupported($capability);
    }

    public function capabilitiesSupported(array $capabilities): bool
    {
        return parent::capabilitiesSupported($capabilities);
    }
}

test(
    'Testing Capability Single Match',
    /**
     * @throws Exception
     */
    function () {
        $testNetConf = new TestNetConf(
            "localhost",
            new NetConfAuthPassword(['username' => 'admin', 'password' => 'admin'])
        );

        expect($testNetConf->capabilitySupported(NetConfConstants::CAPABILITY_COMMIT_CONFIRMED_1_0))->toBeTrue()
            ->and($testNetConf->capabilitySupported(NetConfConstants::CAPABILITY_COMMIT_CONFIRMED_1_1))->toBeFalse()
            ->and($testNetConf->capabilitySupported(NetConfConstants::CAPABILITY_COMMIT_CONFIRMED))->toBeTrue()
            ->and(
                $testNetConf->capabilitiesSupported(
                    [NetConfConstants::CAPABILITY_COMMIT_CONFIRMED_1_0, NetConfConstants::CAPABILITY_VALIDATE]
                )
            )->toBeTrue();
    }
);
