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
class Document {
  /**
   * The document id (might be NULL for new documents)
   * @var string - document id
   */
  protected $_id      = NULL;
  
  /**
   * The document revision (might be NULL for new documents)
   * @var mixed
   */
  protected $_rev     = NULL;
  
  /**
   * The document attributes (names/values)
   * @var array
   */
  protected $_values  = array();

  /**
   * Flag to indicate whether document was changed locally
   * @var bool
   */
  protected $_changed;

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
   * Factory method to construct a new document using the values passed to populate it
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

    $document->setChanged(true);

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

    // do not change the _changed flag here
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
    return json_encode($this->getAll());
  }
  
  /**
   * Returns the document as a serialized string
   *
   * @return string - PHP serialized document 
   */
  public function toSerialized() {
    return serialize($this->getAll());
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
   * Set the internal document id 
   * This will throw if the id of an existing document gets updated to some other id
   *
   * @throws ClientException
   * @param string $id - internal document id
   * @return void
   */
  public function setInternalId($id) {
    if ($this->_id !== NULL && $this->_id != $id) {
      throw new ClientException('Should not update the id of an existing document');
    }

    if (!preg_match('/^\d+\/\d+$/', $id)) {
      throw new ClientException('Invalid format for document id');
    }

    $this->_id = $id;
  }

  /**
   * Get the internal document id (if already known)
   * Document ids are generated on the server only. Document ids consist of collection id and
   * document id, in the format collectionid/documentid
   *
   * @return string - internal document id, might be NULL if document does not yet have an id
   */
  public function getInternalId() {
    return $this->_id; 
  }
  
  /**
   * Convenience function to get the document handle (if already known) - is an alias to getInternalId()
   * Document handles are generated on the server only. Document handles consist of collection id and
   * document id, in the format collectionid/documentid
   *
   * @return string - internal document id, might be NULL if document does not yet have an id
   */
  public function getHandle() {
    return $this->getInternalId(); 
  }
  
  /**
   * Get the document id (if already known)
   * Document ids are generated on the server only. Document ids are numeric but might be
   * bigger than PHP_INT_MAX. To reliably store a document id elsewhere, a PHP string should be used 
   *
   * @return mixed - document id, might be NULL if document does not yet have an id
   */
  public function getId() {
    @list(, $documentId) = explode('/', $this->_id, 2);

    return $documentId;
  }
  
  /**
   * Get the collection id (if already known)
   * Collection ids are generated on the server only. Collection ids are numeric but might be
   * bigger than PHP_INT_MAX. To reliably store a collection id elsewhere, a PHP string should be used 
   *
   * @return mixed - collection id, might be NULL if document does not yet have an id
   */
  public function getCollectionId() {
    @list($collectionId) = explode('/', $this->_id, 2);

    return $collectionId;
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
