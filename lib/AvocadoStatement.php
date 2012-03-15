<?php

namespace triagens;

class AvocadoStatement {
  private $_connection = NULL;
  private $_batchSize = NULL;
  private $_doCount    = false;
  private $_query      = NULL;
  private $_sanitize   = false;
  private $_bindVars;

  public function __construct(AvocadoConnection $connection, array $data) {
    $this->_connection = $connection;
    $this->_bindVars   = new AvocadoBindVars();

    if (!isset($data["query"])) {
      throw new AvocadoException("query must be set");
    }
    $this->setQuery($data["query"]);
    
    if (isset($data["count"])) {
      $this->setCount($data["count"]);
    }

    if (isset($data["batchSize"])) {
      $this->setMaxResults($data["batchSize"]);
    }

    if (isset($data["bindVars"])) {
      $this->_bindVars->set($data["bindVars"]);
    }

    if (isset($data["sanitize"])) {
      $this->_sanitize = (bool) $data["sanitize"];
    }
     
    $this->createData($data);
  }

  private function createData() {
    $data = array();

    $data["query"]    = $this->_query;
    $data["count"]    = $this->_doCount;
    if ($this->_bindVars->getCount() > 0) {
      $data["bindVars"] = $this->_bindVars->getAll();
    }
    else {
      $data["bindVars"] = NULL;
    }
    if ($this->_batchSize > 0) {
      $data["batchSize"] = $this->_batchSize;
    }

    return $data;
  }

  public function execute() {
    $data = $this->createData();
    $json = json_encode($data);
    $result = $this->_connection->post(AvocadoCursor::URL, $json);

    return new AvocadoCursor($this->_connection, $result, $this->getCursorOptions());
  }

  public function bind($key, $value = NULL) {
    $this->_bindVars->set($key, $value);
  }

  public function setQuery($query) {
    if (!is_string($query)) {
      throw new AvocadoException("Query should be a string");
    }

    $this->_query = $query;
  }

  public function getQuery() {
    return $this->_query;
  }


  public function getBindParameters() {
    return $this->_bindVars;
  }

  public function setCount($value) {
    $this->_doCount = (bool) $value;
  }

  public function getCount() {
    return $this->_doCount;
  }

  public function setMaxResults($value) {
    if (!is_int($value) || (int) $value <= 0) {
      throw new AvocadoException("Max result valuee should be a positive integer");
    }

    $this->_batchSize = (int) $value;
  }

  public function getMaxResults() {
    return $this->_batchSize;
  }

  private function getCursorOptions() {
    return array(
      "sanitize" => (bool) $this->_sanitize,
    );
  }

}
