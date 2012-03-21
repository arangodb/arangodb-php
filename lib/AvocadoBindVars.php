<?php

/**
 * AvocadoDB PHP client: bind variables
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoBindVars
 * 
 * A simple container for bind variables
 * This container also handles validation of the bind values.
 *
 * @package AvocadoDbPhpClient
 */
class AvocadoBindVars {
  /**
   * Current bind values
   * @var array
   */
  private $_values = array();

  /**
   * Get all registered bind variables
   *
   * @return array
   */
  public function getAll() {
    return $this->_values;
  }

  /**
   * Get the number of bind variables registered
   *
   * @return int
   */
  public function getCount() {
    return count($this->_values);
  }
  
  /**
   * Get the value of a bind variable with a specific name
   *
   * @param string name
   * @return int
   */
  public function get($name) {
    if (!array_key_exists($name, $this->_values)) {
      return NULL;
    }

    return $this->_values[$name];
  }
  
  /**
   * Set the value of a single bind variable or set all bind variables at once
   * This will also validate the bind values.
   * Allowed value types for bind parameters are string, int,
   * double, bool and array. Arrays must not contain any other
   * than these types.
   *
   * @throws AvocadoException
   * @param string name
   * @param string value
   * @return void
   */
  public function set($name, $value = NULL) {
    if (is_array($name)) {
      foreach ($name as $value) {
        AvocadoValueValidator::validate($value);
      }
      $this->_values = $name;
    }
    else if (is_int($name) || is_string($name)) {
      $key = (string) $name;
      $this->_values[$name] = $value;
      AvocadoValueValidator::validate($value);
    }
    else {
      throw new AvocadoClientException('Bind variable name should be string, int or array');
    }
  }
  
}
