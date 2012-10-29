<?php
/**
 * ArangoDB PHP client testsuite
 * File: documentextendedtest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDb;

class DocumentExtendedTest extends \PHPUnit_Framework_TestCase
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

    /**
     * test for creation, get, and delete of a document given its settings through createFromArray()
     */
    public function testCreateGetAndDeleteDocumentThroughCreateFromArray()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue'));
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue'));

        $response = $documentHandler->delete($document);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

    /**
     * test for creation, get by example, and delete of a document given its settings through createFromArray()
     */
    public function testCreateGetbyExampleAndDeleteDocumentThroughCreateFromArray()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $cursor = $documentHandler->getByExample($this->collection->getId(), $document);

        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument=$cursor->current();
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue'));
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue'));

        $response = $documentHandler->delete($document);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

    /**
     * test for creation, update, get, and delete of a document given its settings through createFromArray()
     */
    public function testCreateUpdateGetAndDeleteDocumentThroughCreateFromArray()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $document->set('someAttribute','someValue2');
        $document->set('someOtherAttribute','someOtherValue2');
        $result = $documentHandler->update($document);

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue2'));
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue2'));

        $response = $documentHandler->delete($document);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }
    
    /**
     * test for creation, update, get, and delete having update and delete doing revision checks.
     */
    public function testCreateUpdateGetAndDeleteDocumentWithRevisionCheck()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');

        
        // Set some new values on the attributes and include the revision in the _rev attribute
        // This should result in a successfull update
        $document->set('someAttribute','someValue2');
        $document->set('someOtherAttribute','someOtherValue2');
        $document->set('_rev',$resultingDocument->getRevision());

        $result = $documentHandler->update($document, 'error');

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue2'));
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue2'));
        
        // Set some new values on the attributes and include a fake revision in the _rev attribute
        // This should result in a failure to update
        $document->set('someAttribute','someValue3');
        $document->set('someOtherAttribute','someOtherValue3');
        $document->set('_rev',$resultingDocument->getRevision()-1000);

        try {
                 $result = $documentHandler->update($document, 'error');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }

        $this->assertInstanceOf('Exception', $e);
        $this->assertTrue($e->getMessage() == 'HTTP/1.1 412 Precondition Failed');
        $resultingDocument1 = $documentHandler->get($this->collection->getId(), $documentId);
        
        $this->assertTrue(true === ($resultingDocument1->someAttribute == 'someValue2'));
        $this->assertTrue(true === ($resultingDocument1->someOtherAttribute == 'someOtherValue2'));
        unset ($e);
        
        $document = Document::createFromArray(array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue3'));
        $document->setInternalId($this->collection->getId().'/'.$documentId);
        // Set some new values on the attributes and  _rev attribute to NULL
        // This should result in a successfull update
        try {
                 $result = $documentHandler->update($document, 'error');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $resultingDocument2 = $documentHandler->get($this->collection->getId(), $documentId);
        
        $this->assertTrue(true === ($resultingDocument2->someAttribute == 'someValue3'));
        $this->assertTrue(true === ($resultingDocument2->someOtherAttribute == 'someOtherValue3'));

        // Set some new values on the attributes and include the revision in the _rev attribute
        // this is only to update the doc and get a new revision for thesting the delete method below
        // This should result in a successfull update
        $document->set('someAttribute','someValue2');
        $document->set('someOtherAttribute','someOtherValue2');
        $document->set('_rev',$resultingDocument2->getRevision());

        $result = $documentHandler->update($document, 'error');

        $this->assertTrue($result);
        $resultingDocument3 = $documentHandler->get($this->collection->getId(), $documentId);
        
        $this->assertTrue(true === ($resultingDocument3->someAttribute == 'someValue2'));
        $this->assertTrue(true === ($resultingDocument3->someOtherAttribute == 'someOtherValue2'));

       
        $e=null;
        try {
                  $response = $documentHandler->delete($resultingDocument, "error");
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        
        $this->assertInstanceOf('Exception', $e, "Delete should have raised an exception here");
        $this->assertTrue($e->getMessage() == 'HTTP/1.1 412 Precondition Failed');
        unset ($e);
        
      
        $response = $documentHandler->delete($resultingDocument3, "error");
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test to set some attributes and get all attributes of the document through getAll()
     * Also testing to optionally get internal attributes _id and _rev 
     */
    public function testGetAll()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentHandler->add($this->collection->getId(), $document);

        $result = $document->getAll();

        $this->assertTrue(true === ($result['someAttribute'] == 'someValue'));
        $this->assertTrue(true === ($result['someOtherAttribute'] == 'someOtherValue'));
        
        $result = $document->getAll(true);
        $this->assertArrayHasKey('_id', $result);
        $this->assertArrayHasKey('_rev', $result);
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
