<?php

/**
 * ArangoDB PHP client: endpoint
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Endpoint specification
 * An endpoint contains the server location the client connects to
 * the following endpoint types are currently supported (more to be added later):
 * - tcp://host:port for tcp connections
 * - unix://socket for UNIX sockets
 *
 * @package ArangoDbPhpClient
 */
class Endpoint {
  /**
   * Current endpoint value
   * @var string
   */
  private $_value;

  /**
   * TCP endpoint type 
   */
  const TYPE_TCP    = 'tcp';

  /**
   * UNIX socket endpoint type 
   */
  const TYPE_UNIX   = 'unix';
      
  /**
   * Regexp for TCP endpoints
   */
  const REGEXP_TCP  = '/^tcp:\/\/(.+?):(\d+)\/?$/';

  /**
   * Regexp for UNIX socket endpoints
   */
  const REGEXP_UNIX = '/^unix:\/\/(.+)$/';

  /**
   * Create a new endpoint
   *
   * @throws ClientException
   * @param string $value - endpoint specification
   * @return void
   */
  public function __construct($value) {
    if (!self::isValid($value)) {
      throw new ClientException(sprintf("invalid endpoint specification '%s'", $value));
    }

    $this->_value = $value;
  }

  /**
   * Return a string representation of the endpoint
   *
   * @return string - string representation of the endpoint
   */
  public function __toString() {
    return $this->_value;
  }

  /**
   * Return the type of an endpoint
   *
   * @param string $value - endpoint specification value
   * @return string - endpoint type
   */
  public static function getType($value) {
    if (preg_match(self::REGEXP_TCP, $value)) {
      return self::TYPE_TCP;
    }

    if (preg_match(self::REGEXP_UNIX, $value)) {
      return self::TYPE_UNIX;
    }

    return NULL;
  }
  
  /**
   * Return the host name of an endpoint
   *
   * @param string $value - endpoint specification value
   * @return string - host name
   */
  public static function getHost($value) {
    if (preg_match(self::REGEXP_TCP, $value, $matches)) {
      return $matches[1];
    }

    return NULL;
  }

  /**
   * check whether an endpoint specification is valid
   *
   * @param string $value - endpoint specification value
   * @return bool - true if endpoint specification is valid, false otherwise
   */
  public static function isValid($value) {
    if (!is_string($value)) {
      return false;
    }

    $type = self::getType($value);
    if ($type === NULL) {
      return false;
    }

    return true;
  }

}
