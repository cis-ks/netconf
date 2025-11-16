<?php
namespace CisBv\Netconf\NetConfAuth;

use Exception;
use phpseclib3\Net\SSH2;
use RuntimeException;

/**
 * Class NetConfAuthPassword
 * @package CisBv\Netconf\NetConfAuth
 */
class NetConfAuthPassword extends NetConfAuthAbstract
{
    protected static array $acceptableParams = ['username' => 'is_string', 'password' => 'is_string'];

    /**
     * Performs the authentication check for this auth type
     *
     * @throws RuntimeException|Exception
     */
    public function login(SSH2 $ssh): void
    {
        if (!$ssh->login($this->authParams['username'], $this->authParams['password'])) {
            throw new RuntimeException("Authentication for {$this->authParams['username']} failed");
        }
    }
}
