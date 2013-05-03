<?php
/**
 * ArangoDB PHP client testsuite
 * File: GraphBasicTest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class GraphBasicTest
 * Basic Tests for the Graph API implementation
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
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);

        // clean up first
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }


        $this->collection1 = new \triagens\ArangoDb\Collection();
        $this->collection1->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection1);

        $this->collection2 = new \triagens\ArangoDb\Collection();
        $this->collection2->setName('ArangoDB_PHP_TestSuite_TestCollection_02');
        $this->collectionHandler->add($this->collection2);
    }


    /**
     * Test if Edge and EdgeHandler instances can be initialized
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

        $array       = array(
            'collections' => array('read' => $readCollections, 'write' => $writeCollections),
            'action'      => $action,
            'waitForSync' => true,
            'lockTimeout' => 5
        );
        $transaction = new \triagens\ArangoDb\Transaction($this->connection, $array);


        $result = $transaction->execute();
        $this->assertTrue($result, 'Did not return true, instead returned: ' . $result);
    }


    /**
     * Test if Edge and EdgeHandler instances can be initialized
     */
    public function testCreateAndExecuteTransactionWithoutArrayInitialization()
    {
        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ test : "hello" });
  }';

        $transaction = new \triagens\ArangoDb\Transaction($this->connection);
        $transaction->setWriteCollections($writeCollections);
        $transaction->setReadCollections($readCollections);
        $transaction->setAction($action);
        $transaction->setWaitForSync(true);
        $transaction->setLockTimeout(10);

        $result = $transaction->execute();
        $this->assertTrue($result, 'Did not return true, instead returned: ' . $result);
    }


    /**
     * Test if Edge and EdgeHandler instances can be initialized
     */
    public function testCreateAndExecuteTransactionWithoutArrayInitializationWithReturnvalue()
    {
        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ _key : "hello" });

    return "hello!!!";
  }';

        $transaction = new \triagens\ArangoDb\Transaction($this->connection);
        $transaction->setWriteCollections($writeCollections);
        $transaction->setReadCollections($readCollections);
        $transaction->setAction($action);

        $result = $transaction->execute();
        $this->assertTrue($result == 'hello!!!', 'Did not return hello!!!, instead returned: ' . $result);
    }

    /**
     * Test if Edge and EdgeHandler instances can be initialized
     *
     * @expectedException triagens\ArangoDb\ServerException
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

        $transaction = new \triagens\ArangoDb\Transaction($this->connection);
        $transaction->setWriteCollections($writeCollections);
        $transaction->setReadCollections($readCollections);
        $transaction->setAction($action);

        $e = null;
        try {
            $result = $transaction->execute();
        } catch (triagens\ArangoDb\ServerException $e) {
        }
        $details = $e->getDetails();

        $this->assertTrue(
            $e->getCode() == 500 && $details['errorMessage'] == 'doh!',
            'Did not return code 500 with message doh!, instead returned: ' . $e->getCode(
            ) . ' and ' . $details['errorMessage']
        );
    }

    /**
     * Test if Edge and EdgeHandler instances can be initialized
     *
     * @expectedException triagens\ArangoDb\ServerException
     */
    public function testCreateAndExecuteTransactionWithTransactionErrorOnSave()
    {
        $writeCollections = array($this->collection1->getName());
        $readCollections  = array($this->collection2->getName());
        $action           = '
  function () {
    var db = require("internal").db;
    db.' . $this->collection1->getName() . '.save({ _key : "hello" });
    db.' . $this->collection1->getName() . '.save({ _key : "hello" });
  }';

        $transaction = new \triagens\ArangoDb\Transaction($this->connection);
        $transaction->setWriteCollections($writeCollections);
        $transaction->setReadCollections($readCollections);
        $transaction->setAction($action);

        $e = null;
        try {
            $result = $transaction->execute();
        } catch (triagens\ArangoDb\ServerException $e) {
        }
        $details                = $e->getDetails();
        $expectedCutDownMessage = "cannot save document: unique constraint violated:";
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
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_02');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection1);
        unset($this->collection2);
        unset($this->connection);
    }
}
