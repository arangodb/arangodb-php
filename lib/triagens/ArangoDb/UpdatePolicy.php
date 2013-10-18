<?php

/**
 * ArangoDB PHP client: update policies
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Document update policies
 *
 * <br />
 *
 * @package triagens\ArangoDb
 * @since   0.2
 */
class UpdatePolicy
{
    /**
     * last update will win in case of conflicting versions
     */
    const LAST = 'last';

    /**
     * an error will be returned in case of conflicting versions
     */
    const ERROR = 'error';

    /**
     * Check if the supplied policy value is valid
     *
     * @throws ClientException
     *
     * @param string $value - update policy value
     *
     * @return void
     */
    public static function validate($value)
    {
        assert(is_string($value));

        if (!in_array($value, array(self::LAST, self::ERROR))) {
            throw new ClientException('Invalid update policy');
        }
    }
}
