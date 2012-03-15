<?php

/**
 * AvocadoDB PHP client: URL helper methods
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoURLHelper
 * 
 * Some helper methods to construct and process URLs
 *
 * @package AvocadoDbPhpClient
 */
abstract class AvocadoURLHelper {
  /**
   * Get the document id from a location header
   *
   * @param string $location 
   * @return string 
   */
  public static function getDocumentIdFromLocation($location) {
    list(,,, $id) = explode('/', $location);
    return $id;
  }
  
  /**
   * Construct a URL from a base URL and additional parts
   * This function accepts varargs
   *
   * @param string $baseUrl
   * @return string 
   */
  public static function buildUrl($baseUrl) {
    $argv = func_get_args();
    $argc = count($argv);

    assert($argc > 1);

    $url = $baseUrl;
    for ($i = 1; $i < $argc; ++$i) {
      $url .= '/' . urlencode($argv[$i]);
    }

    return $url;
  }

}
