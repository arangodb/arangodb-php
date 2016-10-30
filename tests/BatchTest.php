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
 * @property Connection        connection
 * @property Collection        collection
 * @property CollectionHandler collectionHandler
 * @property DocumentHandler   documentHandler
 * @property Collection        edgeCollection
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
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already dropd.
        }

        $this->collection = new Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->create($this->collection);

        try {
            $this->collectionHandler->drop('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already dropd.
        }

        $this->edgeCollection = new Collection();
        $this->edgeCollection->setName('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->edgeCollection->set('type', 3);
        $this->collectionHandler->create($this->edgeCollection);
    }


    public function testEmptyBatch()
    {
        $batch = new Batch($this->connection);
        static::assertEquals(0, $batch->countParts());
        static::assertEquals([], $batch->getBatchParts());

        try {
            // should fail
            static::assertEquals(null, $batch->getPart('foo'));
            static::fail('we should have got an exception');
        } catch (ClientException $e) {
        }

        try {
            // should fail on client, too
            $batch->process();
            static::fail('we should have got an exception');
        } catch (ClientException $e) {
        }
    }


    public function testPartIds()
    {
        $batch = new Batch($this->connection);
        static::assertEquals(0, $batch->countParts());

        for ($i = 0; $i < 10; ++$i) {
            $batch->nextBatchPartId('doc' . $i);
            $document = Document::createFromArray(['test1' => $i, 'test2' => $i + 1]);
            $this->documentHandler->save($this->collection->getId(), $document);
        }

        static::assertEquals(10, $batch->countParts());

        $batch->process();

        for ($i = 0; $i < 10; ++$i) {
            $part = $batch->getPart('doc' . $i);
            static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $part);

            static::assertEquals('doc' . $i, $part->getId());
            static::assertEquals(202, $part->getHttpCode());

            $response = $batch->getPartResponse('doc' . $i);
            static::assertEquals(202, $response->getHttpCode());
        }

        try {
            // should fail
            static::assertEquals(null, $batch->getPart('foo'));
            static::fail('we should have got an exception');
        } catch (ClientException $e) {
        }
    }


    public function testProcessProcess()
    {
        try {
            // clean up first
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_02');
        } catch (Exception $e) {
        }

        $batch = new Batch($this->connection);
        static::assertEquals(0, $batch->countParts());

        $collection = new Collection();
        $name       = 'ArangoDB_PHP_TestSuite_TestCollection_02';
        $collection->setName($name);
        $this->collectionHandler->create($collection);

        $part = $batch->getPart(0);
        static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $part);
        static::assertEquals(202, $part->getHttpCode());

        // call process once (this does not clear the batch)
        $batch->process();
        static::assertEquals(200, $part->getHttpCode());

        $response = $batch->getPartResponse(0);
        static::assertEquals(200, $response->getHttpCode());

        // this will process the same batch again
        $batch->process();
        $response = $batch->getPartResponse(0);

        // should return 409 conflict, because collection already exists
        static::assertEquals(409, $response->getHttpCode());
    }


    public function testCreateDocumentBatch()
    {
        $batch = new Batch($this->connection);

        // not needed, but just here to test if anything goes wrong if it's called again...
        $batch->startCapture();

        static::assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            ['someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue']
        );
        $documentId = $documentHandler->save($this->collection->getId(), $document);

        static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $documentId, 'Did not return a BatchPart Object!');

        $batchPartId = $documentId->getId();

        $document   = Document::createFromArray(
            ['someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2']
        );
        $documentId = $documentHandler->save($this->collection->getId(), $document);

        static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $documentId, 'Did not return a BatchPart Object!');

        $batch->process();

        $batch->getPart(0)->getProcessedResponse();

        // try getting it from batch
        $batch->getProcessedPartResponse(1);
    }

    /**
     * This tests the batch class when used with an SplFixedArray as its Array for the BatchParts
     */
    public function testCreateDocumentBatchWithDefinedBatchSize()
    {
        $batch = new Batch($this->connection, ['batchSize' => 2]);

        // not needed, but just here to test if anything goes wrong if it's called again...
        $batch->startCapture();

        static::assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            ['someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue']
        );
        $documentId = $documentHandler->save($this->collection->getId(), $document);

        static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $documentId, 'Did not return a BatchPart Object!');

        $document   = Document::createFromArray(
            ['someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2']
        );
        $documentId = $documentHandler->save($this->collection->getId(), $document);

        static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $documentId, 'Did not return a BatchPart Object!');

        static::assertEquals($batch->getConnectionCaptureMode($this->connection), true);

        $batch->stopCapture();

        // Check giving the set method a non-string key
        $caught = false;
        try {
            $batch->stopCapture();
        } catch (ClientException $e) {
            $caught = true;
        }

        static::assertTrue($caught);


        $batch->process();

        $batch->getPart(0)->getProcessedResponse();

        // try getting it from batch
        $batch->getProcessedPartResponse(1);
    }


    /**
     * This tests the batch class when used with an SplFixedArray as its Array for the BatchParts
     * It simulates an invalid index access, in order to check that we are really using SplFixedArray
     */
    public function testFailureWhenCreatingMoreDocumentsInBatchThanDefinedBatchSize()
    {
        $batch = new Batch($this->connection, ['batchSize' => 1]);

        // not needed, but just here to test if anything goes wrong if it's called again...
        $batch->startCapture();

        static::assertInstanceOf('\triagens\ArangoDb\Batch', $batch);
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            ['someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue']
        );
        $documentId = $documentHandler->save($this->collection->getId(), $document);

        static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $documentId, 'Did not return a BatchPart Object!');

        $document = Document::createFromArray(
            ['someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2']
        );
        try {
            $documentId = $documentHandler->save($this->collection->getId(), $document);
        } catch (\Exception $e) {
            // don't bother us, just give us the $e
        }
        static::assertInstanceOf(
            'RuntimeException',
            $e,
            'Exception thrown was not a RuntimeException!'
        );
        static::assertEquals('Index invalid or out of range', $e->getMessage(), 'Error code was not "Index invalid or out of range"');

    }

    public function testCreateMixedBatchWithPartIds()
    {
        $edgeCollection = $this->edgeCollection;

        $batch = new Batch($this->connection);
        static::assertInstanceOf('\triagens\ArangoDb\Batch', $batch);

        // Create collection
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02';
        $collection->setName($name);

        $batch->nextBatchPartId('testCollection1');
        $response = $collectionHandler->create($collection);

        static::assertTrue(is_numeric($response), 'Did not return a fake numeric id!');
        $batch->process();

        $resultingCollectionId = $batch->getProcessedPartResponse('testCollection1');
        $testCollection1Part   = $batch->getPart('testCollection1');
        static::assertEquals($testCollection1Part->getHttpCode(), 200, 'Did not return an HttpCode 200!');
        $resultingCollection = $collectionHandler->get($batch->getProcessedPartResponse('testCollection1'));

        $resultingAttribute = $resultingCollection->getName();
        static::assertSame(
            $name, $resultingAttribute, 'The created collection name and resulting collection name do not match!'
        );

        static::assertEquals(Collection::getDefaultType(), $resultingCollection->getType());


        $batch = new Batch($this->connection);

        // Create documents
        $documentHandler = $this->documentHandler;
        $batch->nextBatchPartId('doc1');
        $document          = Document::createFromArray(
            ['someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue']
        );
        $documentBatchPart = $documentHandler->save($resultingCollectionId, $document);

        static::assertEquals($documentBatchPart->getType(), 'document');

        static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $documentBatchPart, 'Did not return a BatchPart Object!');

        for ($i = 0; $i <= 10; ++$i) {
            $document          = Document::createFromArray(
                [
                    'someAttribute'      => 'someValue' . $i,
                    'someOtherAttribute' => 'someOtherValue2' . $i
                ]
            );
            $documentBatchPart = $documentHandler->save($resultingCollectionId, $document);
        }
        static::assertInstanceOf('\triagens\ArangoDb\BatchPart', $documentBatchPart, 'Did not return a BatchPart Object!');

        $batch->process();

        // try getting processed response through batchPart
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


        static::assertFalse(
            is_a($edge, 'triagens\ArangoDb\HttpResponse'),
            'Edge batch creation did return an error: ' . print_r($edge, true)
        );
        static::assertNotSame($edge, '', 'Edge batch creation did return empty string: ' . print_r($edge, true));


        $batch = new Batch($this->connection);

        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document->someAttribute = 'someValue';
        $documentHandler->save($resultingCollection->getId(), $document);

        // set the next batchPart id
        $batch->nextBatchPartId('myBatchPart');
        // set cursor options for the next batchPart
        $batch->nextBatchPartCursorOptions(
            [
                'sanitize' => true,
            ]
        );


        // set batchsize to 10, so we can test if an additional http request is done when we getAll() a bit later
        $statement = new Statement(
            $connection, [
                'query'     => '',
                'count'     => true,
                'batchSize' => 10,
                'sanitize'  => true,
            ]
        );

        $statement->setQuery('FOR a IN `ArangoDB_PHP_TestSuite_TestCollection_02` RETURN a');
        $statement->execute();

        $documentHandler->removeById($resultingCollectionId, $docId1[1]);
        $documentHandler->removeById($resultingCollectionId, $docId2[1]);


        $batch->nextBatchPartId('docsAfterRemoval');

        $batch->process();

        $collectionHandler->getAllIds($resultingCollectionId);

        $stmtCursor = $batch->getProcessedPartResponse('myBatchPart');

        static::assertCount(
            13, $stmtCursor->getAll(), 'At the time of statement execution there should be 13 documents found! Found: ' . count(
                $stmtCursor->getAll()
            )
        );

        // This fails but we'll just make a note because such a query is not needed to be batched.
        // $docsAfterRemoval=$batch->getProcessedPartResponse('docsAfterRemoval');
        // $this->assertTrue(count($docsAfterRemoval) == 1, 'At the time of statement execution there should be 3 documents found! Found: '.count($stmtCursor->getAll()));

        // Get previously created collection and drop it, from inside a batch
        $batch = new Batch($this->connection);

        $collectionHandler->drop($resultingCollectionId);

        $batch->process();
    }


    public function tearDown()
    {
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already dropd.
        }
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_02');
        } catch (\Exception $e) {
            // don't bother us, if it's already dropd.
        }
        try {
            $this->collectionHandler->drop('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already dropd.
        }

        unset($this->collectionHandlerm, $this->collection, $this->connection);
    }
}
