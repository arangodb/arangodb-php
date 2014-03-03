<?php
/**
 * ArangoDB PHP client testsuite
 * File: TransactionTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class TransactionTest
 *
 * Basic Tests for the Transaction API implementation
 *
 * @property Connection        $connection
 * @property CollectionHandler $collectionHandler
 * @property Collection        $collection1
 * @property Collection        $collection2
 * @package triagens\ArangoDb
 */
class TransactionTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);

        // clean up first
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_02');
        } catch (Exception $e) {
            //Silence the exception
        }


        $this->collection1 = new Collection();
        $this->collection1->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection1);

        $this->collection2 = new Collection();
        $this->collection2->setName('ArangoDB_PHP_TestSuite_TestCollection_02');
        $this->collectionHandler->add($this->collection2);
    }


    /**
     * Test if we can create and execute a transaction by using array initialization at construction time
     */
    public function testCreateAndExecuteTransactionWithArrayInitialization()
    {
        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ test : "hello" });
  }';
        $waitForSync      = true;
        $lockTimeout      = 10;

        $array       = array(
            'collections' => array('read' => $readCollections, 'write' => $writeCollections),
            'action'      => $action,
            'waitForSync' => $waitForSync,
            'lockTimeout' => $lockTimeout
        );
        $transaction = new Transaction($this->connection, $array);

        // check if object was initialized correctly with the array

        $this->assertTrue(
             $transaction->getWriteCollections() == $writeCollections,
             'Did not return writeCollections, instead returned: ' . print_r($transaction->getWriteCollections(), 1)
        );
        $this->assertTrue(
             $transaction->getReadCollections() == $readCollections,
             'Did not return readCollections, instead returned: ' . print_r($transaction->getReadCollections(), 1)
        );
        $this->assertTrue(
             $transaction->getAction() == $action,
             'Did not return action, instead returned: ' . $transaction->getAction()
        );
        $this->assertTrue(
             $transaction->getWaitForSync() == $waitForSync,
             'Did not return waitForSync, instead returned: ' . $transaction->getWaitForSync()
        );
        $this->assertTrue(
             $transaction->getLockTimeout() == $lockTimeout,
             'Did not return lockTimeout, instead returned: ' . $transaction->getLockTimeout()
        );


        $result = $transaction->execute();
        $this->assertTrue($result, 'Did not return true, instead returned: ' . $result);
    }


    /**
     * Test if we can create and execute a transaction by using magic getters/setters
     */
    public function testCreateAndExecuteTransactionWithMagicGettersSetters()
    {
        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ test : "hello" });
  }';
        $waitForSync      = true;
        $lockTimeout      = 10;

        // check if setters work fine
        $transaction                   = new Transaction($this->connection);
        $transaction->writeCollections = $writeCollections;
        $transaction->readCollections  = $readCollections;
        $transaction->action           = $action;
        $transaction->waitForSync      = true;
        $transaction->lockTimeout      = 10;

        // check if getters work fine

        $this->assertTrue(
             $transaction->writeCollections == $writeCollections,
             'Did not return writeCollections, instead returned: ' . print_r($transaction->writeCollections, 1)
        );
        $this->assertTrue(
             $transaction->readCollections == $readCollections,
             'Did not return readCollections, instead returned: ' . print_r($transaction->readCollections, 1)
        );
        $this->assertTrue(
             $transaction->action == $action,
             'Did not return action, instead returned: ' . $transaction->action
        );
        $this->assertTrue(
             $transaction->waitForSync == $waitForSync,
             'Did not return waitForSync, instead returned: ' . $transaction->waitForSync
        );
        $this->assertTrue(
             $transaction->lockTimeout == $lockTimeout,
             'Did not return lockTimeout, instead returned: ' . $transaction->lockTimeout
        );

        $result = $transaction->execute();
        $this->assertTrue($result, 'Did not return true, instead returned: ' . $result);
    }


    /**
     * Test if we can create and execute a transaction by using magic getters/setters and single collection-definitions as strings
     */
    public function testCreateAndExecuteTransactionWithMagicGettersSettersAndSingleCollectionDefinitionsAsStrings()
    {
        $writeCollections = $this->collection1->getName();
        $readCollections  = $this->collection2->getName();
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ test : "hello" });
  }';
        $waitForSync      = true;
        $lockTimeout      = 10;

        // check if setters work fine
        $transaction                   = new Transaction($this->connection);
        $transaction->writeCollections = $writeCollections;
        $transaction->readCollections  = $readCollections;
        $transaction->action           = $action;
        $transaction->waitForSync      = true;
        $transaction->lockTimeout      = 10;

        // check if getters work fine

        $this->assertTrue(
             $transaction->writeCollections == $writeCollections,
             'Did not return writeCollections, instead returned: ' . print_r($transaction->writeCollections, 1)
        );
        $this->assertTrue(
             $transaction->readCollections == $readCollections,
             'Did not return readCollections, instead returned: ' . print_r($transaction->readCollections, 1)
        );
        $this->assertTrue(
             $transaction->action == $action,
             'Did not return action, instead returned: ' . $transaction->action
        );
        $this->assertTrue(
             $transaction->waitForSync == $waitForSync,
             'Did not return waitForSync, instead returned: ' . $transaction->waitForSync
        );
        $this->assertTrue(
             $transaction->lockTimeout == $lockTimeout,
             'Did not return lockTimeout, instead returned: ' . $transaction->lockTimeout
        );

        $result = $transaction->execute();
        $this->assertTrue($result, 'Did not return true, instead returned: ' . $result);
    }


    /**
     * Test if we can create and execute a transaction by using getters/setters
     */
    public function testCreateAndExecuteTransactionWithGettersSetters()
    {
        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ test : "hello" });
  }';
        $waitForSync      = true;
        $lockTimeout      = 10;


        $transaction = new Transaction($this->connection);

        // check if setters work fine
        $transaction->setWriteCollections($writeCollections);
        $transaction->setReadCollections($readCollections);
        $transaction->setAction($action);
        $transaction->setWaitForSync($waitForSync);
        $transaction->setLockTimeout($lockTimeout);

        // check if getters work fine

        $this->assertTrue(
             $transaction->getWriteCollections() == $writeCollections,
             'Did not return writeCollections, instead returned: ' . print_r($transaction->getWriteCollections(), 1)
        );
        $this->assertTrue(
             $transaction->getReadCollections() == $readCollections,
             'Did not return readCollections, instead returned: ' . print_r($transaction->getReadCollections(), 1)
        );
        $this->assertTrue(
             $transaction->getAction() == $action,
             'Did not return action, instead returned: ' . $transaction->getAction()
        );
        $this->assertTrue(
             $transaction->getWaitForSync() == $waitForSync,
             'Did not return waitForSync, instead returned: ' . $transaction->getWaitForSync()
        );
        $this->assertTrue(
             $transaction->getLockTimeout() == $lockTimeout,
             'Did not return lockTimeout, instead returned: ' . $transaction->getLockTimeout()
        );


        $result = $transaction->execute();
        $this->assertTrue($result, 'Did not return true, instead returned: ' . $result);
    }


    /**
     * Test if we get the return-value from the code back.
     */
    public function testCreateAndExecuteTransactionWithReturnValue()
    {
        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ _key : "hello" });

    return "hello!!!";
  }';

        $transaction = new Transaction($this->connection);
        $transaction->setWriteCollections($writeCollections);
        $transaction->setReadCollections($readCollections);
        $transaction->setAction($action);

        $result = $transaction->execute();
        $this->assertTrue($result == 'hello!!!', 'Did not return hello!!!, instead returned: ' . $result);
    }


    /**
     * Test if we get an error back, if we throw an exception inside the transaction code
     */
    public function testCreateAndExecuteTransactionWithTransactionException()
    {
        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ test : "hello" });

    /* will abort and roll back the transaction */
    throw "doh!";
  }';

        $transaction = new Transaction($this->connection);
        $transaction->setWriteCollections($writeCollections);
        $transaction->setReadCollections($readCollections);
        $transaction->setAction($action);

        $e = null;
        try {
            $transaction->execute();
        } catch (ServerException $e) {
        }
        $details = $e->getDetails();

        $this->assertTrue(
             $e->getCode() == 500 && $details['errorMessage'] == 'doh!',
             'Did not return code 500 with message doh!, instead returned: ' . $e->getCode(
             ) . ' and ' . $details['errorMessage']
        );
    }


    /**
     * Test if we get an error back, if we violate a unique constraint
     */
    public function testCreateAndExecuteTransactionWithTransactionErrorUniqueConstraintOnSave()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ _key : "hello" });
    db.' . $this->collection1->getName() . '.save({ _key : "hello" });
  }';

        $transaction = new Transaction($this->connection);
        $transaction->setWriteCollections($writeCollections);
        $transaction->setReadCollections($readCollections);
        $transaction->setAction($action);

        $e = null;
        try {
            $transaction->execute();
        } catch (ServerException $e) {
        }
        $details                = $e->getDetails();
        $expectedCutDownMessage = "unique constraint violated";
        $len                    = strlen($expectedCutDownMessage);
        $this->assertTrue(
             $e->getCode() == 400 && substr(
                 $details['errorMessage'],
                 0,
                 $len
             ) == $expectedCutDownMessage,
             'Did not return code 400 with first part of the message: "' . $expectedCutDownMessage . '", instead returned: ' . $e->getCode(
             ) . ' and "' . $details['errorMessage'] . '"'
        );
    }


    public function tearDown()
    {
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_02');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection1);
        unset($this->collection2);
        unset($this->connection);
    }
}
