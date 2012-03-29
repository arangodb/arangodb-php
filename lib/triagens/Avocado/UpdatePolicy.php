<?php

/**
 * AvocadoDB PHP client: update policies
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * Document update policies
 *
 * @package AvocadoDbPhpClient
 */
class UpdatePolicy {
  /**
   * last update will win in case of conflicting versions
   */
  const LAST  = 'last';
  
  /**
   * an error will be returned in case of conflicting versions
   */
  const ERROR = 'error';

  /**
   * Check if the supplied policy value is valid
   *
   * @throws ClientException
   * @param string $value - update policy value
   * @return void
   */
  public static function validate($value) {
    assert(is_string($value));

    if (!in_array($value, array(self::LAST, self::ERROR))) {
      throw new ClientException('Invalid update policy');
    }
  }
}
