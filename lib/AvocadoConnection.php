<?php

namespace triagens;

class AvocadoConnection {
  private $_options = array();

  const OPTION_HOST     = "host";
  const OPTION_PORT     = "port";
  const OPTION_TIMEOUT  = "timeout";

  const DEFAULT_PORT    = 8529;
  const DEFAULT_TIMEOUT = 2;

  public function __construct(array $options) {
    $options = array_merge($this->getDefaultOptions(), $options);

    $this->validateOptions($options);
    $this->_options = $options;
  }

  public function getOption($name) {
    if (!array_key_exists($name, $this->_options)) {
      throw new AvocadoException("invalid option " . $name);
    }

    return $this->_options[$name];
  }

  public function post($url, $data) {
    $response = $this->executeRequest("POST", $url, $data);
    return $this->parseResponse($response);
  }
  
  public function put($url, $data) {
    $response = $this->executeRequest("PUT", $url, $data);
    return $this->parseResponse($response);
  }

  private function parseResponse(AvocadoResponse $response) {
    $httpCode = $response->getHttpCode();
    if ($httpCode < 200 || $httpCode >= 400) {
      throw new AvocadoException($response->getResult());
    }

    $body = $response->getBody();
    $json = json_decode($body, true);

    if (!is_array($json)) {
      throw new AvocadoException("malformed result");
    }

    return $json;
  }
  
  public function executeRequest($method, $url, $data) {
    $connection = $this->getConnection();
    if ($connection) {
      $body = $method . " " . $url . " HTTP/1.1\r\n" .
              "Content-Length: " . strlen($data) . "\r\n\r\n".
              $data;

      @fwrite($connection, $body);
      @fflush($connection);

      $result = "";
      while (!feof($connection)) {
        $read = fread($connection, 8192);
        $result .= $read;
        if (strlen($read) < 8192) {
          break;
        }
      }

      fclose($connection);
    }

    return new AvocadoResponse($result);
  }

  private function getDefaultOptions() {
    return array(
      self::OPTION_PORT    => self::DEFAULT_PORT,
      self::OPTION_TIMEOUT => self::DEFAULT_TIMEOUT,
    );
  }

  private function validateOptions(array $options) {
    if (!isset($options[self::OPTION_HOST]) || !is_string($options[self::OPTION_HOST])) {
      throw new AvocadoException("host should be a string");
    }
    if (!isset($options[self::OPTION_PORT]) || !is_int($options[self::OPTION_PORT])) {
      throw new AvocadoException("port should be an integer");
    }
  }

  private function getConnection() {
    $fp = @fsockopen($this->getOption(self::OPTION_HOST),
                     $this->getOption(self::OPTION_PORT), 
                     &$number,
                     &$message, 
                     $this->getOption(self::OPTION_TIMEOUT)); 
    if (!$fp) {
      throw new AvocadoException($message, $number);
    }

    return $fp;
  }
}
