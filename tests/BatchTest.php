<?php
/**
 * ArangoDB PHP client testsuite
 * File: BatchTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * @property Connection             connection
 * @property Collection             collection
 * @property CollectionHandler      collectionHandler
 * @property DocumentHandler        documentHandler
 * @property Collection             edgeCollection
 */
class BatchTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();

        $this->documentHandler   = new DocumentHandler($this->connection);
        $this->collectionHandler = new CollectionHandler($this->connection);
        
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        $this->collection = new Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection);
        
        try {
            $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }

        $this->edgeCollection = new Collection();
        $this->edgeCollection->setName('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->edgeCollection->set('type', 3);
        $this->collectionHandler->add($this->edgeCollection);
    }


    public function testEmptyBatch()
    {
        $batch = new Batch($this->connection);
        $this->assertEquals(0, $batch->countParts());
        $this->assertEquals(array(), $batch->getBatchParts());

        try {
            // should fail
            $this->assertEquals(null, $batch->getPart('foo'));
            $this->fail('we should have got an exception');
        } catch (ClientException $e) {
        }

        try {
            // should fail on client, too
            $batch->process();
            $this->fail('we should have got an exception');
        } catch (ClientException $e) {
        }
    }


    public function testPartIds()
    {
        $batch = new Batch($this->connection);
        $this->assertEquals(0, $batch->countParts());

        for ($i = 0; $i < 10; ++$i) {
            $batch->nextBatchPartId('doc' . $i);
            $document = Document::createFromArray(array('test1' => $i, 'test2' => ($i + 1)));
            $this->documentHandler->add($this->collection->getId(), $document);
        }

        $this->assertEquals(10, $batch->countParts());

        $batch->process();

        for ($i = 0; $i < 10; ++$i) {
            $part = $batch->getPart('doc' . $i);
            $this->assertInstanceOf('\triagens\ArangoDb\BatchPart', $part);

            $this->assertEquals('doc' . $i, $part->getId());
            $this->assertEquals(202, $part->getHttpCode());

            $response = $batch->getPartResponse('doc' . $i);
            $this->assertEquals(202, $response->getHttpCode());
        }

        try {
            // should fail
            $this->assertEquals(null, $batch->getPart('foo'));
            $this->fail('we should have got an exception');
        } catch (ClientException $e) {
        }
    }


    public function testProcessProcess()
    {
        try {
            // clean up first
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_02');
        } catch (Exception $e) {
        }

        $batch = new Batch($this->connection);
        $this->assertEquals(0, $batch->countParts());

        $collection = new Collection();
        $name       = 'ArangoDB_PHP_TestSuite_TestCollection_02';
        $collection->setName($name);
        $this->collectionHandler->add($collection);

        $part = $batch->getPart(0);
        $this->assertInstanceOf('\triagens\ArangoDb\BatchPart', $part);
        $this->assertEquals(202, $part->getHttpCode());

        // call process once (this does not clear the batch)
        $batch->process();
        $this->assertEquals(200, $part->getHttpCode());

        $response = $batch->getPartResponse(0);
        $this->assertEquals(200, $response->getHttpCode());

        // this will process the same batch again
        $batch->process();
        $response = $batch->getPartResponse(0);

        // should return 409 conflict, because collection already exists
        $this->assertEquals(409, $response->getHttpCode());
    }


    public function testCreateDocumentBatch()
    {

        $batch = new Batch($this->connection);

        // not needed, but just here to test if anything goes wrong if it's called again...
        $batch->startCapture();

        $this->assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
                              array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $document   = Document::createFromArray(
                              array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $batch->process();

        $batch->getPart(0)->getProcessedResponse();

        // try getting it from batch
        $batch->getProcessedPartResponse(1);
    }


    public function testCreateMixedBatchWithPartIds()
    {
        $edgeCollection = $this->edgeCollection;

        $batch = new Batch($this->connection);
        $this->assertInstanceOf('\triagens\ArangoDb\Batch', $batch);

        // Create collection
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02';
        $collection->setName($name);

        $batch->nextBatchPartId('testCollection1');
        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Did not return a fake numeric id!');
        $batch->process();

        $resultingCollectionId = $batch->getProcessedPartResponse('testCollection1');
        $testCollection1Part   = $batch->getPart('testCollection1');
        $this->assertTrue($testCollection1Part->getHttpCode() == 200, 'Did not return an HttpCode 200!');
        $resultingCollection = $collectionHandler->get($batch->getProcessedPartResponse('testCollection1'));

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue(
             $name === $resultingAttribute,
             'The created collection name and resulting collection name do not match!'
        );

        $this->assertEquals(Collection::getDefaultType(), $resultingCollection->getType());


        $batch = new Batch($this->connection);

        // Create documents
        $documentHandler = $this->documentHandler;
        $batch->nextBatchPartId('doc1');
        $document   = Document::createFromArray(
                              array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($resultingCollectionId, $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return a fake numeric id!');

        for ($i = 0; $i <= 10; ++$i) {
            $document   = Document::createFromArray(
                                  array(
                                       'someAttribute'      => 'someValue' . $i,
                                       'someOtherAttribute' => 'someOtherValue2' . $i
                                  )
            );
            $documentId = $documentHandler->add($resultingCollectionId, $document);
        }
        $this->assertTrue(is_numeric($documentId), 'Did not return a fake numeric id!');

        $batch->process();

        // try getting processed response through batchpart
        $testDocument1PartResponse = $batch->getPart('doc1')->getProcessedResponse();

        // try getting it from batch
        $testDocument2PartResponse = $batch->getProcessedPartResponse(1);


        $batch = new Batch($this->connection);

        $docId1 = explode('/', $testDocument1PartResponse);
        $docId2 = explode('/', $testDocument2PartResponse);
        $documentHandler->getById($resultingCollectionId, $docId1[1]);
        $documentHandler->getById($resultingCollectionId, $docId2[1]);

        $batch->process();

        $document1 = $batch->getProcessedPartResponse(0);
        $document2 = $batch->getProcessedPartResponse(1);

        $batch = new Batch($this->connection);
        // test edge creation
        $edgeDocument        = new Edge();
        $edgeDocumentHandler = new EdgeHandler($connection);
        $edgeDocument->set('label', 'knows');
        $edgeDocumentHandler->saveEdge(
                            $edgeCollection->getName(),
                            $document1->getHandle(),
                            $document2->getHandle(),
                            $edgeDocument
        );

        $batch->process();

        $edge = $batch->getProcessedPartResponse(0);


        $this->assertFalse(
             is_a($edge, 'triagens\ArangoDb\HttpResponse'),
             'Edge batch creation did return an error: ' . print_r($edge, true)
        );
        $this->assertTrue($edge == !'', 'Edge batch creation did return empty string: ' . print_r($edge, true));


        $batch = new Batch($this->connection);

        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document->someAttribute = 'someValue';
        $documentHandler->add($resultingCollection->getId(), $document);

        // set the next batchpart id
        $batch->nextBatchPartId('myBatchPart');
        // set cursor options for the next batchpart
        $batch->nextBatchPartCursorOptions(
              array(
                   "sanitize" => true,
              )
        );


        // set batchsize to 10, so we can test if an additional http request is done when we getAll() a bit later
        $statement = new Statement($connection, array(
                                                     "query"     => '',
                                                     "count"     => true,
                                                     "batchSize" => 10,
                                                     "sanitize"  => true,
                                                ));

        $statement->setQuery('FOR a IN `ArangoDB_PHP_TestSuite_TestCollection_02` RETURN a');
        $statement->execute();

        $documentHandler->removeById($resultingCollectionId, $docId1[1]);
        $documentHandler->removeById($resultingCollectionId, $docId2[1]);


        $batch->nextBatchPartId('docsAfterRemoval');
        $collectionHandler->getAllIds($resultingCollectionId);

        $batch->process();

        $stmtCursor = $batch->getProcessedPartResponse('myBatchPart');

        $this->assertTrue(
             count($stmtCursor->getAll()) == 13,
             'At the time of statement execution there should be 13 documents found! Found: ' . count(
                 $stmtCursor->getAll()
             )
        );

        // This fails but we'll just make a note because such a query is not needed to be batched.
        // $docsAfterRemoval=$batch->getProcessedPartResponse('docsAfterRemoval');
        // $this->assertTrue(count($docsAfterRemoval) == 1, 'At the time of statement execution there should be 3 documents found! Found: '.count($stmtCursor->getAll()));

        // Get previously created collection and delete it, from inside a batch
        $batch = new Batch($this->connection);

        $collectionHandler->delete($resultingCollectionId);

        $batch->process();
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
        try {
            $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
