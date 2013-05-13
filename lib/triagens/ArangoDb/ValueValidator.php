<?php

/**
 * ArangoDB PHP client: value validator
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * A simple validator for values to be stored in the database
 *
 * @package triagens\ArangoDb
 */
class ValueValidator
{
    /**
     * Validate the value of a variable
     *
     * Allowed value types are string, integer, double and boolean. Arrays are also allowed if they contain only one of the former types.
     *
     * @throws ClientException
     *
     * @param mixed $value - value to validate
     *
     * @return void - will throw if an invalid value type is passed
     */
    public static function validate($value)
    {
        if (is_string($value) || is_int($value) || is_double($value) || is_bool($value) || is_null($value)) {
            // type is allowed
            return;
        }

        if (is_array($value)) {
            // must check all elements contained
            foreach ($value as $subValue) {
                self::validate($subValue);
            }

            return;
        }

        // type is invalid
        throw new ClientException('Invalid bind parameter value');
    }
}
