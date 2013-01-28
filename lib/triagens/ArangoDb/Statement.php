<?php

/**
 * ArangoDB PHP client: statement
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Container for a read-only ("select") statement
 * A statement is an AQL query that can be issued to the
 * server. Optional bind parameters can be used when issuing the
 * statement to separate the statement from the values.
 * Executing a statement will result in a cursor being created.
 *
 * @package ArangoDbPhpClient
 */
class Statement {
  /**
   * The connection object
   * 
   * @var Connection
   */
  private $_connection  = NULL;
  
  /**
   * The bind variables and values used for the statement
   * 
   * @var BindVars
   */
  private $_bindVars;
  
  /**
   * The current batch size (number of result documents retrieved per roundtrip)
   * 
   * @var mixed
   */
  private $_batchSize   = NULL;
  
  /**
   * The count flag (should server return total number of results)
   * 
   * @var bool
   */
  private $_doCount     = false;
  
  /**
   * The query string
   * 
   * @var string
   */
  private $_query       = NULL;
  
  /**
   * Sanitation flag (if set, the _id and _rev attributes will be removed from the results)
   * 
   * @var bool
   */
  private $_sanitize    = false;
  
  /**
   * Query string index
   */
  const ENTRY_QUERY     = 'query';

  /**
   * Count option index
   */
  const ENTRY_COUNT     = 'count';
  
  /**
   * Batch size index
   */
  const ENTRY_BATCHSIZE = 'batchSize';
  
  /**
   * Bind variables index
   */
  const ENTRY_BINDVARS  = 'bindVars';
   
  /**
   * Initialise the statement
   *
   * @throws Exception
   * @param Connection $connection - the connection to be used
   * @param array $data - statement initialization data
   * @return void
   */
  public function __construct(Connection $connection, array $data) {
    $this->_connection = $connection;
    $this->_bindVars   = new BindVars();

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

    if (isset($data[Cursor::ENTRY_SANITIZE])) {
      $this->_sanitize = (bool) $data[Cursor::ENTRY_SANITIZE];
    }
  }
  
   /**
   * Return the connection object
   *
   * @return Connection - the connection object
   */
  protected function getConnection() {
    return $this->_connection;
  }
  
  /**
   * Execute the statement
   * 
   * This will post the query to the server and return the results as
   * a Cursor. The cursor can then be used to iterate the results.
   *
   * @throws Exception
   * @return Cursor
   */
  public function execute() {
    $data = $this->buildData();
    $response = $this->_connection->post(Urls::URL_CURSOR, $this->getConnection()->json_encode_wrapper($data));

    return new Cursor($this->_connection, $response->getJson(), $this->getCursorOptions());
  }
  
  
  /**
   * Explain the statement's execution plan
   * 
   * This will post the query to the server and return the execution plan as an array.
   *
   * @throws Exception
   * @return Array
   */
  public function explain() {
    $data = $this->buildData();
    $response = $this->_connection->post(Urls::URL_EXPLAIN, $this->getConnection()->json_encode_wrapper($data));
    
    return $response->getJson();
  }

  
  /**
   * Validates the statement
   * 
   * This will post the query to the server for validation and return the validation result as an array.
   *
   * @throws Exception
   * @return Array
   */
  public function validate() {
    $data = $this->buildData();
    $response = $this->_connection->post(Urls::URL_QUERY, $this->getConnection()->json_encode_wrapper($data));

    return $response->getJson();
  }

  
  /**
   * Invoke the statement
   * 
   * This will simply call execute(). Arguments are ignored.
   *
   * @throws Exception 
   * @param mixed $args - arguments for invocation, will be ignored
   * @return Cursor - the result cursor
   */
  public function __invoke($args) {
    return $this->execute();
  }

  /**
   * Return a string representation of the statement
   *
   * @return string - the current query string
   */
  public function __toString() {
    return $this->_query;
  }
  
  /**
   * Bind a parameter to the statement
   * 
   * This method can either be called with a string $key and a
   * separate value in $value, or with an array of all bind
   * bind parameters in $key, with $value being NULL.
   * 
   * Allowed value types for bind parameters are string, int,
   * double, bool and array. Arrays must not contain any other
   * than these types.
   *
   * @throws Exception
   * @param mixed $key - name of bind variable OR an array of all bind variables
   * @param mixed $value - value for bind variable
   * @return void
   */
  public function bind($key, $value = NULL) {
    $this->_bindVars->set($key, $value);
  }
  
  /**
   * Get all bind parameters as an array
   *
   * @return array - array of bind variables/values
   */
  public function getBindVars() {
    return $this->_bindVars->getAll();
  }
  
  /**
   * Set the query string
   *
   * @throws ClientException
   * @param string $query - query string
   * @return void
   */
  public function setQuery($query) {
    if (!is_string($query)) {
      throw new ClientException('Query should be a string');
    }

    $this->_query = $query;
  }

  /**
   * Get the query string
   *
   * @return string - current query string value
   */
  public function getQuery() {
    return $this->_query;
  }

  /**
   * Set the count option for the statement
   *
   * @param bool $value - value for count option
   * @return void
   */
  public function setCount($value) {
    $this->_doCount = (bool) $value;
  }
  
  /**
   * Get the count option value of the statement
   *
   * @return bool - current value of count option
   */
  public function getCount() {
    return $this->_doCount;
  }

  /**
   * Set the batch size for the statement 
   * 
   * The batch size is the number of results to be transferred
   * in one server roundtrip. If a query produces more results
   * than the batch size, it creates a server-side cursor that
   * provides the additional results. 
   * 
   * The server-side cursor can be accessed by the client with subsequent HTTP requests.
   *
   * @throws ClientException
   * @param int $value - batch size value
   * @return void
   */
  public function setBatchSize($value) {
    if (!is_int($value) || (int) $value <= 0) {
      throw new ClientException('Batch size should be a positive integer');
    }

    $this->_batchSize = (int) $value;
  }

  /**
   * Get the batch size for the statement
   *
   * @return int - current batch size value
   */
  public function getBatchSize() {
    return $this->_batchSize;
  }
  
  /**
   * Build an array of data to be posted to the server when issuing the statement
   *
   * @return array - array of data to be sent to server
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
   * @return array - array of options
   */
  private function getCursorOptions() {
    return array(
      Cursor::ENTRY_SANITIZE => (bool) $this->_sanitize,
    );
  }

}
