<?php

/**
 * ArangoDB PHP client: HTTP response
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Container class for HTTP responses
 *
 * @package ArangoDbPhpClient
 */
class HttpResponse {
  /**
   * The header retrieved
   * 
   * @var string
   */
  private $_header  = '';
  
  /**
   * The body retrieved
   * 
   * @var string
   */
  private $_body    = '';
  
  /**
   * All headers retrieved as an assoc array
   * 
   * @var array
   */
  private $_headers = array();
  
  /**
   * The result statusline (first line of HTTP response header)
   * 
   * @var string
   */
  private $_result  = '';
  
  /**
   * The HTTP status code of the response
   * 
   * @var int
   */
  private $_httpCode;
  
  /**
   * HTTP location header
   */
  const HEADER_LOCATION = 'location';

  /**
   * Set up the response
   *
   * @throws ClientException
   * @param string $responseString - the complete HTTP response as supplied by the server
   * @return void
   */
  public function __construct($responseString) {
    assert(is_string($responseString));

    $barrier = HttpHelper::EOL . HttpHelper::EOL;
    $border = strpos($responseString, $barrier);
    if ($border === false) {
      throw new ClientException('Got an invalid response from the server');
    }

    $this->_header = substr($responseString, 0, $border);
    $this->_body   = substr($responseString, $border + strlen($barrier));

    $this->setupHeaders();
  }

  /**
   * Return the HTTP status code of the response
   *
   * @return int - HTTP status code of response
   */
  public function getHttpCode() {
    return $this->_httpCode;
  }
  
  /**
   * Return an individual HTTP headers of the response
   *
   * @param string $name - name of header
   * @return string - header value, NULL if header wasn't set in response
   */
  public function getHeader($name) {
    assert(is_string($name));

    $name = strtolower($name);

    if (isset($this->_headers[$name])) {
      return $this->_headers[$name];
    }

    return NULL;
  }

  /**
   * Return the HTTP headers of the response
   *
   * @return array - array of all headers with values
   */
  public function getHeaders() {
    return $this->_headers;
  }
  
  /**
   * Return the location HTTP header of the response
   *
   * @return string - header value, NULL is header wasn't set in response
   */
  public function getLocationHeader() {
    return $this->getHeader(self::HEADER_LOCATION);
  }
  
  /**
   * Return the body of the response
   *
   * @return string - body of the response
   */
  public function getBody() {
    return $this->_body;
  }
  
  /**
   * Return the result line (first header line) of the response
   *
   * @return string - the result line (first line of header)
   */
  public function getResult() {
    return $this->_result;
  }
  
  /**
   * Return the data from the JSON-encoded body
   *
   * @throws ClientException
   * @return array - array of values from the JSON-encoded response body
   */
  public function getJson() {
    $body = $this->getBody();
    $json = json_decode($body, true);

    if (!is_array($json)) {
      // should be an array, fail otherwise
      throw new ClientException('Got a malformed result from the server');
    }

    return $json;
  }

  /**
   * Set up an array of HTTP headers
   *
   * @return void
   */
  private function setupHeaders() {
    foreach (explode(HttpHelper::EOL, $this->_header) as $lineNumber => $line) {
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
