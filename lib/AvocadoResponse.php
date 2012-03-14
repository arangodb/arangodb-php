<?php

namespace triagens;

class AvocadoResponse {
  private $_header  = "";
  private $_body    = "";
  private $_headers = array();
  private $_result  = "";
  private $_httpCode;

  public function __construct($responseString) {
    $inBody = false;

    foreach (explode("\r\n", $responseString) as $line) {
      if (!$inBody and $line === "") {
        $inBody = true;
        continue;
      }
      if ($inBody) {
        $this->_body .= $line;
      }
      else {
        if ($this->_header === "") {
          $this->_result = $line;
          if (preg_match("/^HTTP\/\d+\.\d+\s+(\d+)/",$line, $matches)) {
            $this->_httpCode = (int) $matches[1];
          }
        }
        else {
          list($key, $value) = explode(":", $line, 2);
          $this->_headers[trim($key)] = trim($value);
        }

        $this->_header .= $line;
      }
    }
  }

  public function getHttpCode() {
    return $this->_httpCode;
  }

  public function getHeaders() {
    return $this->_headers;
  }

  public function getBody() {
    return $this->_body;
  }

  public function getResult() {
    return $this->_result;
  }
}
