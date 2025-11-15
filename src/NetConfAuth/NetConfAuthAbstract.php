<?php
namespace CisBv\Netconf\NetConfAuth;

use BadMethodCallException;
use CisBv\Netconf\Interfaces\NetConfAuth;
use Exception;
use InvalidArgumentException;
use phpseclib3\Net\SSH2;
use ValueError;

/**
 * Class NetConfAuthAbstract
 * @package CisBv\Netconf\NetConfAuth
 */
abstract class NetConfAuthAbstract implements NetConfAuth
{
    protected static array $acceptableParams = [];

    /**
     * Builds our NetConfAuth* instance
     *
     * @param array $authParams
     * @throws Exception
     */
    public function __construct(
        protected array $authParams
    ) {
        $this->validateAuthParams();
    }

    /**
     * All classes extending NetConfAuthAbstract require the specification of logging in
     * @throws Exception
     */
    abstract public function login(SSH2 $ssh): void;

    /**
     * All children will need this to validate the passed inputs against our defined inputs
     *
     * @throws InvalidArgumentException
     * @throws BadMethodCallException
     * @throws ValueError
     */
    protected function validateAuthParams(): void
    {
        foreach ($this->authParams as $paramName => $paramValue) {
            if (!array_key_exists($paramName, static::$acceptableParams)) {
                throw new InvalidArgumentException("Invalid parameter: $paramName");
            }

            if (method_exists($this, static::$acceptableParams[$paramName])) {
                $validated = call_user_func([$this, static::$acceptableParams[$paramName]], $paramValue);
            } elseif (function_exists(static::$acceptableParams[$paramName])) {
                $validated = call_user_func(static::$acceptableParams[$paramName], $paramValue);
            } else {
                throw new BadMethodCallException(get_class($this) . " does not have a validation for $paramName");
            }

            if (!$validated) {
                throw new ValueError("$paramName has an invalid value: $paramValue");
            }
        }
    }
}
