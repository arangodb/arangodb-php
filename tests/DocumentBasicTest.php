<?php
/**
 * ArangoDB PHP client testsuite
 * File: documentbasictest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

class DocumentBasicTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->collection        = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection);
    }


    /**
     * Test if Document and DocumentHandler instances can be initialized
     */
    public function testInitializeDocument()
    {
        $connection              = $this->connection;
        $this->collection        = new \triagens\ArangoDb\Collection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $document                = new \triagens\ArangoDb\Document();
        $this->assertInstanceOf('triagens\ArangoDb\Document', $document);
        $this->assertInstanceOf('triagens\ArangoDb\Document', $document);
        unset ($document);
    }


    /**
     * Try to create and delete a document
     */
    public function testCreateAndDeleteDocument()
    {
        $connection        = $this->connection;
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $document          = new \triagens\ArangoDb\Document();
        $documentHandler   = new \triagens\ArangoDb\DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->add($collection->getId(), $document);

        $resultingDocument = $documentHandler->get($collection->getId(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        $this->assertTrue(
            $resultingAttribute === 'someValue',
            'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute
        );

        $response = $documentHandler->delete($document);
    }


    /**
     * Try to create and delete a document using a defined key
     */
    public function testCreateAndDeleteDocumentUsingDefinedKey()
    {
        $connection        = $this->connection;
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $document          = new \triagens\ArangoDb\Document();
        $documentHandler   = new \triagens\ArangoDb\DocumentHandler($connection);

        $document->someAttribute = 'someValue';
        $document->set('_key', 'frank01');
        $documentId = $documentHandler->add($collection->getName(), $document);

        $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        $this->assertTrue(
            $resultingAttribute === 'someValue',
            'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute
        );

        $response = $documentHandler->delete($document);
    }


    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->documentHandler);
        unset($this->document);
        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
