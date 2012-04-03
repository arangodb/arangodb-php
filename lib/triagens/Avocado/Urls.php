<?php

/**
 * AvocadoDB PHP client: Base URLs
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * Some basic URLs
 *
 * @package AvocadoDbPhpClient
 */
abstract class URLs {
  /**
   * URL base part for all document-related REST calls
   */
  const URL_DOCUMENT           = '/document';
  
  /**
   * URL base part for all collection-related REST calls
   */
  const URL_COLLECTION         = '/_api/collection';

  /**
   * base URL part for cursor related operations
   */
  const URL_CURSOR             = '/_api/cursor'; 

  /**
   * base URL part for select-by-example
   */
  const URL_EXAMPLE            = '/_api/simple/by-example'; 
}
