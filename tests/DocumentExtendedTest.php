<?php
/**
 * ArangoDB PHP client testsuite
 * File: DocumentExtendedTest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

class DocumentExtendedTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->collection        = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection);
        $this->documentHandler = new DocumentHandler($this->connection);
    }


    /**
     * test for creation of document with non utf encoding. This tests for failure of such an action.
     * We expect an exception here:
     *
     * @expectedException triagens\ArangoDb\ClientException
     */
    public function testCreateDocumentWithWrongEncoding()
    {
        $documentHandler = $this->documentHandler;
        $isoKey          = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "someWrongEncododedAttribute");
        $isoValue        = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "someWrongEncodedValueü");

        $document   = Document::createFromArray(array($isoKey => $isoValue, 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        $this->assertTrue($resultingDocument->someAttribute == 'someValue');
        $this->assertTrue($resultingDocument->someOtherAttribute == 'someOtherValue');

        $response = $documentHandler->delete($document);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, get, and delete of a document given its settings through createFromArray()
     */
    public function testCreateDocumentWithCreateFromArrayGetAndDeleteDocument()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        $this->assertTrue($resultingDocument->someAttribute == 'someValue');
        $this->assertTrue($resultingDocument->someOtherAttribute == 'someOtherValue');

        $response = $documentHandler->delete($document);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, get by example, and delete of a document given its settings through createFromArray()
     */
    public function testCreateDocumentWithCreateFromArrayGetbyExampleAndDeleteDocument()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $cursor = $documentHandler->getByExample($this->collection->getId(), $document);

        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = $cursor->current();

        $this->assertTrue($resultingDocument->someAttribute == 'someValue');
        $this->assertTrue($resultingDocument->someOtherAttribute == 'someOtherValue');

        $response = $documentHandler->delete($document);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, get by example, and delete of a document given its settings through createFromArray()
     */
    public function testCreateDocumentWithCreateFromArrayGetbyExampleWithOptionsAndDeleteDocument()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document, array('waitForSync' => true));

        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute2' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($this->collection->getId(), $document2, array('waitForSync' => true));

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');
        $this->assertTrue(is_numeric($documentId2), 'Did not return an id!');

        $exampleDocument = Document::createFromArray(
            array('someAttribute' => 'someValue')
        );

        $cursor = $documentHandler->getByExample(
            $this->collection->getId(),
            $exampleDocument,
            array('batchSize' => 1, 'skip' => 0, 'limit' => 2)
        );

        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        unset($resultingDocument);
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        $this->assertTrue(
            ($resultingDocument[0]->someAttribute == 'someValue'),
            'Document returned did not contain expected data.'
        );

        $this->assertTrue(
            ($resultingDocument[1]->someAttribute == 'someValue'),
            'Document returned did not contain expected data.'
        );

        $this->assertTrue(count($resultingDocument) == 2, 'Should be 2, was: ' . count($resultingDocument));


        $cursor = $documentHandler->getByExample(
            $this->collection->getId(),
            $exampleDocument,
            array('batchSize' => 1, 'skip' => 1)
        );

        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        unset($resultingDocument);
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        $this->assertTrue(
            ($resultingDocument[0]->someAttribute == 'someValue'),
            'Document returned did not contain expected data.'
        );

        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));


        $cursor = $documentHandler->getByExample(
            $this->collection->getId(),
            $exampleDocument,
            array('batchSize' => 1, 'limit' => 1)
        );

        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        unset($resultingDocument);
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
            ($resultingDocument[0]->someAttribute == 'someValue'),
            'Document returned did not contain expected data.'
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));


        $response = $documentHandler->delete($document);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, get by example, and delete of a document given its settings through createFromArray()
     */
    public function testCreateDocumentWithCreateFromArrayGetFirstExampleAndDeleteDocument()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $resultingDocument = $this->collectionHandler->firstExample($this->collection->getId(), $document);
        $this->assertInstanceOf('triagens\ArangoDb\Document', $resultingDocument);

        $this->assertTrue($resultingDocument->someAttribute == 'someValue');
        $this->assertTrue($resultingDocument->someOtherAttribute == 'someOtherValue');

        $response = $documentHandler->delete($document);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for updating a document using update()
     */
    public function testUpdateDocument()
    {
        $documentHandler = $this->documentHandler;

        $document          = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId        = $documentHandler->add($this->collection->getId(), $document);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $patchDocument = new \triagens\ArangoDb\Document();
        $patchDocument->set('_id', $document->getHandle());
        $patchDocument->set('_rev', $document->getRevision());
        $patchDocument->set('someOtherAttribute', 'someOtherValue2');
        $result = $documentHandler->update($patchDocument);

        $this->assertTrue($result);

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingDocument->someAttribute == 'someValue'),
            'Should be :someValue, is: ' . $resultingDocument->someAttribute
        );
        $this->assertTrue(
            ($resultingDocument->someOtherAttribute == 'someOtherValue2'),
            'Should be :someOtherValue2, is: ' . $resultingDocument->someOtherAttribute
        );
        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for updating a document using update() with wrong encoding
     * We expect an exception here:
     *
     * @expectedException triagens\ArangoDb\ClientException
     */
    public function testUpdateDocumentWithWrongEncoding()
    {
        $documentHandler = $this->documentHandler;

        $document          = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId        = $documentHandler->add($this->collection->getId(), $document);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $patchDocument = new \triagens\ArangoDb\Document();
        $patchDocument->set('_id', $document->getHandle());
        $patchDocument->set('_rev', $document->getRevision());

        // inject wrong encoding       
        $isoValue = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "someWrongEncodedValueü");

        $patchDocument->set('someOtherAttribute', $isoValue);
        $result = $documentHandler->update($patchDocument);

        $this->assertTrue($result);

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingDocument->someAttribute == 'someValue'),
            'Should be :someValue, is: ' . $resultingDocument->someAttribute
        );
        $this->assertTrue(
            ($resultingDocument->someOtherAttribute == 'someOtherValue2'),
            'Should be :someOtherValue2, is: ' . $resultingDocument->someOtherAttribute
        );
        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for updating a document using update()
     */
    public function testUpdateDocumentDontKeepNull()
    {
        $documentHandler = $this->documentHandler;

        $document          = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId        = $documentHandler->add($this->collection->getId(), $document);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $patchDocument = new \triagens\ArangoDb\Document();
        $patchDocument->set('_id', $document->getHandle());
        $patchDocument->set('_rev', $document->getRevision());
        $patchDocument->set('someAttribute', null);
        $patchDocument->set('someOtherAttribute', 'someOtherValue2');
        $result = $documentHandler->update($patchDocument, array("keepNull" => false));

        $this->assertTrue($result);

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingDocument->someAttribute == null),
            'Should be : null, is: ' . $resultingDocument->someAttribute
        );
        $this->assertTrue(
            ($resultingDocument->someOtherAttribute == 'someOtherValue2'),
            'Should be :someOtherValue2, is: ' . $resultingDocument->someOtherAttribute
        );
        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for replacing a document using replace()
     */
    public function testReplaceDocument()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $document->set('someAttribute', 'someValue2');
        $document->set('someOtherAttribute', 'someOtherValue2');
        $result = $documentHandler->replace($document);

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingDocument->someAttribute == 'someValue2'),
            'Should be :someValue2, is: ' . $resultingDocument->someAttribute
        );
        $this->assertTrue(
            ($resultingDocument->someOtherAttribute == 'someOtherValue2'),
            'Should be :someOtherValue2, is: ' . $resultingDocument->someOtherAttribute
        );

        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for replacing a document using replace() with wrong encoding
     * We expect an exception here:
     *
     * @expectedException triagens\ArangoDb\ClientException
     */
    public function testReplaceDocumentWithWrongEncoding()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        // inject wrong encoding       
        $isoKey   = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "someWrongEncododedAttribute");
        $isoValue = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "someWrongEncodedValueü");

        $document->set($isoKey, $isoValue);
        $document->set('someOtherAttribute', 'someOtherValue2');
        $result = $documentHandler->replace($document);

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingDocument->someAttribute == 'someValue2'),
            'Should be :someValue2, is: ' . $resultingDocument->someAttribute
        );
        $this->assertTrue(
            ($resultingDocument->someOtherAttribute == 'someOtherValue2'),
            'Should be :someOtherValue2, is: ' . $resultingDocument->someOtherAttribute
        );

        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for deletion of a document with deleteById() not giving the revision
     */
    public function testDeleteDocumentWithDeleteByIdWithoutRevision()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $document->set('someAttribute', 'someValue2');
        $document->set('someOtherAttribute', 'someOtherValue2');
        $result = $documentHandler->replace($document);

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');

        $this->assertTrue($resultingDocument->someAttribute == 'someValue2');
        $this->assertTrue($resultingDocument->someOtherAttribute == 'someOtherValue2');

        $response = $documentHandler->deleteById($this->collection->getId(), $documentId);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for deletion of a document with deleteById() given the revision
     */
    public function testDeleteDocumentWithDeleteByIdWithRevisionAndPolicyIsError()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $revision = $document->getRevision();
        try {
            $documentHandler->deleteById($this->collection->getId(), $documentId, $revision - 1000, 'error');
        } catch (\triagens\ArangoDb\ServerException $e) {
            $this->assertTrue(true);
        }

        $response = $documentHandler->deleteById($this->collection->getId(), $documentId, $revision, 'error');
        $this->assertTrue($response, 'deleteById() should return true! (because correct revision given)');
    }


    /**
     * test for deletion of a document with deleteById() given the revision
     */
    public function testDeleteDocumentWithDeleteByIdWithRevisionAndPolicyIsLast()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $revision = $document->getRevision();

        $response = $documentHandler->deleteById($this->collection->getId(), $documentId, $revision - 1000, 'last');
        $this->assertTrue(
            $response,
            'deleteById() should return true! (because policy  is "last write wins")'
        );
    }


    /**
     * test for creation, update, get, and delete having update and delete doing revision checks.
     */
    public function testCreateUpdateGetAndDeleteDocumentWithRevisionCheck()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');


        // Set some new values on the attributes and include the revision in the _rev attribute
        // This should result in a successfull update
        $document->set('someAttribute', 'someValue2');
        $document->set('someOtherAttribute', 'someOtherValue2');
        $document->set('_rev', $resultingDocument->getRevision());

        $result = $documentHandler->update($document, 'error');

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertTrue($resultingDocument->someAttribute == 'someValue2');
        $this->assertTrue($resultingDocument->someOtherAttribute == 'someOtherValue2');

        // Set some new values on the attributes and include a fake revision in the _rev attribute
        // This should result in a failure to update
        $patchDocument = new \triagens\ArangoDb\Document();
        $patchDocument->set('someOtherAttribute', 'someOtherValue3');
        $patchDocument->set('_rev', $resultingDocument->getRevision() - 1000);

        try {
            $result = $documentHandler->update($document, 'error');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }

        $this->assertInstanceOf('Exception', $e);
        $this->assertTrue($e->getMessage() == 'HTTP/1.1 412 Precondition Failed');
        $resultingDocument1 = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertTrue(
            ($resultingDocument1->someAttribute == 'someValue2'),
            "This value should not have changed using UPDATE() - this is the behavior of REPLACE()"
        );
        $this->assertTrue($resultingDocument1->someOtherAttribute == 'someOtherValue2');
        unset ($e);

        $document = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue3'));
        $document->setInternalId($this->collection->getId() . '/' . $documentId);
        // Set some new values on the attributes and  _rev attribute to NULL
        // This should result in a successfull update
        try {
            $result = $documentHandler->update($document, 'error');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $resultingDocument2 = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertTrue($resultingDocument2->someOtherAttribute == 'someOtherValue3');

        // Set some new values on the attributes and include the revision in the _rev attribute
        // this is only to update the doc and get a new revision for thesting the delete method below
        // This should result in a successfull update
        $document->set('someAttribute', 'someValue');
        $document->set('someOtherAttribute', 'someOtherValue2');
        $document->set('_rev', $resultingDocument2->getRevision());

        $result = $documentHandler->update($document, 'error');

        $this->assertTrue($result);
        $resultingDocument3 = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertTrue($resultingDocument3->someAttribute == 'someValue');
        $this->assertTrue($resultingDocument3->someOtherAttribute == 'someOtherValue2');

        $e = null;
        try {
            $response = $documentHandler->delete($resultingDocument, "error");
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }

        $this->assertInstanceOf('Exception', $e, "Delete should have raised an exception here");
        $this->assertTrue($e->getMessage() == 'HTTP/1.1 412 Precondition Failed');
        unset ($e);

        $response = $documentHandler->delete($resultingDocument3, "error");
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, update, get, and delete having update and delete doing revision checks.
     */
    public function testCreateReplaceGetAndDeleteDocumentWithRevisionCheck()
    {
        $documentHandler = $this->documentHandler;

        $document   = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');


        // Set some new values on the attributes and include the revision in the _rev attribute
        // This should result in a successfull update
        $document->set('someAttribute', 'someValue2');
        $document->set('someOtherAttribute', 'someOtherValue2');
        $document->set('_rev', $resultingDocument->getRevision());

        $result = $documentHandler->update($document, 'error');

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertTrue($resultingDocument->someAttribute == 'someValue2');
        $this->assertTrue($resultingDocument->someOtherAttribute == 'someOtherValue2');

        // Set some new values on the attributes and include a fake revision in the _rev attribute
        // This should result in a failure to update
        $document->set('someAttribute', 'someValue3');
        $document->set('someOtherAttribute', 'someOtherValue3');
        $document->set('_rev', $resultingDocument->getRevision() - 1000);

        try {
            $result = $documentHandler->update($document, 'error');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }

        $this->assertInstanceOf('Exception', $e);
        $this->assertTrue($e->getMessage() == 'HTTP/1.1 412 Precondition Failed');
        $resultingDocument1 = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertTrue($resultingDocument1->someAttribute == 'someValue2');
        $this->assertTrue($resultingDocument1->someOtherAttribute == 'someOtherValue2');
        unset ($e);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue3')
        );
        $document->setInternalId($this->collection->getId() . '/' . $documentId);
        // Set some new values on the attributes and  _rev attribute to NULL
        // This should result in a successfull update
        try {
            $result = $documentHandler->update($document, 'error');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $resultingDocument2 = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertTrue($resultingDocument2->someAttribute == 'someValue3');
        $this->assertTrue($resultingDocument2->someOtherAttribute == 'someOtherValue3');

        // Set some new values on the attributes and include the revision in the _rev attribute
        // this is only to update the doc and get a new revision for thesting the delete method below
        // This should result in a successful update
        $document->set('someAttribute', 'someValue2');
        $document->set('someOtherAttribute', 'someOtherValue2');
        $document->set('_rev', $resultingDocument2->getRevision());

        $result = $documentHandler->update($document, 'error');

        $this->assertTrue($result);
        $resultingDocument3 = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertTrue($resultingDocument3->someAttribute == 'someValue2');
        $this->assertTrue($resultingDocument3->someOtherAttribute == 'someOtherValue2');

        $e = null;
        try {
            $response = $documentHandler->delete($resultingDocument, "error");
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }

        $this->assertInstanceOf('Exception', $e, "Delete should have raised an exception here");
        $this->assertTrue($e->getMessage() == 'HTTP/1.1 412 Precondition Failed');
        unset ($e);

        $response = $documentHandler->delete($resultingDocument3, "error");
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test to set some attributes and get all attributes of the document through getAll()
     * Also testing to optionally get internal attributes _id and _rev
     */
    public function testGetAll()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(
            array(
                 'someAttribute'      => 'someValue',
                 'someOtherAttribute' => 'someOtherValue',
                 'someThirdAttribute' => 'someThirdValue'
            )
        );
        $documentHandler->add($this->collection->getId(), $document);

        // set hidden fields
        $document->setHiddenAttributes(array('someThirdAttribute'));

        $result = $document->getAll();

        $this->assertTrue($result['someAttribute'] == 'someValue');
        $this->assertTrue($result['someOtherAttribute'] == 'someOtherValue');

        // Check if the hidden field is actually hidden...
        $this->assertArrayNotHasKey('someThirdAttribute', $result);

        $result = $document->getAll(true);
        $this->assertArrayHasKey('_id', $result);
        $this->assertArrayHasKey('_rev', $result);
    }


    /**
     * Test for correct exception codes if nonexistant objects are tried to be gotten, replaced, updated or removed
     */
    public function testGetReplaceUpdateAndRemoveOnNonExistantObjects()
    {
        // Setup objects
        $documentHandler = $this->documentHandler;
        $document        = Document::createFromArray(
            array(
                 'someAttribute'      => 'someValue',
                 'someOtherAttribute' => 'someOtherValue',
                 'someThirdAttribute' => 'someThirdValue'
            )
        );


        // Try to get a non-existent document out of a nonexistent collection
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $documentHandler->get('nonExistantCollection', 'nonexistantId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to get a non-existent document out of an existent collection
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $documentHandler->get($this->collection->getId(), 'nonexistantId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to update a non-existent document
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $documentHandler->updateById($this->collection->getId(), 'nonexistantId', $document);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to replace a non-existent document
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $documentHandler->replaceById($this->collection->getId(), 'nonexistantId', $document);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to remove a non-existent document
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $documentHandler->removeById($this->collection->getId(), 'nonexistantId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
    }

    /**
     * Test for correct exception codes if nonexistant objects are tried to be gotten, replaced, updated or removed
     */
    public function testStoreNewDocumentThenReplace()
    {
    	//Setup
    	$document = new Document();
    	$document->set('data', 'this is some test data');
    	
    	//Check that the document is new
    	$this->assertTrue($document->getIsNew(), 'Document is not marked as new when it is not.');
    	
    	$documentHandler = $this->documentHandler;

    	//Store the document
    	$id = $documentHandler->store($document, $this->collection->getId());
    	
    	$rev = $document->getRevision();
    	
    	$this->assertTrue($id == $document->getId(), 'Returned ID does not match the one in the document');
    	$this->assertTrue($document->get('data') == 'this is some test data', 'Data has been modified for some reason.');

    	//Check that the document is not new
    	$this->assertTrue(!$document->getIsNew(), 'Document is marked as new when it is not.');
    	
    	//Update the document and save again
    	$document->set('data', 'this is some different data');
    	$document->set('favorite_sport', 'hockey');
    	$documentHandler->store($document);
    	
    	//Check that the id remains the same
    	$this->assertTrue($document->getId() == $id, 'ID of updated document does not match the initial ID.');

    	//Retrieve a copy of the document from the server
    	$document = $documentHandler->get($this->collection->getId(), $id);
    	
    	//Assert that it is not new
    	$this->assertTrue(!$document->getIsNew(), 'Document is marked as new when it is not.');
    	
    	//Assert the id is the same
    	$this->assertTrue($document->getId() == $id, 'ID of retrieved document does not match expected ID');
    	
    	//Assert new data has been saved
    	$this->assertTrue($document->get('favorite_sport') == 'hockey', 'Retrieved data does not match.');
    	
    	$this->assertTrue($document->getRevision() != $rev, 'Revision matches when it is not suppose to.');
    }

    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
