<?php
namespace CisBv\Netconf\NetConfAuth;

use Exception;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;

/**
 * Class NetConfAuthRSAFile
 * @package CisBv\Netconf\NetConfAuth
 */
class NetConfAuthRSAFile extends NetConfAuthAbstract
{
    protected static array $acceptableParams = ['username' => 'is_string', 'rsafile' => 'file_exists'];

    /**
     * Performs the authentication check for this auth type
     *
     * @throws Exception
     * @throws RuntimeException
     */
    public function login(SSH2 $ssh): void
    {
        $rsaKey = PublicKeyLoader::load(file_get_contents($this->authParams['rsafile']));

        if (!$ssh->login($this->authParams['username'], $rsaKey)) {
            throw new RuntimeException("Authentication for {$this->authParams['username']} failed with RSA key");
        }
    }
}
