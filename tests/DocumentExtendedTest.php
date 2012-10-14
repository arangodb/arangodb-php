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
     * test to set some attributes and get all attributes of the document through getAll()
     */
    public function testGetAll()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentHandler->add($this->collection->getId(), $document);

        $result = $document->getAll();

        $this->assertTrue(true === ($result['someAttribute'] == 'someValue'));
        $this->assertTrue(true === ($result['someOtherAttribute'] == 'someOtherValue'));
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
