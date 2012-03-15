<?php

/**
 * AvocadoDB PHP client: connection
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoConnection
 * 
 * Provides access to the AvocadoDB server
 * As all access is done using HTTP, we do not need to establish a
 * persistent connection and keep its state.
 * Instead, connections are established on the fly for each request
 * and are destroyed afterwards.
 *
 * @package AvocadoDbPhpClient
 */
class AvocadoConnection {
  private $_options;

  /**
   * Set up the connection object, validate the options provided
   *
   * @throws AvocadoException
   * @param array $options
   * @return void
   */
  public function __construct(array $options) {
    $this->_options = new AvocadoConnectionOptions($options);
  }
  
  /**
   * Issue an HTTP GET request
   *
   * @throws AvocadoException
   * @param string $url
   * @return array
   */
  public function get($url) {
    $response = $this->executeRequest(AvocadoHttpHelper::METHOD_GET, $url, '');
    return $this->parseResponse($response);
  }
  
  /**
   * Issue an HTTP POST request with the data provided
   *
   * @throws AvocadoException
   * @param string $url
   * @param string $data
   * @return array
   */
  public function post($url, $data) {
    $response = $this->executeRequest(AvocadoHttpHelper::METHOD_POST, $url, $data);
    return $this->parseResponse($response);
  }

  /**
   * Issue an HTTP PUT request with the data provided
   *
   * @throws AvocadoException
   * @param string $url
   * @param string $data
   * @return array
   */
  public function put($url, $data) {
    $response = $this->executeRequest(AvocadoHttpHelper::METHOD_PUT, $url, $data);
    return $this->parseResponse($response);
  }
  
  /**
   * Issue an HTTP DELETE request with the data provided
   *
   * @throws AvocadoException
   * @param string $url
   * @param string $data
   * @return array
   */
  public function delete($url) {
    $response = $this->executeRequest(AvocadoHttpHelper::METHOD_DELETE, $url, '');
    return $this->parseResponse($response);
  }

  /**
   * Parse the response return the body values as an assoc array
   *
   * @throws AvocadoException
   * @param AvocadoHttpResponse $response
   * @return AvocadoHttpResponse
   */
  private function parseResponse(AvocadoHttpResponse $response) {
    $httpCode = $response->getHttpCode();

    if ($httpCode < 200 || $httpCode >= 400) {
      // failure on server
      $details = array();

      $body = $response->getBody();
      if ($body != '') {
        // check if we can find details in the response body
        $details = json_decode($body, true);
        if (is_array($details)) {
          // yes, we got details
          $exception = new AvocadoServerException($response->getResult(), $httpCode);
          $exception->setDetails($details);
          throw $exception;
        }
      }

      // no details found, throw normal exception
      throw new AvocadoServerException($response->getResult(), $httpCode);
    }

    return $response;
  }
  
  /**
   * Execute an HTTP request and return the results
   *
   * @throws AvocadoException
   * @param string $method 
   * @param string $url 
   * @param string $data 
   * @return AvocadoHttpResponse
   */
  private function executeRequest($method, $url, $data) {
    // create request data
    $request = AvocadoHttpHelper::buildRequest($this->_options[AvocadoConnectionOptions::OPTION_HOST], $method, $url, $data);
    
    $traceFunc = $this->_options[AvocadoConnectionOptions::OPTION_TRACE];
    if ($traceFunc) {
      // call tracer func
      $traceFunc('send', $request);
    }

    // set socket timeout for this scope 
    $getFunc = function() {
      return ini_get('default_socket_timeout');
    };
    $setFunc = function($value) {
      ini_set('default_socket_timeout', $value);
    };

    $scope = new AvocadoScope($getFunc, $setFunc);
    $setFunc($this->_options[AvocadoConnectionOptions::OPTION_TIMEOUT]);

    // open the socket. note: this might throw if the connection cannot be established
    $connection = AvocadoHttpHelper::createConnection($this->_options);
    if ($connection) {
      // send data and get response back
      $result = AvocadoHttpHelper::transfer($connection, $request);
      // must close the connection
      fclose($connection);
      
      if ($traceFunc) {
        // call tracer func
        $traceFunc('receive',$result);
      }

      return new AvocadoHttpResponse($result);
    } 

    throw new AvocadoClientException('Whoops, this should never happen');
  }

}
