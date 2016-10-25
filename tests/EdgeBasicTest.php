<?php
/**
 * ArangoDB PHP client testsuite
 * File: EdgeBasicTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;


/**
 * Class EdgeBasicTest
 *
 * @property Connection        $connection
 * @property Collection        $collection
 * @property Collection        $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler   $documentHandler
 *
 * @package triagens\ArangoDb
 */
class EdgeBasicTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);

        try {
            $this->collectionHandler->drop('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }

        try {
            $this->collectionHandler->drop('ArangoDBPHPTestSuiteTestCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }

        $this->edgeCollection = new Collection();
        $this->edgeCollection->setName('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->edgeCollection->set('type', 3);

        $this->collection = new Collection();
        $this->collection->setName('ArangoDBPHPTestSuiteTestCollection01');

        $this->collectionHandler->create($this->edgeCollection);
        $this->collectionHandler->create($this->collection);
    }


    /**
     * Test if Edge and EdgeHandler instances can be initialized
     */
    public function testInitializeEdge()
    {
        $this->collection        = new Collection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        $document                = new Edge();
        static::assertInstanceOf('triagens\ArangoDb\Edge', $document);
        unset ($document);
    }


    /**
     * Try to create and delete an edge
     */
    public function testCreateAndDeleteEdge()
    {
        $connection     = $this->connection;
        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocument        = new Edge();
        $edgeDocumentHandler = new EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';


        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();


        $edgeDocument->set('label', 'knows');
        $edgeDocumentId = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            $edgeDocument
        );

        $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['label' => 'knows (but created using an array instead of an edge object)']
        );

        $resultingDocument = $documentHandler->get($edgeCollection->getName(), $edgeDocumentId);

        $resultingEdge = $edgeDocumentHandler->get($edgeCollection->getName(), $edgeDocumentId);
        static::assertInstanceOf('triagens\ArangoDb\Edge', $resultingEdge);

        $resultingAttribute = $resultingEdge->label;
        static::assertSame(
            $resultingAttribute, 'knows', 'Attribute set on the Edge is different from the one retrieved!'
        );


        $edgesQuery1Result = $edgeDocumentHandler->edges($edgeCollection->getName(), $documentHandle1, 'out');

        static::assertCount(2, $edgesQuery1Result);

        $statement = new Statement(
            $connection, [
                'query'     => '',
                'count'     => true,
                'batchSize' => 1000,
                'sanitize'  => true,
            ]
        );
        $statement->setQuery(
            'FOR start IN ArangoDBPHPTestSuiteTestCollection01 FOR v, e, p IN 0..1000 OUTBOUND start ArangoDBPHPTestSuiteTestEdgeCollection01 RETURN { source: start, destination: v, edges: p.edges, vertices: p.vertices }'

        );
        $cursor = $statement->execute();

        $result = $cursor->current();
        static::assertInstanceOf(
            'triagens\ArangoDb\Document',
            $result,
            'IN PATHS statement did not return a document object!'
        );
        $resultingDocument->set('label', 'knows not');

        $documentHandler->update($resultingDocument);


        $resultingEdge      = $documentHandler->get($edgeCollection->getName(), $edgeDocumentId);
        $resultingAttribute = $resultingEdge->label;
        static::assertSame(
            $resultingAttribute, 'knows not', 'Attribute "knows not" set on the Edge is different from the one retrieved (' . $resultingAttribute . ')!'
        );


        $documentHandler->remove($document1);
        $documentHandler->remove($document2);

        // In ArangoDB deleting a vertex doesn't delete the associated edge, unless we're using the graph module. Caution!
        $edgeDocumentHandler->remove($resultingEdge);
    }


    /**
     * Try to create and delete an edge
     */
    public function testCreateAndDeleteEdgeWithoutCreatedEdgeCollection()
    {
        $connection     = $this->connection;
        $edgeCollection = $this->edgeCollection;

        try {
            $this->collectionHandler->drop('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocument        = new Edge();
        $edgeDocumentHandler = new EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';


        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();


        $edgeDocument->set('label', 'knows');
        $edgeDocumentId = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            $edgeDocument,
            ['createCollection' => true]

        );

        $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['label' => 'knows (but created using an array instead of an edge object)']
        );

        $resultingDocument = $documentHandler->get($edgeCollection->getName(), $edgeDocumentId);

        $resultingEdge = $edgeDocumentHandler->get($edgeCollection->getName(), $edgeDocumentId);
        static::assertInstanceOf('triagens\ArangoDb\Edge', $resultingEdge);

        $resultingAttribute = $resultingEdge->label;
        static::assertSame(
            $resultingAttribute, 'knows', 'Attribute set on the Edge is different from the one retrieved!'
        );


        $edgesQuery1Result = $edgeDocumentHandler->edges($edgeCollection->getName(), $documentHandle1, 'out');

        static::assertCount(2, $edgesQuery1Result);

        $statement = new Statement(
            $connection, [
                'query'     => '',
                'count'     => true,
                'batchSize' => 1000,
                'sanitize'  => true,
            ]
        );
        $statement->setQuery(
            'FOR start IN ArangoDBPHPTestSuiteTestCollection01 FOR v, e, p IN 0..1000 OUTBOUND start ArangoDBPHPTestSuiteTestEdgeCollection01 RETURN { source: start, destination: v, edges: p.edges, vertices: p.vertices }'

        );
        $cursor = $statement->execute();

        $result = $cursor->current();
        static::assertInstanceOf(
            'triagens\ArangoDb\Document',
            $result,
            'IN PATHS statement did not return a document object!'
        );
        $resultingDocument->set('label', 'knows not');

        $documentHandler->update($resultingDocument);


        $resultingEdge      = $documentHandler->get($edgeCollection->getName(), $edgeDocumentId);
        $resultingAttribute = $resultingEdge->label;
        static::assertSame(
            $resultingAttribute, 'knows not', 'Attribute "knows not" set on the Edge is different from the one retrieved (' . $resultingAttribute . ')!'
        );


        $documentHandler->remove($document1);
        $documentHandler->remove($document2);

        // In ArangoDB deleting a vertex doesn't delete the associated edge, unless we're using the graph module. Caution!
        $edgeDocumentHandler->remove($resultingEdge);
    }


    /**
     * Try to create and delete an edge with wrong encoding
     * We expect an exception here:
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testCreateAndDeleteEdgeWithWrongEncoding()
    {
        $connection = $this->connection;
        $this->collection;
        $edgeCollection = $this->edgeCollection;
        $this->collectionHandler;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocument        = new Edge();
        $edgeDocumentHandler = new EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';


        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();

        $isoValue = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'knowsü');
        $edgeDocument->set('label', $isoValue);

        $edgeDocumentId = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getId(),
            $documentHandle1,
            $documentHandle2,
            $edgeDocument
        );

        //        $resultingDocument = $documentHandler->get($edgeCollection->getId(), $edgeDocumentId);

        $resultingEdge = $edgeDocumentHandler->get($edgeCollection->getId(), $edgeDocumentId);

        $resultingAttribute = $resultingEdge->label;
        static::assertSame(
            $resultingAttribute, 'knows', 'Attribute set on the Edge is different from the one retrieved!'
        );


        $edgesQuery1Result = $edgeDocumentHandler->edges($edgeCollection->getId(), $documentHandle1, 'out');

        static::assertCount(2, $edgesQuery1Result);

        $statement = new Statement(
            $connection, [
                'query'     => '',
                'count'     => true,
                'batchSize' => 1000,
                'sanitize'  => true,
            ]
        );
        $statement->setQuery(
            'FOR p IN PATHS(ArangoDBPHPTestSuiteTestCollection01, ArangoDBPHPTestSuiteTestEdgeCollection01, "outbound")  RETURN p'
        );
        $cursor = $statement->execute();

        $result = $cursor->current();
        static::assertInstanceOf(
            'triagens\ArangoDb\Document',
            $result,
            'IN PATHS statement did not return a document object!'
        );
        $resultingEdge->set('label', 'knows not');

        $documentHandler->update($resultingEdge);


        $resultingEdge      = $edgeDocumentHandler->get($edgeCollection->getId(), $edgeDocumentId);
        $resultingAttribute = $resultingEdge->label;
        static::assertSame(
            $resultingAttribute, 'knows not', 'Attribute "knows not" set on the Edge is different from the one retrieved (' . $resultingAttribute . ')!'
        );


        $documentHandler->remove($document1);
        $documentHandler->remove($document2);

        // On ArangoDB 1.0 deleting a vertex doesn't delete the associated edge. Caution!
        $edgeDocumentHandler->remove($resultingEdge);
    }

    /**
     * Try to create, get and delete a edge using the revision-
     */
    public function testCreateGetAndDeleteEdgeWithRevision()
    {
        $connection  = $this->connection;
        $edgeHandler = new EdgeHandler($connection);


        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocument = new Edge();

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';


        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();


        $edgeDocument->set('label', 'knows');
        $edgeId = $edgeHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            $edgeDocument
        );

        /**
         * lets get the edge in a wrong revision
         */
        try {
            $edgeHandler->get($edgeCollection->getId(), $edgeId, ['ifMatch' => true, 'revision' => 12345]);
        } catch (\Exception $exception412) {
        }
        static::assertEquals($exception412->getCode(), 412);

        try {
            $edgeHandler->get(
                $edgeCollection->getId(), $edgeId, [
                    'ifMatch'  => false,
                    'revision' => $edgeDocument->getRevision()
                ]
            );
        } catch (\Exception $exception304) {
        }
        static::assertEquals($exception304->getMessage(), 'Document has not changed.');

        $resultingEdge = $edgeHandler->get($edgeCollection->getId(), $edgeId);


        $resultingEdge->set('someAttribute', 'someValue2');
        $resultingEdge->set('someOtherAttribute', 'someOtherValue2');
        $edgeHandler->replace($resultingEdge);

        $oldRevision = $edgeHandler->get(
            $edgeCollection->getId(), $edgeId,
            ['revision' => $resultingEdge->getRevision()]
        );
        static::assertEquals($oldRevision->getRevision(), $resultingEdge->getRevision());
        $documentHandler->remove($document1);
        $documentHandler->remove($document2);
        $edgeHandler->removeById($edgeCollection->getName(), $edgeId);
    }

    /**
     * Try to create, head and delete a edge
     */
    public function testCreateHeadAndDeleteEdgeWithRevision()
    {
        $connection  = $this->connection;
        $edgeHandler = new EdgeHandler($connection);


        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocument = new Edge();

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';


        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();


        $edgeDocument->set('label', 'knows');
        $edgeId = $edgeHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            $edgeDocument
        );

        try {
            $edgeHandler->getHead($edgeCollection->getId(), $edgeId, '12345', true);
        } catch (\Exception $e412) {
        }

        static::assertEquals($e412->getCode(), 412);

        try {
            $edgeHandler->getHead($edgeCollection->getId(), 'notExisting');
        } catch (\Exception $e404) {
        }

        static::assertEquals($e404->getCode(), 404);


        $result304 = $edgeHandler->getHead($edgeCollection->getId(), $edgeId, $edgeDocument->getRevision(), false);
        static::assertEquals($result304['etag'], '"' . $edgeDocument->getRevision() . '"');
        static::assertEquals($result304['content-length'], 0);
        static::assertEquals($result304['httpCode'], 304);

        $result200 = $edgeHandler->getHead($edgeCollection->getId(), $edgeId, $edgeDocument->getRevision(), true);
        static::assertEquals($result200['etag'], '"' . $edgeDocument->getRevision() . '"');
        static::assertNotEquals($result200['content-length'], 0);
        static::assertEquals($result200['httpCode'], 200);
        $documentHandler->remove($document1);
        $documentHandler->remove($document2);
        $edgeHandler->removeById($edgeCollection->getName(), $edgeId);
    }

    /**
     * Test collectionHandler::getAllIds on an edge collection
     */
    public function testGetAllIds()
    {
        $connection     = $this->connection;
        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocumentHandler = new EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';

        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();

        $edgeDocument1 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 1]
        );

        $edgeDocument2 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle2,
            $documentHandle1,
            ['value' => 2]
        );

        $edgeDocument3 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 3]
        );

        $result = $this->collectionHandler->getAllIds($edgeCollection->getName());

        static::assertCount(3, $result);

        static::assertTrue(in_array($edgeDocument1, $result));
        static::assertTrue(in_array($edgeDocument2, $result));
        static::assertTrue(in_array($edgeDocument3, $result));
    }

    /**
     * Test edges method
     */
    public function testEdges()
    {
        $connection     = $this->connection;
        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocumentHandler = new EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';

        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();

        $edgeDocument1 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 1]
        );

        $edgeDocument2 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle2,
            $documentHandle1,
            ['value' => 2]
        );

        $edgeDocument3 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 3]
        );

        $edgesQueryResult = $edgeDocumentHandler->edges($edgeCollection->getName(), $documentHandle1);

        static::assertCount(3, $edgesQueryResult);
        foreach ($edgesQueryResult as $edge) {
            static::assertInstanceOf('triagens\ArangoDb\Edge', $edge);

            if ($edge->value === 1) {
                static::assertEquals($documentHandle1, $edge->getFrom());
                static::assertEquals($documentHandle2, $edge->getTo());
                static::assertEquals($edgeDocument1, $edge->getId());
            } else if ($edge->value === 2) {
                static::assertEquals($documentHandle2, $edge->getFrom());
                static::assertEquals($documentHandle1, $edge->getTo());
                static::assertEquals($edgeDocument2, $edge->getId());
            } else {
                static::assertEquals($documentHandle1, $edge->getFrom());
                static::assertEquals($documentHandle2, $edge->getTo());
                static::assertEquals($edgeDocument3, $edge->getId());
            }
        }

        // test empty result
        $edgesQueryResult = $edgeDocumentHandler->edges($edgeCollection->getName(), 'ArangoDBPHPTestSuiteTestCollection01/foobar');
        static::assertCount(0, $edgesQueryResult);
    }

    /**
     * Test edges method
     */
    public function testEdgesAny()
    {
        $connection     = $this->connection;
        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocumentHandler = new EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';

        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();

        $edgeDocument1 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 1]
        );

        $edgeDocument2 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle2,
            $documentHandle1,
            ['value' => 2]
        );

        $edgeDocument3 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 3]
        );

        $edgesQueryResult = $edgeDocumentHandler->edges($edgeCollection->getName(), $documentHandle1, 'any');

        static::assertCount(3, $edgesQueryResult);
        foreach ($edgesQueryResult as $edge) {
            static::assertInstanceOf('triagens\ArangoDb\Edge', $edge);

            if ($edge->value === 1) {
                static::assertEquals($documentHandle1, $edge->getFrom());
                static::assertEquals($documentHandle2, $edge->getTo());
                static::assertEquals($edgeDocument1, $edge->getId());
            } else if ($edge->value === 2) {
                static::assertEquals($documentHandle2, $edge->getFrom());
                static::assertEquals($documentHandle1, $edge->getTo());
                static::assertEquals($edgeDocument2, $edge->getId());
            } else {
                static::assertEquals($documentHandle1, $edge->getFrom());
                static::assertEquals($documentHandle2, $edge->getTo());
                static::assertEquals($edgeDocument3, $edge->getId());
            }
        }

        // test empty result
        $edgesQueryResult = $edgeDocumentHandler->edges($edgeCollection->getName(), 'ArangoDBPHPTestSuiteTestCollection01/foobar', 'any');
        static::assertCount(0, $edgesQueryResult);
    }

    /**
     * Test inEdges method
     */
    public function testEdgesIn()
    {
        $connection     = $this->connection;
        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocumentHandler = new EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';

        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();

        $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 1]
        );

        $edgeDocument2 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle2,
            $documentHandle1,
            ['value' => 2]
        );

        $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 3]
        );

        $edgesQueryResult = $edgeDocumentHandler->inEdges($edgeCollection->getName(), $documentHandle1);

        static::assertCount(1, $edgesQueryResult);
        $edge = $edgesQueryResult[0];
        static::assertEquals($documentHandle2, $edge->getFrom());
        static::assertEquals($documentHandle1, $edge->getTo());
        static::assertEquals($edgeDocument2, $edge->getId());

        // test empty result
        $edgesQueryResult = $edgeDocumentHandler->inEdges($edgeCollection->getName(), 'ArangoDBPHPTestSuiteTestCollection01/foobar');
        static::assertCount(0, $edgesQueryResult);
    }

    /**
     * Test outEdges method
     */
    public function testEdgesOut()
    {
        $connection     = $this->connection;
        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocumentHandler = new EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';

        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->save('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();

        $edgeDocument1 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 1]
        );

        $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle2,
            $documentHandle1,
            ['value' => 2]
        );

        $edgeDocument3 = $edgeDocumentHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            ['value' => 3]
        );

        $edgesQueryResult = $edgeDocumentHandler->outEdges($edgeCollection->getName(), $documentHandle1);

        static::assertCount(2, $edgesQueryResult);
        foreach ($edgesQueryResult as $edge) {
            static::assertInstanceOf('triagens\ArangoDb\Edge', $edge);

            if ($edge->value === 1) {
                static::assertEquals($documentHandle1, $edge->getFrom());
                static::assertEquals($documentHandle2, $edge->getTo());
                static::assertEquals($edgeDocument1, $edge->getId());
            } else {
                static::assertEquals($documentHandle1, $edge->getFrom());
                static::assertEquals($documentHandle2, $edge->getTo());
                static::assertEquals($edgeDocument3, $edge->getId());
            }
        }

        // test empty result
        $edgesQueryResult = $edgeDocumentHandler->outEdges($edgeCollection->getName(), 'ArangoDBPHPTestSuiteTestCollection01/foobar');
        static::assertCount(0, $edgesQueryResult);
    }

    public function tearDown()
    {
        try {
            $this->collectionHandler->drop('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }
        try {
            $this->collectionHandler->drop('ArangoDBPHPTestSuiteTestCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }


        unset($this->documentHandler, $this->document, $this->collectionHandler, $this->collection, $this->connection);
    }
}
