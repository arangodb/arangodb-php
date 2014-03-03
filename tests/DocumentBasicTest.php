<?php
/**
 * ArangoDB PHP client testsuite
 * File: DocumentBasicTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;


/**
 * Class DocumentBasicTest
 *
 * @property Connection        $connection
 * @property Collection        $collection
 * @property Collection        $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler   $documentHandler
 *
 * @package triagens\ArangoDb
 */
class DocumentBasicTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        $this->collection        = new Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection);
    }


    /**
     * Test if Document and DocumentHandler instances can be initialized
     */
    public function testInitializeDocument()
    {
        $this->collection        = new Collection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        $document                = new Document();
        $this->assertInstanceOf('triagens\ArangoDb\Document', $document);
        $this->assertInstanceOf('triagens\ArangoDb\Document', $document);
        unset ($document);
    }


    /**
     * Try to create and delete a document
     */
    public function testCreateAndDeleteDocument()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->add($collection->getId(), $document);

        $resultingDocument = $documentHandler->get($collection->getId(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        $this->assertTrue(
             $resultingAttribute === 'someValue',
             'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute
        );

        $documentHandler->delete($document);
    }


    /**
     * Try to create and delete a document using a defined key
     */
    public function testCreateAndDeleteDocumentUsingDefinedKey()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document->someAttribute = 'someValue';
        $document->set('_key', 'frank01');
        $documentId = $documentHandler->add($collection->getName(), $document);

        $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        $resultingKey       = $resultingDocument->getKey();
        $this->assertTrue(
             $resultingAttribute === 'someValue',
             'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute
        );
        $this->assertTrue(
             $resultingKey === 'frank01',
             'Resulting Attribute should be "someValue". It\'s :' . $resultingKey
        );


        $documentHandler->delete($document);
    }


    /**
     * Try to create and delete a document
     */
    public function testCreateAndDeleteDocumentWithArray()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $documentArray = array('someAttribute' => 'someValue');

        $documentId = $documentHandler->save($collection->getId(), $documentArray);

        $resultingDocument = $documentHandler->get($collection->getId(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        $this->assertTrue(
             $resultingAttribute === 'someValue',
             'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute
        );

        $documentHandler->deleteById($collection->getName(), $documentId);
    }


    /**
     * Try to create, get and delete a document using the revision-
     */
    public function testCreateGetAndDeleteDocumentWithRevision()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $documentArray = array('someAttribute' => 'someValue');

        $documentId = $documentHandler->save($collection->getId(), $documentArray);

        $document = $documentHandler->get($collection->getId(), $documentId);

        /**
         * lets get the document in a wrong revision
         */
        try {
            $result412 = $documentHandler->get($collection->getId(), $documentId, array("ifMatch" => true, "revision" => 12345));
        } catch (\Exception $exception412) {
        }
        $this->assertEquals($exception412->getCode() , 412);

        try {
            $result304 = $documentHandler->get($collection->getId(), $documentId, array("ifMatch" => false, "revision" => $document->getRevision()));
        } catch (\Exception $exception304) {
        }
        $this->assertEquals($exception304->getMessage() , 'Document has not changed.');

        $resultingDocument = $documentHandler->get($collection->getId(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        $this->assertTrue(
            $resultingAttribute === 'someValue',
            'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute
        );

        $resultingDocument->set('someAttribute', 'someValue2');
        $resultingDocument->set('someOtherAttribute', 'someOtherValue2');
        $documentHandler->replace($resultingDocument);

        $oldRevision = $documentHandler->get($collection->getId(), $documentId,
            array("revision" => $resultingDocument->getRevision()));
        $this->assertEquals($oldRevision->getRevision(), $resultingDocument->getRevision());
        $documentHandler->deleteById($collection->getName(), $documentId);
    }

    /**
     * Try to create, head and delete a document
     */
    public function testCreateHeadAndDeleteDocumentWithRevision()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $documentArray = array('someAttribute' => 'someValue');

        $documentId = $documentHandler->save($collection->getId(), $documentArray);
        $document = $documentHandler->get($collection->getId(), $documentId);

        try {
            $documentHandler->getHead($collection->getId(), $documentId, "12345", true);
        } catch (\Exception $e412) {
        }

        $this->assertEquals($e412->getCode() , 412);

        try {
            $documentHandler->getHead($collection->getId(), "notExisting");
        } catch (\Exception $e404) {
        }

        $this->assertEquals($e404->getCode() , 404);


        $result304 = $documentHandler->getHead($collection->getId(), $documentId, $document->getRevision(), false);
        $this->assertEquals($result304["etag"] , '"' .$document->getRevision().'"');
        $this->assertEquals($result304["content-length"] , 0);
        $this->assertEquals($result304["httpCode"] , 304);

        $result200 = $documentHandler->getHead($collection->getId(), $documentId, $document->getRevision() , true);
        $this->assertEquals($result200["etag"] , '"' .$document->getRevision().'"');
        $this->assertNotEquals($result200["content-length"] , 0);
        $this->assertEquals($result200["httpCode"] , 200);

        $documentHandler->deleteById($collection->getName(), $documentId);
    }


    /**
     * Try to create and delete a document using a defined key
     */
    public function testCreateAndDeleteDocumentUsingDefinedKeyWithArrayAndSaveOnly()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $documentArray = array('someAttribute' => 'someValue', '_key' => 'frank01');
        $documentId    = $documentHandler->save($collection->getName(), $documentArray);

        $resultingDocument  = $documentHandler->get($collection->getName(), $documentId);
        $resultingAttribute = $resultingDocument->someAttribute;
        $resultingKey       = $resultingDocument->getKey();
        $this->assertTrue(
             $resultingAttribute === 'someValue',
             'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute
        );
        $this->assertTrue(
             $resultingKey === 'frank01',
             'Resulting Attribute should be "someValue". It\'s :' . $resultingKey
        );


        $documentHandler->deleteById($collection->getName(), $documentId);
    }


    public function tearDown()
    {
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
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
