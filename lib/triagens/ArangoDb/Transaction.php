<?php

/**
 * ArangoDB PHP client: transaction
 *
 * @package   ArangoDbPhpClient
 * @author    Frank Mayer
 * @copyright Copyright 2013, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Transaction object
 * A transaction is an object that is used to prepare and send a transaction
 * to the server.
 *
 * The object encapsulates:
 *
 * * the collections definitions for locking
 * * the actual javascript function
 * * additional options like waitForSync, lockTimeout and params
 *
 *
 * The transaction object requires the connection object and can be initialized
 * with or without initial transaction configuration.
 * Any configuration can be set and retrieved by the object's methods like this:
 *
 * $this->setAction('function (){your code};');
 * $this->setCollections(array('read' => 'my_read_collection, 'write' => array('col_1', 'col2')));
 *
 * or like this:
 *
 * $this->action('function (){your code};');
 * $this->collections(array('read' => 'my_read_collection, 'write' => array('col_1', 'col2')));
 *
 *
 * There are also helper functions to set collections directly, based on their locking:
 *
 * $this->setWriteCollections($array or $string if single collection)
 * $this->setReadCollections($array or $string if single collection)
 *
 * @package ArangoDbPhpClient
 */
class Transaction
{
    /**
     * The connection object
     *
     * @var Connection
     */
    private $_connection = null;

    /**
     * The transaction's attributes.
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * Collections index
     */
    const ENTRY_COLLECTIONS = 'collections';

    /**
     * Action index
     */
    const ENTRY_ACTION = 'action';

    /**
     * WaitForSync index
     */
    const ENTRY_WAIT_FOR_SYNC = 'waitForSync';

    /**
     * Lock timeout index
     */
    const ENTRY_LOCK_TIMEOUT = 'lockTimeout';

    /**
     * Params index
     */
    const ENTRY_PARAMS = 'params';

    /**
     * Read index
     */
    const ENTRY_READ = 'read';

    /**
     * WRITE index
     */
    const ENTRY_WRITE = 'write';


    /**
     * Initialise the transaction object
     *
     * The $transaction array can be used to specify the collections, action and further
     * options for the transaction in form of an array.
     *
     * Example:
     * array(
     *   'collections' => array(
     *     'write' => array(
     *       'my_collection'
     *      )
     *    ),
     *   'action' => 'function (){}',
     *   'waitForSync' => true
     * )
     *
     *
     * @param Connection $connection             - the connection to be used
     * @param array      $transactionArray       - transaction initialization data
     *
     * @return \triagens\ArangoDb\Transaction
     */
    public function __construct(Connection $connection, array $transactionArray = null)
    {
        $this->_connection = $connection;
        if (is_array($transactionArray)) {
            $this->buildTransactionAttributesFromArray($transactionArray);
        }
    }


    /**
     * Execute the transaction
     *
     * This will post the query to the server and return the results as
     * a Cursor. The cursor can then be used to iterate the results.
     *
     * @throws Exception throw exception if transaction failed
     * @return mixed true if successful without a return value or the return value if one was set in the action
     */
    public function execute()
    {
        $response      = $this->_connection->post(
            Urls::URL_TRANSACTION,
            $this->getConnection()->json_encode_wrapper($this->attributes)
        );
        $responseArray = $response->getJson();
        if (isset($responseArray['result'])) {
            return $responseArray['result'];
        }

        return true;
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
    }


    /**
     * @return mixed $value
     */
    public function getCollections()
    {
        return $this->get(self::ENTRY_COLLECTIONS);
    }


    /**
     * @param string $value
     */
    public function setAction($value)
    {
        $this->set(self::ENTRY_ACTION, (string) $value);
    }


    /**
     * @return string action
     */
    public function getAction()
    {
        return $this->get(self::ENTRY_ACTION);
    }


    /**
     * @param bool $value
     */
    public function setWaitForSync($value)
    {
        $this->set(self::ENTRY_WAIT_FOR_SYNC, (bool) $value);
    }


    /**
     * @return bool waitForSync
     */
    public function getWaitForSync()
    {
        return $this->get(self::ENTRY_WAIT_FOR_SYNC);
    }


    /**
     * @param int $value
     */
    public function setLockTimeout($value)
    {
        $this->set(self::ENTRY_LOCK_TIMEOUT, (int) $value);
    }


    /**
     * @return int lockTimeout
     */
    public function getLockTimeout()
    {
        return $this->get(self::ENTRY_LOCK_TIMEOUT);
    }


    /**
     * @param array $value
     */
    public function setParams(array $value)
    {
        $this->set(self::ENTRY_PARAMS, (array) $value);
    }


    /**
     * @return array params
     */
    public function getParams()
    {
        return $this->get(self::ENTRY_PARAMS);
    }


    /**
     * @param array $value
     */
    public function setWriteCollections($value)
    {

        $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_WRITE] = $value;
    }


    /**
     * @return array params
     */
    public function getWriteCollections()
    {
        return $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_WRITE];
    }


    /**
     * @param array $value
     */
    public function setReadCollections($value)
    {

        $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_READ] = $value;
    }


    /**
     * @return array params
     */
    public function getReadCollections()
    {
        return $this->attributes[self::ENTRY_COLLECTIONS][self::ENTRY_READ];
    }


    public function set($key, $value)
    {
        if (!is_string($key)) {
            throw new ClientException('Invalid document attribute key');
        }

        $this->attributes[$key] = $value;
    }


    /**
     * Set a document attribute, magic method
     *
     * This is a magic method that allows the object to be used without
     * declaring all document attributes first.
     *
     * @throws ClientException
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
            case self::ENTRY_ACTION :
                $this->setAction($value);
                break;
            case self::ENTRY_WAIT_FOR_SYNC :
                $this->setWaitForSync($value);
                break;
            case self::ENTRY_LOCK_TIMEOUT :
                $this->setLockTimeout($value);
                break;
            case self::ENTRY_PARAMS :
                $this->setParams($value);
                break;
            default:
                $this->set($key, $value);
                break;
        }
    }

    /**
     * Get a document attribute
     *
     * @param string $key - name of attribute
     *
     * @return mixed - value of attribute, NULL if attribute is not set
     */
    public function get($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return null;
    }

    /**
     * Get a document attribute, magic method
     *
     * This function is mapped to get() internally.
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
     * Returns the action string
     *
     * @return string - the current action string
     */
    public function __toString()
    {
        return $this->_action;
    }

    /**
     * @param $options
     */
    public function buildTransactionAttributesFromArray($options)
    {
        if (isset($options[self::ENTRY_COLLECTIONS])) {
            $this->setCollections($options[self::ENTRY_COLLECTIONS]);
        }

        if (isset($options[self::ENTRY_ACTION])) {
            $this->setAction($options[self::ENTRY_ACTION]);
        }

        if (isset($options[self::ENTRY_WAIT_FOR_SYNC])) {
            $this->setWaitForSync($options[self::ENTRY_WAIT_FOR_SYNC]);
        }

        if (isset($options[self::ENTRY_LOCK_TIMEOUT])) {
            $this->setLockTimeout($options[self::ENTRY_LOCK_TIMEOUT]);
        }

        if (isset($options[self::ENTRY_PARAMS])) {
            $this->setParams($options[self::ENTRY_PARAMS]);
        }
    }
}
