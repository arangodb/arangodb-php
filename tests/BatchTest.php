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
    
    public function testCreateDocumentBatch(){
//        #var_dump($batch);
         $batch = $this->connection->captureBatch('myBatch');

        $this->assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
        
 
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $document = Document::createFromArray(array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        
        $batch = $this->connection->processBatch();
        #var_dump ($batch);
        //todo: check if we have both inserted documents
        
    }

 
    public function testCreateMixedBatchWithNoPartIds(){
        $batch = $this->connection->captureBatch('myBatch');
        $this->assertInstanceOf('\triagens\ArangoDb\Batch', $batch);

        // Create collection        
        $connection = $this->connection;
        $collection = new \triagens\ArangoDb\Collection();
        $collectionHandler = new \triagens\ArangoDb\CollectionHandler($connection);

        $name = 'ArangoDB-PHP-TestSuite-TestCollection-02';
        $collection->setName($name);
        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Did not return a numeric id!');

        // Create documents
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $document = Document::createFromArray(array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        
        $batch = $this->connection->processBatch();
        //todo: check if we have both inserted documents
        
        // Get previously created collection and delete it, from inside a batch
        $resultingCollection = $collectionHandler->get($batch[0]);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue($name === $resultingAttribute, 'The created collection name and resulting collection name do not match!');

        $this->assertEquals(Collection::getDefaultType(), $resultingCollection->getType());

        $batch = $this->connection->captureBatch('myBatch2');
        $this->assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
                
        $response = $collectionHandler->delete($resultingCollection);        
        
        $batch = $this->connection->processBatch();
        #var_dump ($batch);
        
        
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
