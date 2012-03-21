<?php

/**
 * AvocadoDB PHP client: URL helper methods
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * URLHelper
 * 
 * Some helper methods to construct and process URLs
 *
 * @package AvocadoDbPhpClient
 */
abstract class URLHelper {
  /**
   * Get the document id from a location header
   *
   * @param string $location - HTTP response location header
   * @return string - document id parsed from header
   */
  public static function getDocumentIdFromLocation($location) {
    list(,,, $id) = explode('/', $location);
    return $id;
  }
  
  /**
   * Construct a URL from a base URL and additional parts, seperated with '/' each
   * This function accepts variable arguments.
   *
   * @param string $baseUrl - base URL
   * @return string - assembled URL
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
  
  /**
   * Append parameters to a URL
   * Parameter values will be URL-encoded
   *
   * @param string $baseUrl - base URL
   * @param array $params - an array of parameters
   * @return string - the assembled URL
   */
  public static function appendParamsUrl($baseUrl, array $params) {
    $url = $baseUrl . '?' . http_build_query($params);

    return $url;
  }

}
