<?php

/**
 * ArangoDB PHP client: transaction base
 *
 * @package   ArangoDBClient
 * @author    Frank Mayer
 * @copyright Copyright 2013, triagens GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 * Transaction base class, used by Transaction and StreamingTransaction
 *
 * @package ArangoDBClient
 * @since   1.3
 */
class TransactionBase
{
    /**
     * The connection object
     *
     * @var Connection
     */
    private $_connection;

    /**
     * The transaction's attributes.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Collections index
     */
    const ENTRY_COLLECTIONS = 'collections';

    /**
     * WaitForSync index
     */
    const ENTRY_WAIT_FOR_SYNC = 'waitForSync';

    /**
     * Lock timeout index
     */
    const ENTRY_LOCK_TIMEOUT = 'lockTimeout';

    /**
     * Read index
     */
    const ENTRY_READ = 'read';

    /**
     * WRITE index
     */
    const ENTRY_WRITE = 'write';
    
    /**
     * EXCLUSIVE index
     */
    const ENTRY_EXCLUSIVE = 'exclusive';

    /**
     * Initialise the transaction object
     *
     * @param Connection $connection       - the connection to be used
     *
     * @throws \ArangoDBClient\ClientException
     */
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
        
        $this->attributes[self::ENTRY_COLLECTIONS] = [
            self::ENTRY_READ => [],
            self::ENTRY_WRITE => [],
            self::ENTRY_EXCLUSIVE => []
        ];
    }


    /**
     * Return the connection object
     *
     * @return Connection - the connection object
     */
    protected function getConnection()
    {
        return $this->_connection;
    }


    /**
     * Set the collections array.
     *
     * The array should have 2 sub-arrays, namely 'read' and 'write' which should hold the respective collections
     * for the transaction
     *
     * @param array $value
     */
    public function setCollections(array $value)
    {
        if (array_key_exists('read', $value)) {
            $this->setReadCollections($value['read']);
        }
        if (array_key_exists('write', $value)) {
            $this->setWriteCollections($value['write']);
        }
        if (array_key_exists('exclusive', $value)) {
            $this->setExclusiveCollections($value['exclusive']);
        }
    }


    /**
     * Get collections array
     *
     * This holds the read and write collections of the transaction
     *
     * @return array $value
     */
    public function getCollections()
    {
        return $this->get(self::ENTRY_COLLECTIONS);
    }


    /**
     * set waitForSync value
     *
     * @param bool $value
     *
     * @throws \ArangoDBClient\ClientException
     */
    public function setWaitForSync($value)
    {
        $this->set(self::ENTRY_WAIT_FOR_SYNC, (bool) $value);
    }


    /**
     * get waitForSync value
     *
     * @return bool waitForSync
     */
    public function getWaitForSync()
    {
        return $this->get(self::ENTRY_WAIT_FOR_SYNC);
    }


    /**
     * Set lockTimeout value
     *
     * @param int $value
     *
     * @throws \ArangoDBClient\ClientException
     */
    public function setLockTimeout($value)
    {
        $this->set(self::ENTRY_LOCK_TIMEOUT, (int) $value);
    }


    /**
     * Get lockTimeout value
     *
     * @return int lockTimeout
     */
    public function getLockTimeout()
    {
        return $this->get(self::ENTRY_LOCK_TIMEOUT);
    }


    /**
     * Convenience function to directly set read-collections without having to access
     * them from the collections attribute.
     *
     * @param array $value
     */
    public function setReadCollections($value)
    {
        $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_READ] = (array) $value;
    }


    /**
     * Convenience function to directly get read-collections without having to access
     * them from the collections attribute.
     *
     * @return array params
     */
    public function getReadCollections()
    {
        return $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_READ];
    }
    
    
    /**
     * Convenience function to directly set write-collections without having to access
     * them from the collections attribute.
     *
     * @param array $value
     */
    public function setWriteCollections($value)
    {
        $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_WRITE] = (array) $value;
    }


    /**
     * Convenience function to directly get write-collections without having to access
     * them from the collections attribute.
     *
     * @return array params
     */
    public function getWriteCollections()
    {
        return $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_WRITE];
    }

    
    /**
     * Convenience function to directly set exclusive-collections without having to access
     * them from the collections attribute.
     *
     * @param array $value
     */
    public function setExclusiveCollections($value)
    {
        $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_EXCLUSIVE] = (array) $value;
    }


    /**
     * Convenience function to directly get exclusive-collections without having to access
     * them from the collections attribute.
     *
     * @return array params
     */
    public function getExclusiveCollections()
    {
        return $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_EXCLUSIVE];
    }


    /**
     * Sets an attribute
     *
     * @param $key
     * @param $value
     *
     * @throws ClientException
     */
    public function set($key, $value)
    {
        if (!is_string($key)) {
            throw new ClientException('Invalid document attribute key');
        }

        $this->attributes[$key] = $value;
    }


    /**
     * Set an attribute, magic method
     *
     * This is a magic method that allows the object to be used without
     * declaring all document attributes first.
     *
     * @throws ClientException
     *
     * @magic
     *
     * @param string $key   - attribute name
     * @param mixed  $value - value for attribute
     *
     * @return void
     */
    public function __set($key, $value)
    {
        switch ($key) {
            case self::ENTRY_COLLECTIONS :
                $this->setCollections($value);
                break;
            case 'writeCollections' :
                $this->setWriteCollections($value);
                break;
            case 'readCollections' :
                $this->setReadCollections($value);
                break;
            case 'exclusiveCollections' :
                $this->setExclusiveCollections($value);
                break;
            case self::ENTRY_WAIT_FOR_SYNC :
                $this->setWaitForSync($value);
                break;
            case self::ENTRY_LOCK_TIMEOUT :
                $this->setLockTimeout($value);
                break;
            default:
                $this->set($key, $value);
                break;
        }
    }


    /**
     * Get an attribute
     *
     * @param string $key - name of attribute
     *
     * @return mixed - value of attribute, NULL if attribute is not set
     */
    public function get($key)
    {
        switch ($key) {
            case 'readCollections' :
                return $this->getReadCollections();
                break;
            case 'writeCollections' :
                return $this->getWriteCollections();
                break;
            case 'exclusiveCollections' :
                return $this->getExclusiveCollections();
                break;
        }

        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return null;
    }


    /**
     * Get an attribute, magic method
     *
     * This function is mapped to get() internally.
     *
     * @magic
     *
     * @param string $key - name of attribute
     *
     * @return mixed - value of attribute, NULL if attribute is not set
     */
    public function __get($key)
    {
        return $this->get($key);
    }


    /**
     * Is triggered by calling isset() or empty() on inaccessible properties.
     *
     * @param string $key - name of attribute
     *
     * @return boolean returns true or false (set or not set)
     */
    public function __isset($key)
    {
        if (isset($this->attributes[$key])) {
            return true;
        }

        return false;
    }


    /**
     * Build the object's attributes from a given array
     *
     * @param $options
     *
     * @throws \ArangoDBClient\ClientException
     */
    protected function buildTransactionAttributesFromArray($options)
    {
        if (isset($options[self::ENTRY_COLLECTIONS])) {
            $this->setCollections($options[self::ENTRY_COLLECTIONS]);
        }

        if (isset($options[self::ENTRY_WAIT_FOR_SYNC])) {
            $this->setWaitForSync($options[self::ENTRY_WAIT_FOR_SYNC]);
        }

        if (isset($options[self::ENTRY_LOCK_TIMEOUT])) {
            $this->setLockTimeout($options[self::ENTRY_LOCK_TIMEOUT]);
        }
    }
}

class_alias(TransactionBase::class, '\triagens\ArangoDb\TransactionBase');
