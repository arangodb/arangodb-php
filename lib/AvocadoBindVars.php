<?php

namespace triagens;

class AvocadoBindVars {
  private $_values = array();

  public function getAll() {
    return $this->_values;
  }

  public function getCount() {
    return count($this->_values);
  }

  public function get($key) {
    if (!array_key_exists($key, $this->_values)) {
      return NULL;
    }

    return $this->_values[$key];
  }

  public function set($key, $value = NULL) {
    if (is_array($key)) {
      foreach ($key as $value) {
        $this->validateValue($value);
      }
      $this->_values = $key;
    }
    else if (is_int($key) || is_string($key)) {
      $key = (string) $key;
      $this->_values[$key] = $value;
      $this->validateValue($value);
    }
    else {
      throw new AvocadoException("Bind variable name should be string, int or array");
    }
  }
  
  private function validateValue($value) {
    if (is_string($value) || is_int($value) || is_double($value) || is_bool($value)) {
      // valid type
      return;
    }

    if (is_array($value)) {
      foreach ($value as $subValue) {
        $this->validateValue($subValue);
      }

      return;
    }

    throw new AvocadoException("Invalid bind parameter value");
  }
}
