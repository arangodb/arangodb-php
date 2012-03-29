<?php

/**
 * AvocadoDB PHP client: single collection
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * Value object representing a collection
 *
 * @package AvocadoDbPhpClient
 */
class Collection {
  /**
   * The collection id (might be NULL for new collections)
   * @var mixed - collection id
   */
  private $_id            = NULL;

  /**
   * The collection name (might be NULL for new collections)
   * @var string - collection name
   */
  private $_name          = NULL;
  
  /**
   * The collection waitForSync value (might be NULL for new collections)
   * @var bool - waitForSync value
   */
  private $_waitForSync   = NULL;
  
  /**
   * Collection id index
   */
  const ENTRY_ID          = 'id';
  
  /**
   * Collection name index
   */
  const ENTRY_NAME        = 'name';
  
  /**
   * Collection 'waitForSync' index
   */
  const ENTRY_WAIT_SYNC   = 'waitForSync';
  
  /**
   * Constructs an empty collection
   *
   * @return void
   */
  public function __construct() {
  }

  /**
   * Factory method to construct a new collection
   *
   * @throws ClientException
   * @param array $values - initial values for collection
   * @return Collection
   */
  public static function createFromArray(array $values) {
    $collection = new self();

    foreach ($values as $key => $value) {
      $collection->set($key, $value);
    }

    return $collection;
  }
  
  /**
   * Clone a collection
   * Returns the clone 
   *
   * @return void
   */
  public function __clone() {
    $this->_id          = NULL;
    $this->_name        = NULL;
    $this->_waitForSync = NULL;
  }
  
  /**
   * Get a string representation of the collection
   * Returns the collection as JSON-encoded string
   *
   * @return string - JSON-encoded collection
   */
  public function __toString() {
    return $this->toJson();
  }
  
  /**
   * Returns the collection as JSON-encoded string
   *
   * @return string - JSON-encoded collection
   */
  public function toJson() {
    return json_encode($this->getAll());
  }
  
  /**
   * Returns the collection as a serialized string
   *
   * @return string - PHP serialized collection
   */
  public function toSerialized() {
    return serialize($this->getAll());
  }
  
  /**
   * Get all collection attributes
   *
   * @return array - array of all collection attributes
   */
  public function getAll() {
    return array(
      self::ENTRY_ID        => $this->_id,
      self::ENTRY_NAME      => $this->_name,
      self::ENTRY_WAIT_SYNC => $this->_waitForSync,
    );
  }
  
  /**
   * Set a collection attribute
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
      throw new ClientException('Invalid collection attribute type');
    }

    if ($key === self::ENTRY_ID) {
      $this->setId($value);
      return;
    }
    
    if ($key === self::ENTRY_NAME) {
      $this->setName($value);
      return;
    }

    if ($key === self::ENTRY_WAIT_SYNC) {
      $this->setWaitForSync($value);
      return;
    }
   
    // unknown attribute, will be ignored 
  }
  
  /**
   * Set the collection id 
   * This will throw if the id of an existing collection gets updated to some other id
   *
   * @throws ClientException
   * @param mixed $id - collection id
   * @return void
   */
  public function setId($id) {
    if ($this->_id !== NULL && $this->_id != $id) {
      throw new ClientException('Should not update the id of an existing collection');
    }

    return $this->_id = $id;
  }
  
  /**
   * Get the collection id (if already known)
   * Collection ids are generated on the server only. Collection ids are numeric but might be
   * bigger than PHP_INT_MAX. To reliably store a collection id elsewhere, a PHP string should be used 
   *
   * @return mixed - collection id, might be NULL if collection does not yet have an id
   */
  public function getId() {
    return $this->_id; 
  }
  
  /**
   * Set the collection name
   *
   * @throws ClientException
   * @param string $name - name
   * @return void
   */
  public function setName($name) {
    assert(is_string($name));

    if ($this->_name !== NULL && $this->_name != $name) {
      throw new ClientException('Should not update the name of an existing collection');
    }

    $this->_name = $name;
  }
  
  /**
   * Get the collection name (if already known)
   *
   * @return string - name
   */
  public function getName() {
    return $this->_name; 
  }
  
  /**
   * Set the waitForSync value
   *
   * @param bool $value - waitForSync value
   * @return void
   */
  public function setWaitForSync($value) {
    assert(is_null($value) || is_bool($value));
    
    if ($this->_waitForSync !== NULL && $this->_waitForSync !== $value) {
      throw new ClientException('Should not update the waitForSync value of an existing collection');
    }

    $this->_waitForSync = $value; 
  }
  
  /**
   * Get the waitForSync value (if already known)
   *
   * @return bool - waitForSync value
   */
  public function getWaitForSync() {
    return $this->_waitForSync; 
  }
}
