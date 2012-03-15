<?php

/**
 * AvocadoDB PHP client: result set cursor
 * 
 * @modulegroup AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoCursor
 * 
 * Provides access to the results of a select statement
 * The cursor might not contain all results in the beginning.
 * If the result set is too big to be transferred in one go, the
 * cursor might issue additional HTTP requests to fetch the
 * remaining results from the server.
 */
class AvocadoCursor implements \Iterator {
  private $_connection;
  private $_options;
  private $_result;
  private $_hasMore;
  private $_id;
  private $_position;
  private $_length;

  const URL             = '/_api/cursor'; 

  const ENTRY_ID        = '_id';
  const ENTRY_REV       = '_rev';
  const ENTRY_HASMORE   = 'hasMore';
  const ENTRY_RESULT    = 'result';
  const ENTRY_SANITIZE  = 'sanitize';

  /**
   * Initialise the cursor with the first results and some metadata
   *
   * @param AvocadoConnection $connection
   * @param array $data
   * @param array $options
   * @return void 
   */
  public function __construct(AvocadoConnection $connection, array $data, array $options) {
    $this->_connection = $connection;

    $this->_id = NULL;
    if (isset($data[self::ENTRY_ID])) {
      $this->_id = $data[self::ENTRY_ID];
    }

    // attribute must be there
    assert(isset($data[self::ENTRY_HASMORE]));
    $this->_hasMore = (bool) $data[self::ENTRY_HASMORE];

    $this->_options = $options;
    $this->_result = $this->sanitize((array) $data[self::ENTRY_RESULT]);
    $this->setLength();

    $this->rewind();
  }
  
  /**
   * Explicitly delete the cursor
   * This might issue an HTTP DELETE request to inform the server about
   * the deletion.
   *
   * @throws AvocadoException
   * @return bool
   */
  public function delete() {
    if ($this->_id) {
      try {
        $this->_connection->delete(self::URL . '/' . $this->_id);
        return true;
      } 
      catch (Exception $e) {
      }
    }

    return false;
  }
  
  /**
   * Get the total number of results in the cursor
   * This might issue additional HTTP requests to fetch any outstanding
   * results from the server
   *
   * @throws AvocadoException
   * @return int
   */
  public function getCount() {
    while ($this->_hasMore) {
      $this->fetchOutstanding();
    }

    return $this->_length;
  }
  
  /**
   * Get all results as an array 
   * This might issue additional HTTP requests to fetch any outstanding
   * results from the server
   *
   * @throws AvocadoException
   * @return array
   */
  public function getAll() {
    while ($this->_hasMore) {
      $this->fetchOutstanding();
    }

    return $this->_result;
  }
  
  /**
   * Rewind the cursor, necessary for Iterator
   *
   * @return void
   */
  public function rewind() {
    $this->_position = 0;
  }

  /**
   * Return the current result row, necessary for Iterator
   *
   * @return mixed
   */
  public function current() {
    return $this->_result[$this->_position];
  }
  
  /**
   * Return the index of the current result row, necessary for Iterator
   *
   * @return int
   */
  public function key() {
    return $this->_position;
  }

  /**
   * Advance the cursor, necessary for Iterator
   *
   * @return void
   */
  public function next() {
    ++$this->_position;
  }
  
  /**
   * Check if cursor can be advanced further, necessary for Iterator
   * This might issue additional HTTP requests to fetch any outstanding
   * results from the server
   *
   * @throws AvocadoException
   * @return bool
   */
  public function valid() {
    if ($this->_position <= $this->_length -1) {
      // we have more results than the current position is
      return true;
    }

    if (!$this->_hasMore || !$this->_id) {
      // we don't have more results, but the cursor is exhausted
      return false;
    }
  
    // need to fetch additional results from the server
    $this->fetchOutstanding();

    return ($this->_position <= $this->_length - 1);
  }

  /**
   * Sanitize the result set rows
   * This will remove the _id and _rev attributes from the results if the
   * "sanitize" option is set
   *
   * @param array $rows
   * @return array
   */
  private function sanitize(array $rows) {
    if (isset($this->_options[self::ENTRY_SANITIZE]) and $this->_options[self::ENTRY_SANITIZE]) {
      foreach ($rows as $key=>$value) {
        unset($rows[$key][self::ENTRY_ID]);
        unset($rows[$key][self::ENTRY_REV]);
      }
    }
    return $rows;
  }
      
  /**
   * Fetch outstanding results from the server
   *
   * @throws AvocadoException
   * @return void
   */
  private function fetchOutstanding() {
    // continuation
    $response = $this->_connection->put(self::URL . "/" . $this->_id, "");
    $data = $response->getJson();

    $this->_hasMore = (bool) $data[self::ENTRY_HASMORE];
    $this->_result = array_merge($this->_result, $this->sanitize((array) $data[self::ENTRY_RESULT]));

    if (!$this->_hasMore) {
      // we have fetch the complete result set and can unset the id now 
      $this->_id = NULL;
    }

    $this->setLength();
  }

  /**
   * Set the length of the (fetched) result set
   *
   * @return void
   */
  private function setLength() {
    $this->_length = count($this->_result); 
  }
}
