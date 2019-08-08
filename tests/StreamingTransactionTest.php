<?php
/**
 * ArangoDB PHP client testsuite
 * File: StreamingTransactionTest.php
 *
 * @package ArangoDBClient
 * @author  Jan Steemann
 */

namespace ArangoDBClient;

/**
 * Class StreamingTransactionTest
 *
 * Basic Tests for the streaming transaction API implementation
 *
 * @property Connection        $connection
 * @property CollectionHandler $collectionHandler
 * @property Collection        $collection1
 * @property Collection        $collection2
 * @package ArangoDBClient
 */
class StreamingTransactionTest extends
    \PHPUnit_Framework_TestCase
{
    protected static $testsTimestamp;

    private $_shutdown;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        static::$testsTimestamp = str_replace('.', '_', (string) microtime(true));
    }


    public function setUp()
    {
        // transactions to shut down later
        $this->_shutdown         = [];

        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);

        // clean up first
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
            //Silence the exception
        }


        $this->collection1 = new Collection();
        $this->collection1->setName('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp);
        $this->collectionHandler->create($this->collection1);

        $this->collection2 = new Collection();
        $this->collection2->setName('ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp);
        $this->collectionHandler->create($this->collection2);
        
        $adminHandler = new AdminHandler($this->connection);
        $this->isMMFilesEngine         = ($adminHandler->getEngine()["name"] == "mmfiles"); 
        
        $this->transactionHandler = new StreamingTransactionHandler($this->connection);
    }


    public function testCreateTransaction()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $status = $this->transactionHandler->getStatus($trx);

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('running', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertTrue(in_array($trx->getId(), $running));
    }
    
    public function testCreateAndAbortTransaction()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        static::assertTrue($this->transactionHandler->abort($trx));
        $status = $this->transactionHandler->getStatus($trx);

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('aborted', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertFalse(in_array($trx->getId(), $running));
    }
    
    public function testCreateAndAbortTransactionById()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        static::assertTrue($this->transactionHandler->abort($trx->getId()));
        $status = $this->transactionHandler->getStatus($trx);

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('aborted', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertFalse(in_array($trx->getId(), $running));
    }
    
    public function testCreateAndCommitTransaction()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        static::assertTrue($this->transactionHandler->commit($trx));
        $status = $this->transactionHandler->getStatus($trx);

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('committed', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertFalse(in_array($trx->getId(), $running));
    }
    
    public function testCreateAndCommitTransactionById()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        static::assertTrue($this->transactionHandler->commit($trx->getId()));
        $status = $this->transactionHandler->getStatus($trx);

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('committed', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertFalse(in_array($trx->getId(), $running));
    }
    
    public function testCreateAndGetStatusTransaction()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $status = $this->transactionHandler->getStatus($trx);

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('running', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertTrue(in_array($trx->getId(), $running));
    }
    
    public function testCreateAndGetStatusTransactionById()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $status = $this->transactionHandler->getStatus($trx->getId());

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('running', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertTrue(in_array($trx->getId(), $running));
    }
    
    public function testGetStatusForNonExistingTransaction()
    {
        $found = false;
        try {
            $this->transactionHandler->getStatus("999999999999");
            $found = true;
        } catch (\Exception $e) {
            static::assertEquals(404, $e->getCode());
        }
        static::assertFalse($found);
    }
    
    public function testCreateWithCollections()
    {
        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_READ => [ $this->collection1->getName(), $this->collection2->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $collection1 = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $collection1->getName());
        static::assertEquals('read', $collection1->getMode());
        
        $collection2 = $trx->getCollection($this->collection2->getName());
        static::assertEquals($this->collection2->getName(), $collection2->getName());
        static::assertEquals('read', $collection2->getMode());

        $status = $this->transactionHandler->getStatus($trx->getId());

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('running', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertTrue(in_array($trx->getId(), $running));
    }
    
    public function testCreateWithCollectionsAndModes()
    {
        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_WRITE => [ $this->collection1->getName() ],
                TransactionBase::ENTRY_EXCLUSIVE => [ $this->collection2->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $collection1 = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $collection1->getName());
        static::assertEquals('write', $collection1->getMode());
        
        $collection2 = $trx->getCollection($this->collection2->getName());
        static::assertEquals($this->collection2->getName(), $collection2->getName());
        static::assertEquals('exclusive', $collection2->getMode());

        $status = $this->transactionHandler->getStatus($trx->getId());

        static::assertEquals($trx->getId(), $status['id']);
        static::assertEquals('running', $status['status']);

        $running = array_map(function($trx) { return $trx['id']; }, $this->transactionHandler->getRunning()); 
        static::assertTrue(in_array($trx->getId(), $running));
    }
    
    public function testGetCollection()
    {
        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_READ => [ $this->collection1->getName(), $this->collection2->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        
        $collection1 = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $collection1->getName());
        static::assertEquals('read', $collection1->getMode());
        
        $collection2 = $trx->getCollection($this->collection2->getName());
        static::assertEquals($this->collection2->getName(), $collection2->getName());
        static::assertEquals('read', $collection2->getMode());

        $found = false;
        try {
            $trx->getCollection('piff');
            $found = true;
        } catch (\Exception $e) {
        }
        static::assertFalse($found);
    }
    
    public function testInsert()
    {
        if ($this->isMMFilesEngine) {
            $this->markTestSkipped("test is only meaningful with the rocksdb engine");
        }
        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_WRITE => [ $this->collection1->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $trxCollection = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $trxCollection->getName());
        
        $documentHandler = new DocumentHandler($this->connection);
        $result = $documentHandler->save($trxCollection, [ '_key' => 'test', 'value' => 'test' ]);
        static::assertEquals('test', $result);

        // non-transactional lookup should not find the document
        $found = false;
        try {
            $documentHandler->getById($this->collection1->getName(), "test");
            $found = true;
        } catch (\Exception $e) {
            static::assertEquals(404, $e->getCode());
        }
        static::assertFalse($found);

        // transactional lookup should find the document
        $doc = $documentHandler->getById($trxCollection, "test");
        static::assertEquals('test', $doc->getKey());
        
        // now commit
        static::assertTrue($this->transactionHandler->commit($trx->getId()));

        // non-transactional lookup should find the document now too
        $doc = $documentHandler->getById($this->collection1->getName(), "test");
        static::assertEquals('test', $doc->getKey());
    }
    
    public function testRemove()
    {
        if ($this->isMMFilesEngine) {
            $this->markTestSkipped("test is only meaningful with the rocksdb engine");
        }
        // insert a document before the transaction
        $documentHandler = new DocumentHandler($this->connection);
        $result = $documentHandler->save($this->collection1->getName(), [ '_key' => 'test', 'value' => 'test' ]);
        static::assertEquals('test', $result);

        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_WRITE => [ $this->collection1->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $trxCollection = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $trxCollection->getName());

        // document should be present inside transaction
        $doc = $documentHandler->getById($trxCollection, "test");
        static::assertEquals('test', $doc->getKey());

        // remove document inside transaction
        $result = $documentHandler->removeById($trxCollection, 'test');
        static::assertTrue($result);

        // transactional lookup should not find the document
        $found = false;
        try {
            $documentHandler->getById($trxCollection, "test");
            $found = true;
        } catch (\Exception $e) {
            static::assertEquals(404, $e->getCode());
        }
        static::assertFalse($found);
        
        // non-transactional lookup should still see it
        $doc = $documentHandler->getById($this->collection1->getName(), "test");
        static::assertEquals('test', $doc->getKey());
        
        // now commit
        static::assertTrue($this->transactionHandler->commit($trx->getId()));

        // now it should be gone
        $found = false;
        try {
            $documentHandler->getById($this->collection1->getName(), "test");
            $found = true;
        } catch (\Exception $e) {
            static::assertEquals(404, $e->getCode());
        }
        static::assertFalse($found);
    }
    
    public function testUpdate()
    {
        if ($this->isMMFilesEngine) {
            $this->markTestSkipped("test is only meaningful with the rocksdb engine");
        }
        // insert a document before the transaction
        $documentHandler = new DocumentHandler($this->connection);
        $result = $documentHandler->save($this->collection1->getName(), [ '_key' => 'test', 'value' => 'test' ]);
        static::assertEquals('test', $result);

        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_WRITE => [ $this->collection1->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $trxCollection = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $trxCollection->getName());

        // document should be present inside transaction
        $doc = $documentHandler->getById($trxCollection, "test");
        static::assertEquals('test', $doc->getKey());
        static::assertEquals('test', $doc->value);

        // update document inside transaction
        $doc->value = 'foobar';
        $result = $documentHandler->updateById($trxCollection, 'test', $doc);
        static::assertTrue($result);

        // transactional lookup should find the modified document
        $doc = $documentHandler->getById($trxCollection, "test");
        static::assertEquals('test', $doc->getKey());
        static::assertEquals('foobar', $doc->value);
        
        // non-transactional lookup should still see the old document
        $doc = $documentHandler->getById($this->collection1->getName(), "test");
        static::assertEquals('test', $doc->getKey());
        static::assertEquals('test', $doc->value);
        
        // now commit
        static::assertTrue($this->transactionHandler->commit($trx->getId()));

        $doc = $documentHandler->getById($this->collection1->getName(), "test");
        static::assertEquals('test', $doc->getKey());
        static::assertEquals('foobar', $doc->value);
    }
    
    public function testReplace()
    {
        if ($this->isMMFilesEngine) {
            $this->markTestSkipped("test is only meaningful with the rocksdb engine");
        }
        // insert a document before the transaction
        $documentHandler = new DocumentHandler($this->connection);
        $result = $documentHandler->save($this->collection1->getName(), [ '_key' => 'test', 'value' => 'test' ]);
        static::assertEquals('test', $result);

        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_WRITE => [ $this->collection1->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $trxCollection = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $trxCollection->getName());

        // document should be present inside transaction
        $doc = $documentHandler->getById($trxCollection, "test");
        static::assertEquals('test', $doc->getKey());
        static::assertEquals('test', $doc->value);

        // replace document inside transaction
        unset($doc->value);
        $doc->hihi = 'hoho';
        $result = $documentHandler->replaceById($trxCollection, 'test', $doc);
        static::assertTrue($result);

        // transactional lookup should find the modified document
        $doc = $documentHandler->getById($trxCollection, "test");
        static::assertEquals('test', $doc->getKey());
        static::assertEquals('hoho', $doc->hihi);
        static::assertObjectNotHasAttribute('value', $doc);
        
        // non-transactional lookup should still see the old document
        $doc = $documentHandler->getById($this->collection1->getName(), "test");
        static::assertEquals('test', $doc->getKey());
        static::assertEquals('test', $doc->value);
        static::assertObjectNotHasAttribute('hihi', $doc);
        
        // now commit
        static::assertTrue($this->transactionHandler->commit($trx->getId()));

        $doc = $documentHandler->getById($this->collection1->getName(), "test");
        static::assertEquals('test', $doc->getKey());
        static::assertEquals('hoho', $doc->hihi);
        static::assertObjectNotHasAttribute('value', $doc);
    }
    
    public function testTruncate()
    {
        if ($this->isMMFilesEngine) {
            $this->markTestSkipped("test is only meaningful with the rocksdb engine");
        }
        $stmt = new Statement($this->connection, [
            'query' => 'FOR i IN 1..10 INSERT { _key: CONCAT("test", i), value: i } INTO @@collection',
            'bindVars' => [ '@collection' => $this->collection1->getName() ]
        ]);
        $stmt->execute();

        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_WRITE => [ $this->collection1->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $trxCollection = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $trxCollection->getName());

        // truncate the collection inside the transaction
        $this->collectionHandler->truncate($trxCollection);

        // transactional lookup should not find any documents
        $collectionHandler = new CollectionHandler($this->connection);
        static::assertEquals(0, $collectionHandler->count($trxCollection));
        $documentHandler = new DocumentHandler($this->connection);
        $found = false;
        for ($i = 1; $i <= 10; ++$i) {
            try {
                $documentHandler->getById($trxCollection, "test" . $i);
                $found = true;
                break;
            } catch (\Exception $e) {
                static::assertEquals(404, $e->getCode());
            }
        }
        static::assertFalse($found);

        // non-transactional lookup should find them all!
        static::assertEquals(10, $this->collectionHandler->count($this->collection1->getName()));
        for ($i = 1; $i <= 10; ++$i) {
            $doc = $documentHandler->getById($this->collection1->getName(), "test" . $i);
            self::assertEquals('test' . $i, $doc->getKey());
        }
    }
    
    public function testQuery()
    {
        if ($this->isMMFilesEngine) {
            $this->markTestSkipped("test is only meaningful with the rocksdb engine");
        }
        $trx = new StreamingTransaction($this->connection, [
            TransactionBase::ENTRY_COLLECTIONS => [
                TransactionBase::ENTRY_WRITE => [ $this->collection1->getName() ]
            ]
        ]);

        $trx = $this->transactionHandler->create($trx);
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue(is_string($trx->getId()));

        $trxCollection = $trx->getCollection($this->collection1->getName());
        static::assertEquals($this->collection1->getName(), $trxCollection->getName());

        // execute query in transaction
        $result = $trx->query([
            'query' => 'FOR i IN 1..10 INSERT { _key: CONCAT("test", i), value: i } INTO @@collection',
            'bindVars' => [ '@collection' => $this->collection1->getName() ]
        ]);

        // non-transactional lookup should not find any documents
        $documentHandler = new DocumentHandler($this->connection);
        $found = false;
        for ($i = 1; $i <= 10; ++$i) {
            try {
                $documentHandler->getById($this->collection1->getName(), "test" . $i);
                $found = true;
                break;
            } catch (\Exception $e) {
                static::assertEquals(404, $e->getCode());
            }
        }
        static::assertFalse($found);

        // documents should not be visible outside of transaction
        $collectionHandler = new CollectionHandler($this->connection);
        static::assertEquals(0, $this->collectionHandler->count($this->collection1->getName()));
        
        // documents should be visible outside of transaction
        static::assertEquals(10, $collectionHandler->count($trxCollection));
    }
    
    public function testCommitAndthenCommitTransaction()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue($this->transactionHandler->commit($trx));
        $status = $this->transactionHandler->getStatus($trx);

        static::assertTrue($this->transactionHandler->commit($trx));
    }
    
    public function testCommitAndthenAbortTransaction()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue($this->transactionHandler->commit($trx));
        $status = $this->transactionHandler->getStatus($trx);

        $success = false;
        try {
            $this->transactionHandler->abort($trx);
            $success = true;
        } catch (\Exception $e) {
        }

        static::assertFalse($success);
    }
    
    public function testAbortAndthenAbortTransaction()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue($this->transactionHandler->abort($trx));
        $status = $this->transactionHandler->getStatus($trx);

        static::assertTrue($this->transactionHandler->abort($trx));
    }
    
    public function testAbortAndthenCommitTransaction()
    {
        $trx = $this->transactionHandler->create();
        $this->_shutdown[] = $trx;
        static::assertInstanceOf(StreamingTransaction::class, $trx);
        
        static::assertTrue($this->transactionHandler->abort($trx));
        $status = $this->transactionHandler->getStatus($trx);

        $success = false;
        try {
            $this->transactionHandler->commit($trx);
            $success = true;
        } catch (\Exception $e) {
        }

        static::assertFalse($success);
    }
    
    public function tearDown()
    {
        foreach ($this->_shutdown as $trx) {
            try {
                $this->transactionHandler->abort($trx);
            } catch (\Exception $e) {
            }
        }

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
    }
}
