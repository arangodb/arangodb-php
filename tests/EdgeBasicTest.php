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
        $this->edgeCollection    = new Collection();
        $this->edgeCollection->setName('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->edgeCollection->set('type', 3);
        $this->collection = new Collection();
        $this->collection->setName('ArangoDBPHPTestSuiteTestCollection01');

        $this->collectionHandler->add($this->edgeCollection);

        $this->collectionHandler->add($this->collection);
    }


    /**
     * Test if Edge and EdgeHandler instances can be initialized
     */
    public function testInitializeEdge()
    {
        $this->collection        = new Collection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        $document                = new Edge();
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $document);
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $document);
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


        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document2);
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
                            array('label' => 'knows (but created using an array instead of an edge object)')
        );

        $resultingDocument = $documentHandler->get($edgeCollection->getName(), $edgeDocumentId);

        $resultingEdge = $edgeDocumentHandler->get($edgeCollection->getName(), $edgeDocumentId);
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $resultingEdge);

        $resultingAttribute = $resultingEdge->label;
        $this->assertTrue(
             $resultingAttribute === 'knows',
             'Attribute set on the Edge is different from the one retrieved!'
        );


        $edgesQuery1Result = $edgeDocumentHandler->edges($edgeCollection->getName(), $documentHandle1, 'out');
        $this->assertArrayHasKey(
             'documents',
             $edgesQuery1Result,
             "edges didn't return an array with a documents attribute!"
        );

        $statement = new Statement($connection, array(
                                                     "query"     => '',
                                                     "count"     => true,
                                                     "batchSize" => 1000,
                                                     "sanitize"  => true,
                                                ));
        $statement->setQuery(
                  'FOR p IN PATHS(ArangoDBPHPTestSuiteTestCollection01, ArangoDBPHPTestSuiteTestEdgeCollection01, "outbound")  RETURN p'
        );
        $cursor = $statement->execute();

        $result = $cursor->current();
        $this->assertInstanceOf(
             'triagens\ArangoDb\Document',
             $result,
             "IN PATHS statement did not return a document object!"
        );
        $resultingDocument->set('label', 'knows not');

        $documentHandler->update($resultingDocument);


        $resultingEdge      = $documentHandler->get($edgeCollection->getName(), $edgeDocumentId);
        $resultingAttribute = $resultingEdge->label;
        $this->assertTrue(
             $resultingAttribute === 'knows not',
             'Attribute "knows not" set on the Edge is different from the one retrieved (' . $resultingAttribute . ')!'
        );


        $documentHandler->delete($document1);
        $documentHandler->delete($document2);

        // In ArangoDB deleting a vertex doesn't delete the associated edge, unless we're using the graph module. Caution!
        $edgeDocumentHandler->delete($resultingEdge);
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


        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();

        $isoValue = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "knowsü");
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
        $this->assertTrue(
             $resultingAttribute === 'knows',
             'Attribute set on the Edge is different from the one retrieved!'
        );


        $edgesQuery1Result = $edgeDocumentHandler->edges($edgeCollection->getId(), $documentHandle1, 'out');
        $this->assertArrayHasKey(
             'documents',
             $edgesQuery1Result,
             "edges didn't return an array with a documents attribute!"
        );

        $statement = new Statement($connection, array(
                                                     "query"     => '',
                                                     "count"     => true,
                                                     "batchSize" => 1000,
                                                     "sanitize"  => true,
                                                ));
        $statement->setQuery(
                  'FOR p IN PATHS(ArangoDBPHPTestSuiteTestCollection01, ArangoDBPHPTestSuiteTestEdgeCollection01, "outbound")  RETURN p'
        );
        $cursor = $statement->execute();

        $result = $cursor->current();
        $this->assertInstanceOf(
             'triagens\ArangoDb\Document',
             $result,
             "IN PATHS statement did not return a document object!"
        );
        $resultingEdge->set('label', 'knows not');

        $documentHandler->update($resultingEdge);


        $resultingEdge      = $edgeDocumentHandler->get($edgeCollection->getId(), $edgeDocumentId);
        $resultingAttribute = $resultingEdge->label;
        $this->assertTrue(
             $resultingAttribute === 'knows not',
             'Attribute "knows not" set on the Edge is different from the one retrieved (' . $resultingAttribute . ')!'
        );


        $documentHandler->delete($document1);
        $documentHandler->delete($document2);

        // On ArangoDB 1.0 deleting a vertex doesn't delete the associated edge. Caution!
        $edgeDocumentHandler->delete($resultingEdge);
    }


    public function tearDown()
    {
        try {
            $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }
        try {
            $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }


        unset($this->documentHandler);
        unset($this->document);
        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
