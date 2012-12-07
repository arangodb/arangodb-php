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

     #   $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
     #   var_dump($resultingDocument);
      #  $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
       # $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue'));
#        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue'));

    #    $response = $documentHandler->delete($document);
#        $this->assertTrue(true === $response, 'Delete should return true!');

 
        
        $batch = $this->connection->processBatch();
        var_dump ($batch);
        
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
