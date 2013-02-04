<?php
/**
 * ArangoDB PHP client testsuite
 * File: documentbasictest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDb;

class EdgeBasicTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->edgeCollection = new \triagens\ArangoDb\Collection();
        $this->edgeCollection->setName('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->edgeCollection->set('type', 3);
        $this->collection = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDBPHPTestSuiteTestCollection01');
        
        $this->collectionHandler->add($this->edgeCollection);
        
        $this->collectionHandler->add($this->collection);
        
    }

    /**
     * Test if Edge and EdgeHandler instances can be initialized
     */
    public function testInitializeEdge()
    {
        $connection = $this->connection;
        $this->collection = new \triagens\ArangoDb\Collection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $document = new \triagens\ArangoDb\Edge();
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $document);
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $document);
        unset ($document);
    }

    /**
     * Try to create and delete an edge
     */
    public function testCreateAndDeleteEdge()
    {
        $connection = $this->connection;
        $collection = $this->collection;
        $edgeCollection = $this->edgeCollection;
        $collectionHandler = $this->collectionHandler;
        
        $document1 = new \triagens\ArangoDb\Document();
        $document2 = new \triagens\ArangoDb\Document();
        $documentHandler = new \triagens\ArangoDb\DocumentHandler($connection);
        
        $edgeDocument = new \triagens\ArangoDb\Edge();
        $edgeDocumentHandler = new \triagens\ArangoDb\EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';
        
        
        $documentId1 = $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentId2 = $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1=$document1->getHandle();
        $documentHandle2=$document2->getHandle();
        
        
        $edgeDocument->set('label','knows');
        $edgeDocumentId = $edgeDocumentHandler->saveEdge($edgeCollection->getName(), $documentHandle1, $documentHandle2, $edgeDocument);
        
        $resultingDocument = $documentHandler->get($edgeCollection->getId(), $edgeDocumentId);
        
        $resultingEdge = $documentHandler->get($edgeCollection->getId(), $edgeDocumentId);
        
        $resultingAttribute = $resultingEdge->label;
        $this->assertTrue($resultingAttribute === 'knows', 'Attribute set on the Edge is different from the one retrieved!');

        
        $edgesQuery1Result=$edgeDocumentHandler->edges($edgeCollection->getId(),$documentHandle1,'out');
        $this->assertArrayHasKey('documents',$edgesQuery1Result, "edges didn't return an array with a documents attribute!");     
        
        $statement = new \triagens\ArangoDb\Statement($connection, array(
            "query" => '',
            "count" => true,
            "batchSize" => 1000,
            "sanitize" => true,
        ));
        $statement->setQuery('FOR p IN PATHS(ArangoDBPHPTestSuiteTestCollection01, ArangoDBPHPTestSuiteTestEdgeCollection01, "outbound")  RETURN p');
        $cursor = $statement->execute();

        $result = $cursor->current();
        $this->assertInstanceOf('triagens\ArangoDb\Document',$result, "IN PATHS statement did not return a document object!");
        $resultingDocument->set('label','knows not');
       
        $resultingDocument2 = $documentHandler->update($resultingDocument);

          
        $resultingEdge = $documentHandler->get($edgeCollection->getName(), $edgeDocumentId);
        $resultingAttribute = $resultingEdge->label;
        $this->assertTrue($resultingAttribute === 'knows not', 'Attribute "knows not" set on the Edge is different from the one retrieved ('.$resultingAttribute.')!');
        
        
        $response = $documentHandler->delete($document1);
        $response = $documentHandler->delete($document2);
        
        // On ArangoDB 1.0 deleting a vertice doesn't delete the associated edge. Caution!
        $response = $edgeDocumentHandler->delete($resultingEdge);
        
    } 
    
    
     /**
     * Try to create and delete an edge with wrong encoding
     * We expect an exception here:
     * 
     * @expectedException triagens\ArangoDb\ClientException
     */
    public function testCreateAndDeleteEdgeWithWrongEncoding()
    {
        $connection = $this->connection;
        $collection = $this->collection;
        $edgeCollection = $this->edgeCollection;
        $collectionHandler = $this->collectionHandler;
        
        $document1 = new \triagens\ArangoDb\Document();
        $document2 = new \triagens\ArangoDb\Document();
        $documentHandler = new \triagens\ArangoDb\DocumentHandler($connection);
        
        $edgeDocument = new \triagens\ArangoDb\Edge();
        $edgeDocumentHandler = new \triagens\ArangoDb\EdgeHandler($connection);

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';
        
        
        $documentId1 = $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentId2 = $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1=$document1->getHandle();
        $documentHandle2=$document2->getHandle();
        
        $isoValue=iconv("UTF-8","ISO-8859-1//TRANSLIT","knowsü");
        $edgeDocument->set('label',$isoValue);
        
        $edgeDocumentId = $edgeDocumentHandler->saveEdge($edgeCollection->getId(), $documentHandle1, $documentHandle2, $edgeDocument);
        
        $resultingDocument = $documentHandler->get($edgeCollection->getId(), $edgeDocumentId);
        
        $resultingEdge = $documentHandler->get($edgeCollection->getId(), $edgeDocumentId);
        
        $resultingAttribute = $resultingEdge->label;
        $this->assertTrue($resultingAttribute === 'knows', 'Attribute set on the Edge is different from the one retrieved!');

        
        $edgesQuery1Result=$edgeDocumentHandler->edges($edgeCollection->getId(),$documentHandle1,'out');
        $this->assertArrayHasKey('documents',$edgesQuery1Result, "edges didn't return an array with a documents attribute!");     
        
        $statement = new \triagens\ArangoDb\Statement($connection, array(
            "query" => '',
            "count" => true,
            "batchSize" => 1000,
            "sanitize" => true,
        ));
        $statement->setQuery('FOR p IN PATHS(ArangoDBPHPTestSuiteTestCollection01, ArangoDBPHPTestSuiteTestEdgeCollection01, "outbound")  RETURN p');
        $cursor = $statement->execute();

        $result = $cursor->current();
        $this->assertInstanceOf('triagens\ArangoDb\Document',$result, "IN PATHS statement did not return a document object!");
        $resultingDocument->set('label','knows not');
       
        $resultingDocument2 = $documentHandler->update($resultingDocument);

          
        $resultingEdge = $documentHandler->get($edgeCollection->getId(), $edgeDocumentId);
        $resultingAttribute = $resultingEdge->label;
        $this->assertTrue($resultingAttribute === 'knows not', 'Attribute "knows not" set on the Edge is different from the one retrieved ('.$resultingAttribute.')!');
        
        
        $response = $documentHandler->delete($document1);
        $response = $documentHandler->delete($document2);
        
        // On ArangoDB 1.0 deleting a vertice doesn't delete the associated edge. Caution!
        $response = $edgeDocumentHandler->delete($resultingEdge);
        
    }

    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            #don't bother us, if it's already deleted.
        }
        try {
            $response = $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestCollection01');
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
