<?php

/**
 * AvocadoDB PHP client: default values
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * Defaults
 * 
 * Contains default values used by the server
 *
 * @package AvocadoDbPhpClient
 */
abstract class DefaultValues {
  /**
   * Default port number (used if no port specified)
   */
  const DEFAULT_PORT    = 8529;
  
  /**
   * Default timeout value (used if no timeout value specified)
   */
  const DEFAULT_TIMEOUT = 2;
}
