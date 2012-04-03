<?php

/**
 * AvocadoDB PHP client: base handler
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * A base class for REST-based handlers
 *
 * @package AvocadoDbPhpClient
 */
abstract class Handler {
  /**
   * Connection object
   * @param Connection
   */
  private $_connection;

  /**
   * Construct a new handler
   *
   * @param Connection $connection - connection to be used
   * @return void
   */
  public function __construct(Connection $connection) {
    $this->_connection = $connection;
  }

  /**
   * Return the connection object
   *
   * @return Connection - the connection object
   */
  protected function getConnection() {
    return $this->_connection;
  }
}
