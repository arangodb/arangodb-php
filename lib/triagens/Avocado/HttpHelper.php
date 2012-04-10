<?php

/**
 * AvocadoDB PHP client: http helper methods
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * Helper methods for HTTP request/response handling
 *
 * @package AvocadoDbPhpClient
 */
class HttpHelper {
  /**
   * HTTP POST string constant
   */
  const METHOD_POST     = 'POST';
  
  /**
   * HTTP PUT string constant
   */
  const METHOD_PUT      = 'PUT';
  
  /**
   * HTTP DELETE string constant
   */
  const METHOD_DELETE   = 'DELETE';
  
  /**
   * HTTP GET string constant
   */
  const METHOD_GET      = 'GET';

  /**
   * Chunk size (number of bytes processed in one batch)
   */
  const CHUNK_SIZE      = 8192;
  
  /**
   * End of line mark used in HTTP
   */
  const EOL             = "\r\n";
  
  /**
   * HTTP protocol used
   */
  const PROTOCOL        = 'HTTP/1.1';
  
  /**
   * Validate an HTTP request method name
   *
   * @throws ClientException
   * @param string $method - method name
   * @return bool - always true, will throw if an invalid method name is supplied
   */
  public static function validateMethod($method) {
    if ($method === self::METHOD_POST   ||
        $method === self::METHOD_PUT    ||
        $method === self::METHOD_DELETE ||
        $method === self::METHOD_GET) {
      return true;
    }

    throw new ClientException('Invalid request method');
  }

  /**
   * Create a request string (header and body)
   *
   * @param string $host - host name
   * @param string $method - HTTP method
   * @param string $url - HTTP URL
   * @param string $body - optional body to post
   * @return string - assembled HTTP request string
   */
  public static function buildRequest($host, $method, $url, $body) {
    $type = '';
    $length = strlen($body);

    if ($length > 0) {
      $type = 'Content-Type: application/json' . self::EOL;
    }

    $request = sprintf('%s %s %s%s', $method, $url, self::PROTOCOL, self::EOL) .
               sprintf('Host: %s%s', $host, self::EOL) . 
               $type.
               sprintf('Content-Length: %s%s%s', $length, self::EOL, self::EOL) .
               $body; 

    return $request;
  }

  /**
   * Execute an HTTP request on an opened socket
   * It is the caller's responsibility to close the socket
   *
   * @param resource $socket - connection socket (must be open)
   * @param string $request - complete HTTP request as a string
   * @return string - HTTP response string as provided by the server
   */
  public static function transfer($socket, $request) {
    assert(is_resource($socket));
    assert(is_string($request));

    @fwrite($socket, $request);
    @fflush($socket);

    $result = '';
    while (!feof($socket)) {
      $read = @fread($socket, self::CHUNK_SIZE);
      $result .= $read;
      if (strlen($read) < self::CHUNK_SIZE) {
        break;
      }
    }

    return $result;
  }

  /**
   * Create a one-time HTTP connection by opening a socket to the server
   * It is the caller's responsibility to close the socket
   *
   * @throws ConnectException
   * @param ConnectionOptions $options - connection options
   * @return resource - socket with server connection, will throw when no connection can be established
   */
  public static function createConnection(ConnectionOptions $options) {
    $fp = @fsockopen($options[ConnectionOptions::OPTION_HOST],
                     $options[ConnectionOptions::OPTION_PORT], 
                     $number,
                     $message, 
                     $options[ConnectionOptions::OPTION_TIMEOUT]); 
    if (!$fp) {
      throw new ConnectException($message, $number);
    }

    return $fp;
  }
}
