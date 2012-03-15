<?php

/**
 * AvocadoDB PHP client: http helper methods
 * 
 * @modulegroup AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoHttpHelper
 * 
 * Helper methods for HTTP request/response handling
 */
class AvocadoHttpHelper {
  const METHOD_POST     = 'POST';
  const METHOD_PUT      = 'PUT';
  const METHOD_DELETE   = 'DELETE';
  const METHOD_GET      = 'GET';

  const CHUNK_SIZE      = 8192;

  const EOL             = "\r\n";

  /**
   * Create a request string (header and body)
   *
   * @param string $host
   * @param string $method
   * @param string $url
   * @param string $body
   * @return string
   */
  public static function buildRequest($host, $method, $url, $body) {
    $type = '';
    $length = strlen($body);

    if ($length > 0) {
      $type = 'Content-Type: application/json' . self::EOL;
    }

    $request = $method . ' ' . $url . ' HTTP/1.1' . self::EOL .
               'Host: ' . $host . self::EOL . 
               $type.
               'Content-Length: ' . $length . self::EOL . self::EOL .
               $body; 

    return $request;
  }
  
  /**
   * Execute an HTTP request on an opened socket
   * It is the caller's responsibility to close the socket
   *
   * @param resource $socket
   * @param string $request
   * @return string
   */
  public static function transfer($socket, $request) {
    assert(is_resource($socket));

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
   * @throws AvocadoException
   * @param AvocadoConnectionOptions $options
   * @return resource
   */
  public static function createConnection(AvocadoConnectionOptions $options) {
    $fp = @fsockopen($options[AvocadoConnectionOptions::OPTION_HOST],
                     $options[AvocadoConnectionOptions::OPTION_PORT], 
                     &$number,
                     &$message, 
                     $options[AvocadoConnectionOptions::OPTION_TIMEOUT]); 
    if (!$fp) {
      throw new AvocadoConnectException($message, $number);
    }

    return $fp;
  }
}
