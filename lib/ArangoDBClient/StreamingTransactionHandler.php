<?php

/**
 * ArangoDB PHP client: streaming transaction handler
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @copyright Copyright 2019, ArangoDB GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 * Provides management of streaming transactions
 *
 * @package   ArangoDBClient
 * @since     3.5
 */
class StreamingTransactionHandler extends Handler
{
    private $_pendingTransactions = [];

    /**
     * Construct a new streaming transaction handler
     *
     * @param Connection $connection - connection to be used
     *
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        register_shutdown_function(array($this, 'closePendingTransactions'));
    }

    /**
     * Creates a streaming transaction from scratch (no collections) or from an 
     * existing transaction object (necessary when collections need to be passed
     * into the transaction or when an existing transaction is resumed)
     *
     * @throws ServerException
     *
     * @param StreamingTransaction $trx - existing transaction
     */
    public function create(StreamingTransaction $trx = null) 
    {
        if ($trx === null) {
            $trx = new StreamingTransaction($this->getConnection());
        }
        
        $response = $this->getConnection()->post(
            Urls::URL_TRANSACTION . '/begin',
            $this->getConnection()->json_encode_wrapper($trx->attributes)
        );
        
        $jsonResponse = $response->getJson();
        $id           = $jsonResponse['result']['id'];
        $trx->setId($id);

        $this->_pendingTransactions[$id] = true;
        return $trx;
    }

    /**
     * Closes all pending transactions created by the handler
     */
    public function closePendingTransactions()
    {
        // automatically abort all unintentionally kept-open transactions, so we don't
        // leak server resources by not closing transactions 
        foreach ($this->_pendingTransactions as $id => $done) {
            try {
                $this->abort($id);
            } catch (\Exception $e) {
                // ignore all errors here
            }
        }
        $this->_pendingTransactions = [];
    }


    /**
     * Steal the transaction from the handler, so that it is not responsible anymore
     * for auto-aborting it on shutdown
     *
     * @param string $id - transaction id
     */
    public function stealTransaction($id)
    {
        unset($this->_pendingTransactions[$id]);
    }


    /**
     * Retrieves the status of a transaction
     *
     * @throws ServerException
     *
     * @param mixed $trx - streaming transaction object or transaction id as string
     *
     * @return array - returns an array with attributes 'id' and 'status'
     */
    public function getStatus($trx) 
    {
        if ($trx instanceof StreamingTransaction) {
            $id = $trx->getId();
        } else {
            $id = (string) $trx;
        }

        $response = $this->getConnection()->get(UrlHelper::buildUrl(Urls::URL_TRANSACTION, [$id]));
        $jsonResponse = $response->getJson();

        $status = $jsonResponse['result']['status'];
        if ($status === 'aborted' || $status === 'committed') {
            $this->stealTransaction($id);
        }

        return $jsonResponse['result'];
    }
    
    /**
     * Commits a transaction
     *
     * @throws ServerException
     *
     * @param mixed $trx - streaming transaction object or transaction id as string
     *
     * @return bool - true if commit succeeds, throws an exception otherwise
     */
    public function commit($trx) 
    {
        if ($trx instanceof StreamingTransaction) {
            $id = $trx->getId();
        } else {
            $id = (string) $trx;
        }

        $this->stealTransaction($id);
        $this->getConnection()->put(UrlHelper::buildUrl(Urls::URL_TRANSACTION, [$id]), '');

        return true;
    }

    /**
     * Aborts a transaction
     *
     * @throws ServerException
     *
     * @param mixed $trx - streaming transaction object or transaction id as string
     *
     * @return bool - true if abort succeeds, throws an exception otherwise
     */
    public function abort($trx) 
    {
        if ($trx instanceof StreamingTransaction) {
            $id = $trx->getId();
        } else {
            $id = (string) $trx;
        }

        $this->stealTransaction($id);
        $this->getConnection()->delete(UrlHelper::buildUrl(Urls::URL_TRANSACTION, [$id]));

        return true;
    }
    
    /**
     * Return all currently running transactions
     *
     * @throws ServerException
     *
     * @return array - array of currently running transactions, each transaction is an array with attributes 'id' and 'status'
     */
    public function getRunning() 
    {
        $response = $this->getConnection()->get(Urls::URL_TRANSACTION);
        
        $jsonResponse = $response->getJson();
        return $jsonResponse['transactions'];
    }
    
}

class_alias(CollectionHandler::class, '\triagens\ArangoDb\StreamingTransactionHandler');
