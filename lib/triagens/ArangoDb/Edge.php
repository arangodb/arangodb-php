<?php

/**
 * ArangoDB PHP client: single document 
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Value object representing a single collection-based document
 *
 * @package ArangoDbPhpClient
 */
class Edge extends Document {
  /**
   * The document id (might be NULL for new documents)
   * @var string - document id
   */
  private $_id      = NULL;
  
  /**
   * The document revision (might be NULL for new documents)
   * @var mixed
   */
  private $_rev     = NULL;
  
  /**
   * The document attributes (names/values)
   * @var array
   */
  private $_values  = array();

  /**
   * Flag to indicate whether document was changed locally
   * @var bool
   */
  private $_changed;

  /**
   * Document _id index
   */
  const ENTRY_ID    = '_id';
  
  /**
   * Revision _rev index
   */
  const ENTRY_REV   = '_rev';
  
  /**
   * Document _from index
   */
   
  const ENTRY_FROM    = '_from';
  
  /**
   * Revision _to index
   */
  const ENTRY_TO   = '_to';

  
  /**
   * Clone a document
   * Returns the clone 
   *
   * @return void
   */
  public function __clone() {
    $this->_id = NULL;
    $this->_rev = NULL;
    $this->_to = NULL;
    $this->_from = NULL;

    // do not change the _changed flag here
  }
  

  /**
   * Set a document attribute
   *
   * The key (attribute name) must be a string.
   * This will validate the value of the attribute and might throw an
   * exception if the value is invalid.
   *
   * @throws ClientException
   * @param string $key - attribute name
   * @param mixed $value - value for attribute
   * @return void
   */
  public function set($key, $value) {
    if (!is_string($key)) {
      throw new ClientException('Invalid document attribute key');
    }

    // validate the value passed
    ValueValidator::validate($value);

    if ($key === self::ENTRY_ID) {
      $this->setInternalId($value);
      return;
    }
    
    if ($key === self::ENTRY_REV) {
      $this->setRevision($value);
      return;
    }

    if ($key === self::ENTRY_FROM) {
      $this->setInternalId($value);
      return;
    }
    
    if ($key === self::ENTRY_TO) {
      $this->setRevision($value);
      return;
    }

    if (!$this->_changed) {
      if (!isset($this->_values[$key]) || $this->_values[$key] !== $value) {
        // set changed flag
        $this->_changed = true;
      }
    }

    // and store the value
    $this->_values[$key] = $value;
  }
  
  
  /**
   * Get the document revision (if already known)
   *
   * @return mixed - revision id, might be NULL if document does not yet have an id
   */
  public function getFrom() {
    return $this->_from;
  }
  /**
   * Get the document revision (if already known)
   *
   * @return mixed - revision id, might be NULL if document does not yet have an id
   */
  public function getTo() {
    return $this->_to;
  }

}
