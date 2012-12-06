<?php
/**
 * ArangoDB PHP client testsuite
 * File: BatchTest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDb;

class BatchTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->collection = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDB-PHP-TestSuite-TestCollection-01');
        $this->collectionHandler->add($this->collection);
        $this->documentHandler = new DocumentHandler($this->connection);
    }
    
    public function testCreateBatch(){
        $batch = $this->connection->captureBatch('myBatch');
        #var_dump($batch);
        $this->assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
    }

 
    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB-PHP-TestSuite-TestCollection-01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
