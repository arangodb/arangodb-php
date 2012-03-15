<?php

/**
 * AvocadoDB PHP client: HTTP response
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoHttpResponse
 * 
 * Container class for HTTP responses
 *
 * @package AvocadoDbPhpClient
 */
class AvocadoHttpResponse {
  private $_header  = '';
  private $_body    = '';
  private $_headers = array();
  private $_result  = '';
  private $_httpCode;

  const SEPARATOR   = "\r\n";

  /**
   * Set up the response
   *
   * @throws AvocadoException
   * @param string $responseString
   * @return void
   */
  public function __construct($responseString) {
    $barrier = self::SEPARATOR . self::SEPARATOR;
    $border = strpos($responseString, $barrier);
    if ($border === false) {
      throw new AvocadoClientException('Got an invalid response from the server');
    }

    $this->_header = substr($responseString, 0, $border);
    $this->_body   = substr($responseString, $border + strlen($barrier));

    $this->setupHeaders();
  }

  /**
   * Return the HTTP status code of the response
   *
   * @return int
   */
  public function getHttpCode() {
    return $this->_httpCode;
  }
  
  /**
   * Return an individual HTTP headers of the response
   *
   * @return string
   */
  public function getHeader($name) {
    $name = strtolower($name);

    if (isset($this->_headers[$name])) {
      return $this->_headers[$name];
    }

    return NULL;
  }

  /**
   * Return the HTTP headers of the response
   *
   * @return array
   */
  public function getHeaders() {
    return $this->_headers;
  }
  
  /**
   * Return the body of the response
   *
   * @return array
   */
  public function getBody() {
    return $this->_body;
  }
  
  /**
   * Return the result line (first header) of the response
   *
   * @return string
   */
  public function getResult() {
    return $this->_result;
  }
  
  /**
   * Return the data from the JSON-encoded body
   *
   * @throws AvocadoException
   * @return string
   */
  public function getJson() {
    $body = $this->getBody();
    $json = json_decode($body, true);

    if (!is_array($json)) {
      // should be an array, fail otherwise
      throw new AvocadoClientException('Got a malformed result from the server');
    }

    return $json;
  }

  /**
   * Create an array of HTTP headers
   *
   * @return void
   */
  private function setupHeaders() {
    foreach (explode(self::SEPARATOR, $this->_header) as $lineNumber => $line) {
      $line = trim($line);

      if ($lineNumber == 0) {
        // first line of result is special
        $this->_result = $line;
        if (preg_match("/^HTTP\/\d+\.\d+\s+(\d+)/",$line, $matches)) {
          $this->_httpCode = (int) $matches[1];
        }
      }
      else {
        // other lines contain key:value-like headers
        list($key, $value) = explode(':', $line, 2);
        $this->_headers[strtolower(trim($key))] = trim($value);
      }
    }
  }
  
}
