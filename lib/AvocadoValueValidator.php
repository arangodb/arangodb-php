<?php

/**
 * AvocadoDB PHP client: value validator
 * 
 * @modulegroup AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoValueValidator
 * 
 * A simple validator for values to be stored in the database
 */

class AvocadoValueValidator {
  /**
   * Validate the value of a variable
   *
   * @throws AvocadoException
   * @param mixed value
   * @return void
   */
  public static function validate($value) {
    if (is_string($value) || is_int($value) || is_double($value) || is_bool($value)) {
      // type is allowed
      return;
    }

    if (is_array($value)) {
      // must check all elements contained
      foreach ($value as $subValue) {
        self::validate($subValue);
      }

      return;
    }

    // type is invalid
    throw new AvocadoClientException('Invalid bind parameter value');
  }
}
