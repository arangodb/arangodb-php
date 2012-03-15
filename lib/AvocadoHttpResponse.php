<?php

/**
 * AvocadoDB PHP client: HTTP response
 * 
 * @modulegroup AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoHttpResponse
 * 
 * Container class for HTTP responses
 */
class AvocadoHttpResponse {
  private $_header  = '';
  private $_body    = '';
  private $_headers = array();
  private $_result  = '';
  private $_httpCode;

  /**
   * Set up the response
   *
   * @param string $responseString
   * @return void
   */
  public function __construct($responseString) {
    $inBody = false;

    foreach (explode("\r\n", $responseString) as $line) {
      if (!$inBody and $line === '') {
        $inBody = true;
        continue;
      }
      if ($inBody) {
        $this->_body .= $line;
      }
      else {
        if ($this->_header === '') {
          $this->_result = $line;
          if (preg_match("/^HTTP\/\d+\.\d+\s+(\d+)/",$line, $matches)) {
            $this->_httpCode = (int) $matches[1];
          }
        }
        else {
          list($key, $value) = explode(':', $line, 2);
          $this->_headers[trim($key)] = trim($value);
        }

        $this->_header .= $line;
      }
    }
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
}
