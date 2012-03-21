<?php

/**
 * AvocadoDB PHP client: single document 
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoDocument
 * 
 * Value object representing a single collection-based document
 *
 * @package AvocadoDbPhpClient
 */
class AvocadoDocument {
  /**
   * The document id (might be NULL for new documents)
   * @var mixed
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

  const ENTRY_ID    = '_id';
  const ENTRY_REV   = '_rev';

  /**
   * Constructs an empty document
   *
   * @return void
   */
  public function __construct() {
    $this->setChanged(false);
  }

  /**
   * Constructs a new document using the values passed to populate it
   *
   * @throws AvocadoException
   * @param array $values
   * @return AvocadoDocument
   */
  public static function createFromArray(array $values) {
    $document = new self();
    
    foreach ($values as $key => $value) {
      $document->set($key, $value);
    }

    $document->setChanged(false);

    return $document;
  }
  
  /**
   * Get a string representation of the document
   *
   * @return string
   */

  public function __toString() {
    return json_encode($this->_values);
  }

  /**
   * Set a document attribute
   *
   * The key (attribute name) must be a string.
   * This will validate the value of the attribute and might throw an
   * exception if the value is invalid.
   *
   * @throws AvocadoException
   * @param string $key
   * @param mixed $value
   * @return void
   */
  public function set($key, $value) {
    if (!is_string($key)) {
      throw new AvocadoClientException('Invalid document attribute key');
    }

    // validate the value passed
    AvocadoValueValidator::validate($value);

    if ($key === self::ENTRY_ID) {
      $this->setId($value);
    }
    else if ($key === self::ENTRY_REV) {
      $this->setRevision($value);
    }
    else {
      if (!$this->_changed) {
        if (!isset($this->_values[$key]) || $this->_values[$key] !== $value) {
          // set changed flag
          $this->_changed = true;
        }
      }

      // and store the value
      $this->_values[$key] = $value;
    }
  }
  
  /**
   * Set a document attribute, magic method
   *
   * This is a magic method that allows the object to be used without
   * declaring all document attributes first.
   * This function is mapped to set() internally.
   *
   * @throws AvocadoException
   * @param string $key
   * @param mixed $value
   * @return void
   */
  public function __set($key, $value) {
    $this->set($key, $value);
  }

  /**
   * Get a document attribute
   *
   * @param string $key
   * @return mixed
   */
  public function get($key) {
    if (isset($this->_values[$key])) {
      return $this->_values[$key];
    }

    return NULL;
  }

  /**
   * Get a document attribute, magic method
   * This function is mapped to get() internally.
   *
   * @param string $key
   * @return mixed
   */
  public function __get($key) {
    return $this->get($key);
  }
  
  /**
   * Get all document attributes
   *
   * @return array
   */
  public function getAll() {
    return $this->_values;
  }
  
  /**
   * Set the changed flag
   *
   * @param bool $value
   * @return void
   */
  public function setChanged($value) {
    return $this->_changed = (bool) $value;
  }
  
  /**
   * Get the changed flag
   *
   * @return bool
   */
  public function getChanged() {
    return $this->_changed;
  }
  
  /**
   * Set the document id 
   *
   * @param mixed $id
   * @return void
   */
  public function setId($id) {
    return $this->_id = $id;
  }

  /**
   * Get the document id (if already known)
   *
   * @return mixed
   */
  public function getId() {
    return $this->_id; 
  }
  
  /**
   * Set the document revision
   *
   * @param mixed $rev
   * @return void
   */
  public function setRevision($rev) {
    $this->_rev = $rev;
  }
  
  /**
   * Get the document revision (if already known)
   *
   * @return mixed
   */
  public function getRevision() {
    return $this->_rev;
  }

}
