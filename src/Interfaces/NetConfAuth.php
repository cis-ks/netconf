<?php

namespace CisBv\Netconf\Interfaces;

use phpseclib3\Net\SSH2;
use ValueError;

interface NetConfAuth
{
    /**
     * @throws ValueError, BadMethodCallException, \InvalidArgumentException
     */
    public function login(SSH2 $ssh);
}
