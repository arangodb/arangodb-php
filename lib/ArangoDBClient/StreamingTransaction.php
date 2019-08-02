<?php

/**
 * ArangoDB PHP client: streaming transaction
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @copyright Copyright 2013, triagens GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 * Streaming transaction object
 *
 * @package ArangoDBClient
 * @since   3.5
 */
class StreamingTransaction extends TransactionBase
{
    /**
     * class constant for id values
     */
    const ENTRY_ID = 'id';

    /**
     * The transaction id - assigned by the server
     *
     * @var string - transaction id
     */
    private $_id;

    /**
     * An array of collections used by this transaction
     *
     * @var array - collection objects used by the transaction
     */
    private $_collections;

    /**
     * Constructs a streaming transaction object
     *
     * @param Connection $connection   - client connection
     * @param array $transactionArray  - array with collections used by the transaction
     */
    public function __construct(Connection $connection, array $transactionArray = null)
    {
        parent::__construct($connection);

        if (is_array($transactionArray)) {
            $this->buildTransactionAttributesFromArray($transactionArray);
        }

        // set up participating collections
        $this->_collections = [];
        foreach ($this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_EXCLUSIVE] as $name) {
            $this->_collections[$name] = new StreamingTransactionCollection($this, $name, self::ENTRY_EXCLUSIVE);
        }

        foreach ($this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_WRITE] as $name) {
            if (!isset($this->_collections[$name])) {
                $this->_collections[$name] = new StreamingTransactionCollection($this, $name, self::ENTRY_WRITE);
            }
        }

        foreach ($this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_READ] as $name) {
            if (!isset($this->_collections[$name])) {
                $this->_collections[$name] = new StreamingTransactionCollection($this, $name, self::ENTRY_READ);
            }
        }
    }


    /**
     * Get a participating collection of the transaction by name
     * Will throw an exception if the collection is not part of the transaction
     *
     * @throws ClientException
     *
     * @param string $name - name of the collection
     *
     * @return StreamingTransactionCollection - collection object
     */
    public function getCollection($name)
    {
        if (!isset($this->_collections[$name])) {
          throw new ClientException('Collection not registered in transaction');
        }
        return $this->_collections[$name];
    }
    
    /**
     * Get the transaction's id
     *
     * @return string - transaction id
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set the transaction's id - this is used internally and should not be called by end users
     *
     * @param mixed $id - transaction id as number or string
     */
    public function setId($id)
    {
        assert(is_string($id) || is_numeric($id));

        if ($this->_id !== null) {
            throw new ClientException('Should not update the id of an existing transaction');
        }

        if (is_numeric($id)) {
          $id = (string) $id;
        } 
        $this->_id = $id;
    }
    
    
    /**
     * Executes an AQL query inside the transaction
     *
     * This is a shortcut for creating a new Statement and executing it.
     *
     * @throws ClientException
     *
     * @param array $data - query data, as is required by Statement::__construct()
     *
     * @return Cursor - query cursor, as returned by Statement::execute()
     */
    public function query(array $data)
    {
        $data['transaction'] = $this;
        $statement = new Statement($this->getConnection(), $data);
        return $statement->execute();
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
        parent::buildTransactionAttributesFromArray($options);

        if (isset($options[self::ENTRY_ID])) {
            $this->setId($options[self::ENTRY_ID]);
        }
    }

}

class_alias(Transaction::class, '\triagens\ArangoDb\StreamingTransaction');
