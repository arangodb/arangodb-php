<?php

/**
 * ArangoDB PHP client: client exception
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * This exception type will be thrown by the client when there is an error
 * on the client side, i.e. something the server is not involved in.
 *
 * @package triagens\ArangoDb
 */
class ClientException extends
    Exception
{
    /**
     * Return a string representation of the exception
     *
     * @return string - string representation
     */
    public function __toString()
    {
        return __CLASS__ . ': ' . $this->getMessage();
    }
}
