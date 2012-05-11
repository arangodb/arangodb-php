<?php

/**
 * ArangoDB PHP client: client exception
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * This exception type will be thrown by the client when there is an error
 * on the client side, i.e. something the server is not involved in.
 *
 * @package ArangoDbPhpClient
 */
class ClientException extends Exception {
}
