<?php

/**
 * AvocadoDB PHP client: connection options
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * Simple container class for connection options.
 * This class also provides the default values for the connection
 * options and will perform a simple validation of them.
 * It provides array access to its members.
 *
 * @package AvocadoDbPhpClient
 */
class ConnectionOptions implements \ArrayAccess {
  /**
   * The current options
   * @var array 
   */
  private $_values           = array();

  /**
   * Host name string index constant
   */
  const OPTION_HOST          = 'host';
  
  /**
   * Port number index constant
   */
  const OPTION_PORT          = 'port';
  
  /**
   * Timeout value index constant
   */
  const OPTION_TIMEOUT       = 'timeout';

  /**
   * Trace function index constant
   */
  const OPTION_TRACE         = 'trace';
  
  /**
   * "Create collections if they don't exist" index constant
   */
  const OPTION_CREATE        = 'create';
  
  /**
   * Update policy index constant
   */
  const OPTION_UPDATE_POLICY = 'policy';
  
  /**
   * Set defaults, use options provided by client and validate them
   *
   * @throws ClientException
   * @param array $options - initial options
   * @return void
   */
  public function __construct(array $options) {
    $this->_values = array_merge($this->getDefaults(), $options);
    $this->validate();
  }

  /**
   * Get all options
   *
   * @return array - all options as an array
   */
  public function getAll() {
    return $this->_values;
  }
  
  /**
   * Set and validate a specific option, necessary for ArrayAccess
   *
   * @throws Exception
   * @param string $offset - name of option
   * @param mixed $value - value for option
   * @return void
   */
  public function offsetSet($offset, $value) {
    $this->_values[$offset] = $value;
    $this->validate();
  }
  
  /**
   * Check whether an option exists, necessary for ArrayAccess
   *
   * @param string $offset -name of option
   * @return bool - true if option exists, false otherwise
   */
  public function offsetExists($offset) {
    return isset($this->_values[$offset]);
  }
  
  /**
   * Remove an option and validate, necessary for ArrayAccess
   *
   * @throws Exception
   * @param string $offset - name of option
   * @return void
   */
  public function offsetUnset($offset) {
    unset($this->_values[$offset]);
    $this->validate();
  }

  /**
   * Get a specific option, necessary for ArrayAccess
   *
   * @throws ClientException
   * @param string $offset - name of option
   * @return mixed - value of option, will throw if option is not set
   */
  public function offsetGet($offset) {
    if (!array_key_exists($offset, $this->_values)) {
      throw new ClientException('Invalid option ' . $offset);
    }

    return $this->_values[$offset];
  }
  
  /**
   * Get the default values for the options
   *
   * @return array - array of default connection options
   */
  private function getDefaults() {
    return array(
      self::OPTION_PORT          => DefaultValues::DEFAULT_PORT,
      self::OPTION_TIMEOUT       => DefaultValues::DEFAULT_TIMEOUT,
      self::OPTION_TRACE         => NULL,
      self::OPTION_CREATE        => false,
      self::OPTION_UPDATE_POLICY => UpdatePolicy::ERROR,
    );
  }
  
  /**
   * Validate the options
   *
   * @throws ClientException
   * @return void - will throw if an invalid option value is found
   */
  private function validate() {
    if (!isset($this->_values[self::OPTION_HOST]) || !is_string($this->_values[self::OPTION_HOST])) {
      throw new ClientException('host should be a string');
    }

    if (!isset($this->_values[self::OPTION_PORT]) || !is_int($this->_values[self::OPTION_PORT])) {
      throw new ClientException('port should be an integer');
    }

    UpdatePolicy::validate($this->_values[self::OPTION_UPDATE_POLICY]);
  }

}
