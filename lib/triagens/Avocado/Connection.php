<?php

/**
 * AvocadoDB PHP client: connection
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * Provides access to the AvocadoDB server
 * As all access is done using HTTP, we do not need to establish a
 * persistent connection and keep its state.
 * Instead, connections are established on the fly for each request
 * and are destroyed afterwards.
 *
 * @package AvocadoDbPhpClient
 */
class Connection {
  /**
   * Connection options
   * @var array 
   */
  private $_options;

  /**
   * Set up the connection object, validate the options provided
   *
   * @throws Exception
   * @param array $options - initial connection options
   * @return void
   */
  public function __construct(array $options) {
    $this->_options = new ConnectionOptions($options);
  }

  /**
   * Get an option set for the connection
   *
   * @throws ClientException
   * @param string name - name of option
   * @return mixed
   */
  public function getOption($name) {
    assert(is_string($name));

    return $this->_options[$name];
  }
  
  /**
   * Issue an HTTP GET request
   *
   * @throws Exception
   * @param string $url - GET URL
   * @return HttpResponse
   */
  public function get($url) {
    $response = $this->executeRequest(HttpHelper::METHOD_GET, $url, '');
    return $this->parseResponse($response);
  }
  
  /**
   * Issue an HTTP POST request with the data provided
   *
   * @throws Exception
   * @param string $url - POST URL
   * @param string $data - body to post
   * @return HttpResponse
   */
  public function post($url, $data) {
    $response = $this->executeRequest(HttpHelper::METHOD_POST, $url, $data);
    return $this->parseResponse($response);
  }

  /**
   * Issue an HTTP PUT request with the data provided
   *
   * @throws Exception
   * @param string $url - PUT URL
   * @param string $data - body to post
   * @return HttpResponse
   */
  public function put($url, $data) {
    $response = $this->executeRequest(HttpHelper::METHOD_PUT, $url, $data);
    return $this->parseResponse($response);
  }
  
  /**
   * Issue an HTTP DELETE request with the data provided
   *
   * @throws Exception
   * @param string $url - DELETE URL
   * @return HttpResponse
   */
  public function delete($url) {
    $response = $this->executeRequest(HttpHelper::METHOD_DELETE, $url, '');
    return $this->parseResponse($response);
  }

  /**
   * Parse the response return the body values as an assoc array
   *
   * @throws Exception
   * @param HttpResponse $response - the response as supplied by the server
   * @return HttpResponse
   */
  private function parseResponse(HttpResponse $response) {
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
          $exception = new ServerException($response->getResult(), $httpCode);
          $exception->setDetails($details);
          throw $exception;
        }
      }

      // no details found, throw normal exception
      throw new ServerException($response->getResult(), $httpCode);
    }

    return $response;
  }
  
  /**
   * Execute an HTTP request and return the results
   * 
   * This function will throw if no connection to the server can be established or if
   * there is a problem during data exchange with the server.
   * The function might temporarily alter the value of the php.ini value 'default_socket_timeout' but
   * will restore it.
   *
   * @throws Exception
   * @param string $method - HTTP request method
   * @param string $url - HTTP URL
   * @param string $data - data to post in body
   * @return HttpResponse
   */
  private function executeRequest($method, $url, $data) {
    HttpHelper::validateMethod($method);

    // create request data
    $request = HttpHelper::buildRequest($this->_options[ConnectionOptions::OPTION_HOST], $method, $url, $data);
    
    $traceFunc = $this->_options[ConnectionOptions::OPTION_TRACE];
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

    $scope = new Scope($getFunc, $setFunc);
    $setFunc($this->_options[ConnectionOptions::OPTION_TIMEOUT]);

    // open the socket. note: this might throw if the connection cannot be established
    $connection = HttpHelper::createConnection($this->_options);
    if ($connection) {
      // send data and get response back
      $result = HttpHelper::transfer($connection, $request);
      // must close the connection
      fclose($connection);
      $scope->leave();
      
      if ($traceFunc) {
        // call tracer func
        $traceFunc('receive',$result);
      }

      return new HttpResponse($result);
    } 

    $scope->leave();
    throw new ClientException('Whoops, this should never happen');
  }

}
