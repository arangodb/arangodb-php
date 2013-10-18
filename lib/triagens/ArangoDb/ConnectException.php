<?php

/**
 * ArangoDB PHP client: connect exception
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Connect-Exception
 *
 * This exception type will be thrown by the client when there is an error
 * during connecting to the server.<br>
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class ConnectException extends
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
