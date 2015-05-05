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
     * Try to create and delete a document with several keys
     */
    public function testCreateAndDeleteDocumentWithSeveralKeys()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $keys = array(
            "_",
            "foo", 
            "bar", 
            "bar:bar", 
            "baz", 
            "1", 
            "0", 
            "a-b-c", 
            "a:b", 
            "this-is-a-test", 
            "FOO", 
            "BAR", 
            "Bar", 
            "bAr"
        );
        
        $adminHandler = new AdminHandler($this->connection);
        $version = preg_replace("/-[a-z0-9]+$/", "", $adminHandler->getServerVersion());

        if (version_compare($version, '2.6.0') >= 0) {
            // 2.6 will also allow the following document keys, while 2.5 will not
            $keys[] = ".";
            $keys[] = ":";
            $keys[] = "@";
            $keys[] = "-.:@";
            $keys[] = "foo@bar.baz.com";
            $keys[] = ":.foo@bar-bar_bar.baz.com.:";
        }

        foreach ($keys as $key) {
          $document        = new Document();
          $document->someAttribute = 'someValue';
          $document->set('_key', $key);
          $documentId = $documentHandler->add($collection->getName(), $document);

          $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

          $resultingAttribute = $resultingDocument->someAttribute;
          $resultingKey       = $resultingDocument->getKey();
          $this->assertTrue(
               $resultingAttribute === 'someValue',
               'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute
          );
          $this->assertTrue(
               $resultingKey === $key,
               'Resulting Attribute should be "someValue". It\'s :' . $resultingKey
          );

          $documentHandler->delete($document);
        }
    }
    
    
    /**
     * Try to create a document with invalid keys
     */
    public function testCreateDocumentWithInvalidKeys()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $keys = array(
            "", 
            " ", 
            " bar", 
            "bar ", 
            "/", 
            "?", 
            "abcdef gh", 
            "abcxde&", 
            "mötörhead", 
            "this-key-will-be-too-long-to-be-processed-successfully-would-you-agree-with-me-sure-you-will-because-there-is-a-limit-of-254-characters-per-key-which-this-string-will-not-conform-to-if-you-are-still-reading-this-you-should-probably-do-something-else-right-now-REALLY", 
            "#", 
            "|",
            "ü",
            "~",
            "<>",
            "µµ"
        );

        foreach ($keys as $key) {
          $document = new Document();
          $document->someAttribute = 'someValue';

          $caught = false;
          try {
            $document->set('_key', $key);
          } catch (\triagens\ArangoDb\ClientException $exception) {
            $caught = true;
          }

          $this->assertTrue($caught, "expecting exception to be thrown for key ". $key);
        }
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


    public function testHasDocumentReturnsFalseIfDocumentDoesNotExist()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);
        $this->assertFalse($documentHandler->has($collection->getId(), 'just_a_stupid_document_id_which_does_not_exist'));
    }


    public function testHasDocumentReturnsTrueIfDocumentExists()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        // create doc first
        $document        = new Document();
        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->add($collection->getId(), $document);

        $this->assertTrue($this->collectionHandler->has($collection->getId(), $documentId));
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
