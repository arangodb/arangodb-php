<?php

/**
 * AvocadoDB PHP client: single document 
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * Value object representing a single collection-based document
 *
 * @package AvocadoDbPhpClient
 */
class Document {
  /**
   * The document id (might be NULL for new documents)
   * @var mixed - document id
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
   * Document id index
   */
  const ENTRY_ID    = '_id';
  
  /**
   * Revision id index
   */
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
   * Factory method to constructs a new document using the values passed to populate it
   *
   * @throws ClientException
   * @param array $values - initial values for document
   * @return Document
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
   * Clone a document
   * Returns the clone 
   *
   * @return void
   */
  public function __clone() {
    $this->_id = NULL;
    $this->_rev = NULL;
    $this->_changed = false;
  }
  
  /**
   * Get a string representation of the document
   * Returns the document as JSON-encoded string
   *
   * @return string - JSON-encoded document 
   */
  public function __toString() {
    return $this->toJson();
  }
  
  /**
   * Returns the document as JSON-encoded string
   *
   * @return string - JSON-encoded document 
   */
  public function toJson() {
    return json_encode($this->_values);
  }
  
  /**
   * Returns the document as a serialized string
   *
   * @return string - PHP serialized document 
   */
  public function toSerialized() {
    return serialize($this->_values);
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
      $this->setId($value);
      return;
    }
    
    if ($key === self::ENTRY_REV) {
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
   * Set a document attribute, magic method
   *
   * This is a magic method that allows the object to be used without
   * declaring all document attributes first.
   * This function is mapped to set() internally.
   *
   * @throws ClientException
   * @param string $key - attribute name
   * @param mixed $value - value for attribute
   * @return void
   */
  public function __set($key, $value) {
    $this->set($key, $value);
  }

  /**
   * Get a document attribute
   *
   * @param string $key - name of attribute
   * @return mixed - value of attribute, NULL if attribute is not set
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
   * @param string $key - name of attribute
   * @return mixed - value of attribute, NULL if attribute is not set
   */
  public function __get($key) {
    return $this->get($key);
  }
  
  /**
   * Get all document attributes
   *
   * @return array - array of all document attributes/values
   */
  public function getAll() {
    return $this->_values;
  }
  
  /**
   * Set the changed flag
   *
   * @param bool $value - change flag
   * @return void
   */
  public function setChanged($value) {
    return $this->_changed = (bool) $value;
  }
  
  /**
   * Get the changed flag
   *
   * @return bool - true if document was changed, false otherwise
   */
  public function getChanged() {
    return $this->_changed;
  }
  
  /**
   * Set the document id 
   * This will throw if the id of an existing document gets updated to some other id
   *
   * @throws ClientException
   * @param mixed $id - document id
   * @return void
   */
  public function setId($id) {
    if ($this->_id !== NULL && $this->_id != $id) {
      throw new ClientException('Should not update the id of an existing document');
    }

    return $this->_id = $id;
  }

  /**
   * Get the document id (if already known)
   * Document ids are generated on the server only. Document ids are numeric but might be
   * bigger than PHP_INT_MAX. To reliably store a document id elsewhere, a PHP string should be used 
   *
   * @return mixed - document id, might be NULL if document does not yet have an id
   */
  public function getId() {
    return $this->_id; 
  }
  
  /**
   * Set the document revision
   * Revision ids are generated on the server only. Document ids are numeric but might be
   * bigger than PHP_INT_MAX. To reliably store a document id elsewhere, a PHP string should be used 
   *
   * @param mixed $rev - revision id
   * @return void
   */
  public function setRevision($rev) {
    $this->_rev = $rev;
  }
  
  /**
   * Get the document revision (if already known)
   *
   * @return mixed - revision id, might be NULL if document does not yet have an id
   */
  public function getRevision() {
    return $this->_rev;
  }

}
