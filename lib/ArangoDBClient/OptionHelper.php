<?php
/**
 * ArangoDB PHP client: Options handling utility class
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @author    Tom Regner <tom.regner@fb-research.de>
 * @copyright Copyright 2022, ArangoDB GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 *
 */
abstract class OptionHelper implements \ArrayAccess
{
    /**
     * The current options
     *
     * @var array
     */
    protected $values = [];

    /**
     * This function is called after potentially modifying method calls.
     * Implement it not empty to ensure consistency if necessary, implement it
     * empty otherwise.
     */
    abstract protected function validate() : void;

    /**
     * Set defaults, use options provided by client and validate them
     * @param array $options
     */
    protected function init(array $options) : void
    {
        foreach ($options as $name => $value) {
            $this->values[$name] = $value;
        }
    }

    /**
     * Create and initialize the options instance.
     * @param array $options
     * @throws \ArangoDBClient\ClientException
     */
    public function __construct(array $options)
    {
        $this->init($options);
        $this->validate();
    }

    /**
     * Set and validate a specific option, necessary for ArrayAccess
     *
     * @throws Exception
     *
     * @param string $offset - name of option
     * @param mixed  $value  - value for option
     *
     * @return void
     */
    public function offsetSet($offset, $value) : void
    {
        $this->values[$offset] = $value;
        $this->validate();
    }

    /**
     * Check whether an option exists, necessary for ArrayAccess
     *
     * @param string $offset -name of option
     *
     * @return bool - true if option exists, false otherwise
     */
    public function offsetExists($offset) : bool
    {
        return isset($this->values[$offset]);
    }

    /**
     * Remove an option and validate, necessary for ArrayAccess
     *
     * @throws Exception
     *
     * @param string $offset - name of option
     *
     * @return void
     */
    public function offsetUnset($offset) : void
    {
        unset($this->values[$offset]);
        $this->validate();
    }

    /**
     * Get a specific option, necessary for ArrayAccess
     *
     * @throws ClientException
     *
     * @param string $offset - name of option
     *
     * @return mixed - value of option, will throw if option is not set
     */
    public function offsetGet($offset) : mixed
    {
        if (!array_key_exists($offset, $this->values)) {
            throw new ClientException('Invalid option ' . $offset);
        }

        return $this->values[$offset];
    }

}
