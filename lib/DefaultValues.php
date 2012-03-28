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
 * Contains default values used by the client
 *
 * @package AvocadoDbPhpClient
 */
abstract class DefaultValues {
  /**
   * Default port number (used if no port specified)
   */
  const DEFAULT_PORT          = 8529;
  
  /**
   * Default timeout value (used if no timeout value specified)
   */
  const DEFAULT_TIMEOUT       = 2;

  /**
   * Default value for waitForSync (fsync all data to disk on document updates/insertions/deletions)
   */
  const DEFAULT_WAIT_SYNC     = false;
  
  /**
   * Default value for createCollection (create the collection on the fly when the first document is added to an unknown collection)
   */
  const DEFAULT_CREATE        = false;

  /**
   * Default update policy
   */
  const DEFAULT_UPDATE_POLICY = UpdatePolicy::ERROR;
}
