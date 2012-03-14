<?php

namespace triagens;

use \Iterator as Iterator;

class AvocadoCursor implements Iterator {
  private $_connection;
  private $_options;
  private $_result;
  private $_hasMore;
  private $_id;
  private $_position;
  private $_length;

  const URL = "/_api/cursor"; 

  public function __construct(AvocadoConnection $connection, array $data, array $options) {
    $this->_connection = $connection;

    $this->_id = NULL;
    if (isset($data["_id"])) {
      $this->_id = $data["_id"];
    }

    $this->_options = $options;
    $this->_hasMore = (bool) $data["hasMore"];
    $this->_result = $this->sanitize((array) $data["result"]);
    $this->setLength();

    $this->rewind();
  }

  public function delete() {
    if (!$this->_id) {
      return false;
    }

    try {
      $this->_connection->delete(self::URL . "/" . $this->_id, "");
      return true;
    } 
    catch (Exception $e) {
      return false;
    }
  }

  public function getCount() {
    while ($this->_hasMore) {
      $this->fetchOutstanding();
    }

    return $this->_length;
  }

  public function getAll() {
    while ($this->_hasMore) {
      $this->fetchOutstanding();
    }

    return $this->_result;
  }

  public function __toString() {
    return $this->getAll();
  }

  public function rewind() {
    $this->_position = 0;
  }

  public function current() {
    return $this->_result[$this->_position];
  }
  
  public function key() {
    return $this->_position;
  }

  public function next() {
    ++$this->_position;
  }

  public function valid() {
    if ($this->_position <= $this->_length -1) {
      return true;
    }

    if (!$this->_hasMore || !$this->_id) {
      return false;
    }
  
    $this->fetchOutstanding();

    return ($this->_position <= $this->_length - 1);
  }

  private function sanitize(array $rows) {
    if (isset($this->_options["sanitize"]) and $this->_options["sanitize"]) {
      foreach ($rows as $key=>$value) {
        unset($rows[$key]["_id"]);
        unset($rows[$key]["_rev"]);
      }
    }
    return $rows;
  }
      
  private function fetchOutstanding() {
    // continuation
    $data = $this->_connection->put(self::URL . "/" . $this->_id, "");
    $this->_hasMore = (bool) $data["hasMore"];
    $this->_result = array_merge($this->_result, $this->sanitize((array) $data["result"]));

    $this->setLength();
  }

  private function setLength() {
    $this->_length = count($this->_result); 
  }
}
