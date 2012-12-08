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
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection);
        $this->documentHandler = new DocumentHandler($this->connection);
    }
    
    public function testCreateDocumentBatch(){

        $batch = new Batch($this->connection);
        $batch->startCapture();

        $this->assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');
        
        $document = Document::createFromArray(array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');
        
        $responses= $batch->process();

        $testDocument1PartResponse = $batch->getPart(0)->getProcessedResponse();

        // try getting it from batch
        $testDocument2PartResponse = $batch->getProcessedPartResponse(1);
    }

 
    public function testCreateMixedBatchWithPartIds(){
      
        $batch = new Batch($this->connection);
        $this->assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
        
        // Create collection        
        $connection = $this->connection;
        $collection = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02';
        $collection->setName($name);
        
        $batch->nextBatchPartId('testcollection1');
        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Did not return a fake numeric id!');
        $result= $batch->process();
        
        $resultingCollectionId = $batch->getProcessedPartResponse('testcollection1');
        $testcollection1Part = $batch->getPart('testcollection1');
        $this->assertTrue($testcollection1Part->getHttpCode()==200, 'Did not return an HttpCode 200!');
        $resultingCollection = $collectionHandler->get($batch->getProcessedPartResponse('testcollection1'));
        
        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue($name === $resultingAttribute, 'The created collection name and resulting collection name do not match!');

        $this->assertEquals(Collection::getDefaultType(), $resultingCollection->getType());

        
        $batch = new Batch($this->connection);
        
        // Create documents
        $documentHandler = $this->documentHandler;
        $batch->nextBatchPartId('doc1');
        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($resultingCollectionId, $document);
        
        $this->assertTrue(is_numeric($documentId), 'Did not return a fake numeric id!');

        for($i=0;$i<=10;$i++){
        
        
        $document = Document::createFromArray(array('someAttribute' => 'someValue'.$i, 'someOtherAttribute' => 'someOtherValue2'.$i));
        $documentId = $documentHandler->add($resultingCollectionId, $document);
        }
        $this->assertTrue(is_numeric($documentId), 'Did not return a fake numeric id!');

        
        $result= $batch->process();

        // try getting processed response through batchpart
        $testDocument1PartResponse = $batch->getPart('doc1')->getProcessedResponse();
        
        // try getting it from batch
        $testDocument2PartResponse = $batch->getProcessedPartResponse(1);
        
        
        $batch = new Batch($this->connection);

        $docId1=split('/',$testDocument1PartResponse);
        $docId2=split('/',$testDocument2PartResponse);
        $documentHandler->getById($resultingCollectionId, $docId1[1]);
        $documentHandler->getById($resultingCollectionId, $docId2[1]);

        $result= $batch->process();    
        $document1= $batch->getProcessedPartResponse(0);
        $document2= $batch->getProcessedPartResponse(1);
        
        $batch = new Batch($this->connection);
        
        $document = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document->someAttribute = 'someValue';
        $documentId = $documentHandler->add($resultingCollection->getId(), $document);

        $batch->nextBatchPartId('myBatchPart');
        $batch->nextBatchPartCursorOptions(array(
            "sanitize" => true,
        ));

        $statement = new Statement($connection, array(
            "query" => '',
            "count" => true,
            "batchSize" => 1000,
            "sanitize" => true,
        ));
        
        $statement->setQuery('FOR a IN `ArangoDB_PHP_TestSuite_TestCollection_02` RETURN a');
        $cursor = $statement->execute();

        $documentHandler->removeById($resultingCollectionId, $docId1[1]);
        $documentHandler->removeById($resultingCollectionId, $docId2[1]);
        
        
        $batch->nextBatchPartId('docsAfterRemoval');
        $collectionHandler->getAllIds($resultingCollectionId);
        
        $result= $batch->process();
        
        $stmtCursor= $batch->getProcessedPartResponse('myBatchPart');
        
        $this->assertTrue(count($stmtCursor->getAll()) == 13, 'At the time of statement execution there should be 3 documents found! Found: '.count($stmtCursor->getAll()));
       
        // This fails but we'll just make a note because such a query is not needed to be batched.
        //$docsAfterRemoval=$batch->getProcessedPartResponse('docsAfterRemoval'); 
        //$this->assertTrue(count($docsAfterRemoval) == 1, 'At the time of statement execution there should be 3 documents found! Found: '.count($stmtCursor->getAll()));
        
        // Get previously created collection and delete it, from inside a batch
        $batch = new Batch($this->connection);
                
        $response = $collectionHandler->delete($resultingCollectionId);        
        
        $results= $batch->process();

        
    }

 
    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_02');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
