<?php

/**
 * AvocadoDB PHP client: statement
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoStatement
 * 
 * Container for a select statement
 * A statement is an SQL-like query that can be issues to the
 * server. Optional bind parameters can be used when issueing the
 * statement to separate the statement from the values.
 * Executing a statement will result in a cursor being created.
 *
 * @package AvocadoDbPhpClient
 */
class AvocadoStatement {
  private $_connection  = NULL;
  private $_batchSize   = NULL;
  private $_doCount     = false;
  private $_query       = NULL;
  private $_sanitize    = false;
  private $_bindVars;
 
  const ENTRY_QUERY     = 'query';
  const ENTRY_COUNT     = 'count';
  const ENTRY_BATCHSIZE = 'batchSize';
  const ENTRY_BINDVARS  = 'bindVars';
   
  /**
   * Initialise the statement
   *
   * @throws AvocadoException
   * @param AvocadoConnection $connection
   * @param array $data
   * @return void
   */
  public function __construct(AvocadoConnection $connection, array $data) {
    $this->_connection = $connection;
    $this->_bindVars   = new AvocadoBindVars();

    $this->setQuery(@$data[self::ENTRY_QUERY]);
    
    if (isset($data[self::ENTRY_COUNT])) {
      $this->setCount($data[self::ENTRY_COUNT]);
    }

    if (isset($data[self::ENTRY_BATCHSIZE])) {
      $this->setBatchSize($data[self::ENTRY_BATCHSIZE]);
    }

    if (isset($data[self::ENTRY_BINDVARS])) {
      $this->_bindVars->set($data[self::ENTRY_BINDVARS]);
    }

    if (isset($data[AvocadoCursor::ENTRY_SANITIZE])) {
      $this->_sanitize = (bool) $data[AvocadoCursor::ENTRY_SANITIZE];
    }
  }
  
  /**
   * Execute the statement
   * This will post the query to the server and return the results as
   * an AvocadoCursor. The cursor can then be used to iterate the results.
   *
   * @throws AvocadoException
   * @return AvocadoCursor
   */
  public function execute() {
    $data = $this->buildData();
    $response = $this->_connection->post(AvocadoCursor::URL, json_encode($data));

    return new AvocadoCursor($this->_connection, $response->getJson(), $this->getCursorOptions());
  }

  /**
   * Invoke the statement
   * This will simply call execute(). Arguments are ignored.
   *
   * @throws AvocadoException
   * @param mixed $args
   * @return AvocadoCursor
   */
  public function __invoke($args) {
    return $this->execute();
  }
  
  /**
   * Bind a parameter to the statement
   * This method can either be called with a string $key and a
   * separate value in $value, or with an array of all bind
   * bind parameters in $key, with $value being NULL.
   * Allowed value types for bind parameters are string, int,
   * double, bool and array. Arrays must not contain any other
   * than these types.
   *
   * @throws AvocadoException
   * @param mixed $key
   * @param mixed $value
   * @return void
   */
  public function bind($key, $value = NULL) {
    $this->_bindVars->set($key, $value);
  }
  
  /**
   * Get all bind parameters as an array
   *
   * @return array
   */
  public function getBindVars() {
    return $this->_bindVars->getAll();
  }
  
  /**
   * Set the query string
   *
   * @throws AvocadoException
   * @param string $query
   * @return void
   */
  public function setQuery($query) {
    if (!is_string($query)) {
      throw new AvocadoClientException('Query should be a string');
    }

    $this->_query = $query;
  }

  /**
   * Get the query string
   *
   * @return string
   */
  public function getQuery() {
    return $this->_query;
  }

  /**
   * Set the count option for the statement
   *
   * @param bool $value
   * @return void
   */
  public function setCount($value) {
    $this->_doCount = (bool) $value;
  }
  
  /**
   * Get the count option value of the statement
   *
   * @return bool 
   */
  public function getCount() {
    return $this->_doCount;
  }

  /**
   * Set the batch size for the statement 
   * The batch size is the number of results to be transferred
   * in one server roundtrip. If a query produces more results
   * than the batch size, it creates a server-side cursor that
   * provides the additional results. The server-side cursor can
   * be accessed by the client with subsequent HTTP requests.
   *
   * @throws AvocadoException
   * @param int $value
   * @return void
   */
  public function setBatchSize($value) {
    if (!is_int($value) || (int) $value <= 0) {
      throw new AvocadoClientException('Batch size should be a positive integer');
    }

    $this->_batchSize = (int) $value;
  }

  /**
   * Get the batch size for the statement
   *
   * @return int
   */
  public function getBatchSize() {
    return $this->_batchSize;
  }
  
  /**
   * Build an array of data to be posted to the server when
   * issueing the statement
   *
   * @return array
   */
  private function buildData() {
    $data = array(
      self::ENTRY_QUERY => $this->_query,
      self::ENTRY_COUNT => $this->_doCount,
    );

    if ($this->_bindVars->getCount() > 0) {
      $data[self::ENTRY_BINDVARS] = $this->_bindVars->getAll();
    }

    if ($this->_batchSize > 0) {
      $data[self::ENTRY_BATCHSIZE] = $this->_batchSize;
    }

    return $data;
  }

  /**
   * Return an array of cursor options
   *
   * @return array
   */
  private function getCursorOptions() {
    return array(
      AvocadoCursor::ENTRY_SANITIZE => (bool) $this->_sanitize,
    );
  }

}
