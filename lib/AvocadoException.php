<?php

/**
 * AvocadoDB PHP client: exception base class
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

use \Exception as Exception;

/**
 * AvocadoException
 * 
 * Exception base class used to throw Avocado specific exceptions
 *
 * @package AvocadoDbPhpClient
 */
class AvocadoException extends Exception {
}
