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
        $isoKey=iconv("UTF-8","ISO-8859-1//TRANSLIT","someWrongEncododedAttribute");
        $isoValue=iconv("UTF-8","ISO-8859-1//TRANSLIT","someWrongEncodedValueü");
        
        $document = Document::createFromArray(array( $isoKey=> $isoValue, 'someOtherAttribute' => 'someOtherValue'));
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
     * test for creation, get, and delete of a document given its settings through createFromArray()
     */
    public function testCreateDocumentWithCreateFromArrayGetAndDeleteDocument()
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
    public function testCreateDocumentWithCreateFromArrayGetbyExampleAndDeleteDocument()
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
     * test for creation, get by example, and delete of a document given its settings through createFromArray()
     */
    public function testCreateDocumentWithCreateFromArrayGetFirstExampleAndDeleteDocument()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $resultingDocument = $this->collectionHandler->firstExample($this->collection->getId(), $document);
        $this->assertInstanceOf('triagens\ArangoDb\Document', $resultingDocument);

        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue'));
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue'));

        $response = $documentHandler->delete($document);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for updating a document using update()
     */
    public function testUpdateDocument()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $patchDocument =  new \triagens\ArangoDb\Document();
        $patchDocument->set('_id',$document->getHandle());
        $patchDocument->set('_rev',$document->getRevision());
        $patchDocument->set('someOtherAttribute', 'someOtherValue2');
        $result = $documentHandler->update($patchDocument);

        $this->assertTrue($result);
        
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue'), 'Should be :someValue, is: '.$resultingDocument->someAttribute);
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue2'), 'Should be :someOtherValue2, is: '.$resultingDocument->someOtherAttribute);
        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue(true === $response, 'Delete should return true!');
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

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $patchDocument =  new \triagens\ArangoDb\Document();
        $patchDocument->set('_id',$document->getHandle());
        $patchDocument->set('_rev',$document->getRevision());
        
        // inject wrong encoding       
        $isoValue=iconv("UTF-8","ISO-8859-1//TRANSLIT","someWrongEncodedValueü");
        
        $patchDocument->set('someOtherAttribute', $isoValue);
        $result = $documentHandler->update($patchDocument);

        $this->assertTrue($result);
        
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue'), 'Should be :someValue, is: '.$resultingDocument->someAttribute);
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue2'), 'Should be :someOtherValue2, is: '.$resultingDocument->someOtherAttribute);
        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for updating a document using update()
     */
    public function testUpdateDocumentDontKeepNull()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $patchDocument =  new \triagens\ArangoDb\Document();
        $patchDocument->set('_id',$document->getHandle());
        $patchDocument->set('_rev',$document->getRevision());
        $patchDocument->set('someAttribute', null);
        $patchDocument->set('someOtherAttribute', 'someOtherValue2');
        $result = $documentHandler->update($patchDocument,array("keepNull"=>false));

        $this->assertTrue($result);
        
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);
        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == null), 'Should be : null, is: '.$resultingDocument->someAttribute);
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue2'), 'Should be :someOtherValue2, is: '.$resultingDocument->someOtherAttribute);
        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }
    
    
    /**
     * test for replacing a document using replace()
     */
    public function testReplaceDocument()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $document->set('someAttribute','someValue2');
        $document->set('someOtherAttribute','someOtherValue2');
        $result = $documentHandler->replace($document);

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue2'), 'Should be :someValue2, is: '.$resultingDocument->someAttribute);
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue2'), 'Should be :someOtherValue2, is: '.$resultingDocument->someOtherAttribute);

        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue(true === $response, 'Delete should return true!');
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

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        // inject wrong encoding       
        $isoKey=iconv("UTF-8","ISO-8859-1//TRANSLIT","someWrongEncododedAttribute");
        $isoValue=iconv("UTF-8","ISO-8859-1//TRANSLIT","someWrongEncodedValueü");
        
        $document->set($isoKey, $isoValue);
        $document->set('someOtherAttribute','someOtherValue2');
        $result = $documentHandler->replace($document);

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue2'), 'Should be :someValue2, is: '.$resultingDocument->someAttribute);
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue2'), 'Should be :someOtherValue2, is: '.$resultingDocument->someOtherAttribute);

        $response = $documentHandler->delete($resultingDocument);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for deletion of a document with deleteById() not giving the revision
     */
    public function testDeleteDocumentWithDeleteByIdWithoutRevision()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $document->set('someAttribute','someValue2');
        $document->set('someOtherAttribute','someOtherValue2');
        $result = $documentHandler->replace($document);

        $this->assertTrue($result);
        $resultingDocument = $documentHandler->get($this->collection->getId(), $documentId);

        $this->assertObjectHasAttribute('_id', $resultingDocument, '_id field should exist, empty or with an id');
        
        $this->assertTrue(true === ($resultingDocument->someAttribute == 'someValue2'));
        $this->assertTrue(true === ($resultingDocument->someOtherAttribute == 'someOtherValue2'));

        $response = $documentHandler->deleteById($this->collection->getId(), $documentId);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for deletion of a document with deleteById() given the revision
     */
    public function testDeleteDocumentWithDeleteByIdWithRevisionAndPolicyIsError()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $revision=$document->getRevision();
          try{
             $documentHandler->deleteById($this->collection->getId(), $documentId, $revision-1000, 'error');
          }catch(\triagens\ArangoDb\ServerException $e){
            $this->assertTrue(true);
          }
        
        $response = $documentHandler->deleteById($this->collection->getId(), $documentId, $revision, 'error');
        $this->assertTrue(true === $response, 'deleteById() should return true! (because correct revision given)');
    }


    /**
     * test for deletion of a document with deleteById() given the revision
     */
    public function testDeleteDocumentWithDeleteByIdWithRevisionAndPolicyIsLast()
    {
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($this->collection->getId(), $document);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');

        $revision=$document->getRevision();

        $response=$documentHandler->deleteById($this->collection->getId(), $documentId, $revision-1000, 'last');
        $this->assertTrue(true === $response, 'deleteById() should return true! (because policy  is "last write wins")');
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
        $patchDocument =  new \triagens\ArangoDb\Document();
        $patchDocument->set('someOtherAttribute','someOtherValue3');
        $patchDocument->set('_rev',$resultingDocument->getRevision()-1000);

        try {
                 $result = $documentHandler->update($document, 'error');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }

        $this->assertInstanceOf('Exception', $e);
        $this->assertTrue($e->getMessage() == 'HTTP/1.1 412 Precondition Failed');
        $resultingDocument1 = $documentHandler->get($this->collection->getId(), $documentId);
        
        $this->assertTrue(true === ($resultingDocument1->someAttribute == 'someValue2'), "This value should not have changed using UPDATE() - this is the behavior of REPLACE()");
        $this->assertTrue(true === ($resultingDocument1->someOtherAttribute == 'someOtherValue2'));
        unset ($e);
        
        $document = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue3'));
        $document->setInternalId($this->collection->getId().'/'.$documentId);
        // Set some new values on the attributes and  _rev attribute to NULL
        // This should result in a successfull update
        try {
                 $result = $documentHandler->update($document, 'error');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $resultingDocument2 = $documentHandler->get($this->collection->getId(), $documentId);
        
        $this->assertTrue(true === ($resultingDocument2->someOtherAttribute == 'someOtherValue3'));

        // Set some new values on the attributes and include the revision in the _rev attribute
        // this is only to update the doc and get a new revision for thesting the delete method below
        // This should result in a successfull update
        $document->set('someAttribute','someValue');
        $document->set('someOtherAttribute','someOtherValue2');
        $document->set('_rev',$resultingDocument2->getRevision());

        $result = $documentHandler->update($document, 'error');

        $this->assertTrue($result);
        $resultingDocument3 = $documentHandler->get($this->collection->getId(), $documentId);
        
        $this->assertTrue(true === ($resultingDocument3->someAttribute == 'someValue'));
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
     * test for creation, update, get, and delete having update and delete doing revision checks.
     */
    public function testCreateReplaceGetAndDeleteDocumentWithRevisionCheck()
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
        // This should result in a successful update
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

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue', 'someThirdAttribute' => 'someThirdValue'));
        $documentHandler->add($this->collection->getId(), $document);
        
        // set hidden fields
        $document->setHiddenAttributes(array('someThirdAttribute'));
        
        $result = $document->getAll();

        $this->assertTrue(true === ($result['someAttribute'] == 'someValue'));
        $this->assertTrue(true === ($result['someOtherAttribute'] == 'someOtherValue'));
        
        // Check if the hidden field is actually hidden...
        $this->assertArrayNotHasKey('someThirdAttribute', $result);

        $result = $document->getAll(true);
        $this->assertArrayHasKey('_id', $result);
        $this->assertArrayHasKey('_rev', $result);
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
