<?php

/**
 * AvocadoDB PHP client: connection options
 * 
 * @modulegroup AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoConnectionOptions
 * 
 * Simple container for connection options.
 * This class also provides the default values for the connection
 * options and will perform a simple validation of them.
 * It provides array access to its members.
 */
class AvocadoConnectionOptions implements \ArrayAccess {
  private $_values = array();

  const OPTION_HOST     = 'host';
  const OPTION_PORT     = 'port';
  const OPTION_TIMEOUT  = 'timeout';
  const OPTION_TRACE    = 'trace';
  
  const DEFAULT_PORT    = 8529;
  const DEFAULT_TIMEOUT = 2;

  /**
   * Set defaults, use options provided by client and validate them
   *
   * @throws AvocadoException
   * @return void
   */
  public function __construct(array $options) {
    $this->_values = array_merge($this->getDefaults(), $options);
    $this->validate();
  }

  /**
   * Get all options
   *
   * @return array
   */
  public function getAll() {
    return $this->_values;
  }
  
  /**
   * Set and validate a specific option, necessary for ArrayAccess
   *
   * @param string $offset
   * @param string $value
   * @throws AvocadoException
   * @return void
   */
  public function offsetSet($offset, $value) {
    $this->_values[$offset] = $value;
    $this->_validate();
  }
  
  /**
   * Check whether an option exists, necessary for ArrayAccess
   *
   * @param string $offset
   * @return bool
   */
  public function offsetExists($offset) {
    return isset($this->_values[$offset]);
  }
  
  /**
   * Remove an option and validate, necessary for ArrayAccess
   *
   * @param string $offset
   * @throws AvocadoException
   * @return bool
   */
  public function offsetUnset($offset) {
    unset($this->_values[$offset]);
    $this->_validate();
  }

  /**
   * Get a specific option, necessary for ArrayAccess
   *
   * @param string $offset
   * @throws AvocadoException
   * @return mixed
   */
  public function offsetGet($offset) {
    if (!array_key_exists($offset, $this->_values)) {
      throw new AvocadoClientException('Invalid option ' . $offset);
    }

    return $this->_values[$offset];
  }
  
  /**
   * Get the default values for the options
   *
   * @return array
   */
  private function getDefaults() {
    return array(
      self::OPTION_PORT    => self::DEFAULT_PORT,
      self::OPTION_TIMEOUT => self::DEFAULT_TIMEOUT,
      self::OPTION_TRACE   => NULL,
    );
  }
  
  /**
   * Validate the options
   *
   * @throws AvocadoException
   * @return void
   */
  private function validate() {
    if (!isset($this->_values[self::OPTION_HOST]) || !is_string($this->_values[self::OPTION_HOST])) {
      throw new AvocadoClientException('host should be a string');
    }
    if (!isset($this->_values[self::OPTION_PORT]) || !is_int($this->_values[self::OPTION_PORT])) {
      throw new AvocadoClientException('port should be an integer');
    }
  }

}
