<?php

/**
 * ArangoDB PHP client: bind variables
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * A simple container for bind variables
 *
 * This container also handles validation of the bind values.<br>
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class BindVars
{
    /**
     * Current bind values
     *
     * @var array
     */
    private $_values = array();

    /**
     * Get all registered bind variables
     *
     * @return array - array of all registered bind variables
     */
    public function getAll()
    {
        return $this->_values;
    }

    /**
     * Get the number of bind variables registered
     *
     * @return int - number of bind variables registered
     */
    public function getCount()
    {
        return count($this->_values);
    }

    /**
     * Get the value of a bind variable with a specific name
     *
     * @param string $name - name of bind variable
     *
     * @return mixed - value of bind variable
     */
    public function get($name)
    {
        if (!array_key_exists($name, $this->_values)) {
            return null;
        }

        return $this->_values[$name];
    }

    /**
     * Set the value of a single bind variable or set all bind variables at once
     *
     * This will also validate the bind values.
     *
     * Allowed value types for bind parameters are string, int,
     * double, bool and array. Arrays must not contain any other
     * than these types.
     *
     * @throws ClientException
     *
     * @param mixed  $name  - name of bind variable OR an array with all bind variables
     * @param string $value - value for bind variable
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $value) {
                ValueValidator::validate($value);
            }
            $this->_values = $name;
        } else {
            if (is_int($name) || is_string($name)) {
                $this->_values[(string) $name] = $value;
                ValueValidator::validate($value);
            } else {
                throw new ClientException('Bind variable name should be string, int or array');
            }
        }
    }
}
