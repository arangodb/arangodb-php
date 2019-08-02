<?php

/**
 * ArangoDB PHP client: streaming transaction collection
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @copyright Copyright 2019, ArangoDB GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 * Streaming transaction collection object
 *
 * @package ArangoDBClient
 * @since   3.5
 */
class StreamingTransactionCollection extends Collection 
{
    /**
     * The transaction - assigned on construction
     *
     * @var StreamingTransaction - transaction
     */
    private $_trx;

    /**
     * Collection name - assigned on construction
     *
     * @var string - collection name
     */
    private $_name;

    /**
     * Lock mode for this collection, i.e. 'read', 'write' or 'exclusive'
     *
     * @var string - lock mode
     */
    private $_mode;


    /**
     * Constructs a streaming transaction collection object
     *
     * @param StreamingTransaction $trx  - the transaction
     * @param string $name               - collection name
     * @param string $mode               - lock mode, i.e. 'read', 'write', 'exclusive'
     */
    public function __construct(StreamingTransaction $trx, $name, $mode)
    {
      parent::__construct($name);

      $this->_trx        = $trx;
      $this->_name       = $name;
      $this->_mode       = $mode;
    }
    
    /**
     * Return the name of the collection
     *
     * @magic
     *
     * @return string - collection name
     */
    public function __toString()
    {
        return $this->_name;
    }
    
    /**
     * Return the name of the collection
     *
     * @return string - collection name
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Return the lock mode of the collection
     *
     * @return string - lock mode, i.e. 'read', 'write', 'exclusive'
     */
    public function getMode()
    {
        return $this->_mode;
    }

    /**
     * Return the transaction's id
     *
     * @return string - transaction id
     */
    public function getTrxId()
    {
       return $this->_trx->getId();
    } 
}

class_alias(Transaction::class, '\triagens\ArangoDb\StreamingTransactionCollection');
