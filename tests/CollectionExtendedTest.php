<?php
/**
 * ArangoDB PHP client testsuite
 * File: collectionextendedtest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDb;

class CollectionExtendedTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
        $this->collection = new \triagens\ArangoDb\Collection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->documentHandler = new \triagens\ArangoDb\DocumentHandler($this->connection);

    }

    /**
     * test for creation, get, and delete of a collection with waitForSync default value (no setting)
     */
    public function testCreateGetAndDeleteCollectionWithWaitForSyncDefault()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;

        $resultingAttribute = $collection->getWaitForSync();
        $this->assertTrue(null === $resultingAttribute, 'Default waitForSync in API should be NULL!');

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);
       

        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($name);

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for creation, getProperties, and delete of a volatile (in-memory-only) collection
     */
    public function testCreateGetAndDeleteVolatileCollection()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;

        $resultingAttribute = $collection->getIsVolatile();
        $this->assertTrue(NULL === $resultingAttribute, 'Default waitForSync in API should be NULL!');

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);
        $collection->setIsVolatile(true);


        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($name);

        $properties=$collectionHandler->getProperties($name);
        $this->assertTrue($properties->getIsVolatile() === true, '"isVolatile" should be true!');


        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for creation, getProperties, and delete of a volatile (in-memory-only) collection
     */
    public function testCreateGetAndDeleteSystemCollection()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;

        $resultingAttribute = $collection->getIsSystem();
        $this->assertTrue(NULL === $resultingAttribute, 'Default isSystem in API should be NULL!');

        $name = '_ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);
        $collection->setIsSystem(true);


        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($name);

        //todo: Cannot run this test, as the properties function does not return isSystem at this time.. revisit later
//        $properties=$collectionHandler->getProperties($name);
//        $this->assertTrue($properties->getIsSystem() === true, '"isSystem" should be true!');


        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for creation, rename, and delete of a collection 
     */
    public function testCreateRenameAndDeleteCollection()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;


        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);

        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($name);

        $response = $collectionHandler->rename($resultingCollection, 'ArangoDB_PHP_TestSuite_TestCollection_01_renamed');
        
        $resultingCollectionRenamed = $collectionHandler->get( 'ArangoDB_PHP_TestSuite_TestCollection_01_renamed');
        $newName=$resultingCollectionRenamed->getName();

        $this->assertTrue($newName == 'ArangoDB_PHP_TestSuite_TestCollection_01_renamed', 'Collection was not renamed!');
        $response = $collectionHandler->delete($resultingCollectionRenamed);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for creation, rename, and delete of a collection with wrong encoding
     * 
     * We expect an exception here:
     * 
     * @expectedException triagens\ArangoDb\ClientException
     * 
     */
    public function testCreateRenameAndDeleteCollectionWithWrongEncoding()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;


        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);

        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($name);

        // inject wrong encoding       
        $isoValue=iconv("UTF-8","ISO-8859-1//TRANSLIT","ArangoDB_PHP_TestSuite_TestCollection_01_renamedü");
        
        $response = $collectionHandler->rename($resultingCollection, $isoValue);

        
        $response = $collectionHandler->delete($resultingCollection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

    
    /**
     * test for creation, get, and delete of a collection with waitForSync set to true
     */
    public function testCreateGetAndDeleteCollectionWithWaitForSyncTrueAndJournalSizeSet()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $collection->setWaitForSync(true);
        $collection->setJournalSize(1024*1024*2);
        $resultingWaitForSyncAttribute = $collection->getWaitForSync();
        $resultingJournalSizeAttribute = $collection->getJournalSize();




        $this->assertTrue(true === $resultingWaitForSyncAttribute, 'WaitForSync should be true!');
        $this->assertTrue($resultingJournalSizeAttribute == 1024*1024*2, 'JournalSize should be 2MB!');

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);

        $response = $collectionHandler->add($collection);

        // here we check the collectionHandler->getProperties function
        $properties = $collectionHandler->getProperties($collection->getName());
        $this->assertObjectHasAttribute('_waitForSync', $properties, 'waiForSync field should exist, empty or with an id');
        $this->assertObjectHasAttribute('_journalSize', $properties, 'journalSize field should exist, empty or with an id');

        // here we check the collectionHandler->unload() function
        // First fill it a bit to make sure it's loaded...
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($collection->getName(), $document);

        $document = Document::createFromArray(array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2'));
        $documentId = $documentHandler->add($collection->getName(), $document);

        $arrayOfDocuments = $collectionHandler->getAllIds($collection->getName());

        $this->assertTrue(true === (is_array($arrayOfDocuments) && (count($arrayOfDocuments)==2)), 'Should return an array of 2 document ids!');

        //now check
        $unloadResult = $collectionHandler->unload($collection->getName());
        $unloadResult = $unloadResult->getJson();
        $this->assertArrayHasKey('status', $unloadResult, 'status field should exist');
        $this->assertTrue(($unloadResult['status'] == 4 || $unloadResult['status'] == 2), 'Collection status should be 4 (in the process of being unloaded) or 2 (unloaded). Found: '.$unloadResult['status'].'!');


        // here we check the collectionHandler->load() function
        $loadResult = $collectionHandler->load($collection->getName());
        $loadResult = $loadResult->getJson();
        $this->assertArrayHasKey('status', $loadResult, 'status field should exist');
        $this->assertTrue($loadResult['status'] == 3, 'Collection status should be 3(loaded). Found: '.$unloadResult['status'].'!');


        $resultingWaitForSyncAttribute = $collection->getWaitForSync();
        $resultingJournalSizeAttribute = $collection->getJournalSize();
        $this->assertTrue(true === $resultingWaitForSyncAttribute, 'Server waitForSync should return true!');
        $this->assertTrue($resultingJournalSizeAttribute == 1024*1024*2, 'JournalSize should be 2MB!');

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for creation, get, and delete of a collection given its settings through createFromArray() and waitForSync set to true
     */
    public function testCreateGetAndDeleteCollectionThroughCreateFromArrayWithWaitForSyncTrue()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true));
        $response = $collectionHandler->add($collection);

        $resultingCollection = $collectionHandler->get($response);
        
        $resultingAttribute = $collection->getWaitForSync();
        $this->assertTrue(true === $resultingAttribute, 'Server waitForSync should return true!');

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for creation of documents, and removal by example
     */
    public function testCreateDocumentsWithCreateFromArrayAndRemoveByExample()
    {
        $documentHandler = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true));
        $response = $collectionHandler->add($collection);
        $document = Document::createFromArray(array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($collection->getId(), $document);
        $document2 = Document::createFromArray(array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2'));
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3 = Document::createFromArray(array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue'));
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        $this->assertTrue(is_numeric($documentId), 'Did not return an id!');
        $this->assertTrue(is_numeric($documentId2), 'Did not return an id!');
        $this->assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $documentExample = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $result =  $collectionHandler->removeByExample($collection->getId(), $documentExample);
        $this->assertTrue($result === 2);
    }


    /**
     * test for import of documents, Headers-Values Style
     */
    public function testImportFromFileUsingHeadersAndValues()
    {
        $collectionHandler = $this->collectionHandler;
        $result = $collectionHandler->importFromFile('importCollection_01_arango_unittests', __DIR__.'/files_for_tests/import_file_header_values.txt', $options = array('createCollection'=>true));

        $this->assertTrue($result['error'] === false && $result['created']==2);

        $statement = new \triagens\ArangoDb\Statement($this->connection, array(
                                                                              "query" => '',
                                                                              "count" => true,
                                                                              "batchSize" => 1000,
                                                                              "sanitize" => true,
                                                                         ));
        $query='FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test1' && $result->firstName=='Joe', 'Document returned did not contain expected data.');
        $cursor->next();
        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test2' && $result->firstName=='Jane', 'Document returned did not contain expected data.');
   }


    /**
     * test for import of documents, Line by Line Documents Style
     */
    public function testImportFromFileUsingDocumentsLineByLine()
    {
        $collectionHandler = $this->collectionHandler;
        $result = $collectionHandler->importFromFile('importCollection_01_arango_unittests', __DIR__.'/files_for_tests/import_file_line_by_line.txt', $options = array('createCollection'=>true, 'type'=>'documents'));
        $this->assertTrue($result['error'] === false && $result['created']==2);

        $statement = new \triagens\ArangoDb\Statement($this->connection, array(
                                                                              "query" => '',
                                                                              "count" => true,
                                                                              "batchSize" => 1000,
                                                                              "sanitize" => true,
                                                                         ));
        $query='FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test1' && $result->firstName=='Joe', 'Document returned did not contain expected data.');
        $cursor->next();
        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test2' && $result->firstName=='Jane', 'Document returned did not contain expected data.');
   }


    /**
     * test for import of documents, Line by Line result-set Style
     */
    public function testImportFromFileUsingResultSet()
    {
        $collectionHandler = $this->collectionHandler;
        $result = $collectionHandler->importFromFile('importCollection_01_arango_unittests', __DIR__.'/files_for_tests/import_file_resultset.txt', $options = array('createCollection'=>true, 'type'=>'array'));
        $this->assertTrue($result['error'] === false && $result['created']==2);

        $statement = new \triagens\ArangoDb\Statement($this->connection, array(
                                                                              "query" => '',
                                                                              "count" => true,
                                                                              "batchSize" => 1000,
                                                                              "sanitize" => true,
                                                                         ));
        $query='FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test1' && $result->firstName=='Joe', 'Document returned did not contain expected data.');
        $cursor->next();
        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test2' && $result->firstName=='Jane', 'Document returned did not contain expected data.');
   }


    /**
     * test for import of documents by giving an array of documents
     */
    public function testImportFromArrayOfDocuments()
    {
        $collectionHandler = $this->collectionHandler;

        $document1 = Document::createFromArray(array('firstName' => 'Joe', 'lastName' => 'Public','age' => 42, 'gender' => 'male','_key'=>'test1'));
        $document2 = Document::createFromArray(array('firstName' => 'Jane', 'lastName' => 'Doe','age' => 31, 'gender' => 'female','_key'=>'test2'));

        $data = array($document1,$document2);
        $result = $collectionHandler->import('importCollection_01_arango_unittests', $data, $options = array('createCollection'=>true));

        $this->assertTrue($result['error'] === false && $result['created']==2);

        $statement = new \triagens\ArangoDb\Statement($this->connection, array(
                                                                        "query" => '',
                                                                        "count" => true,
                                                                        "batchSize" => 1000,
                                                                        "sanitize" => true,
                                                                   ));
        $query='FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test1' && $result->firstName=='Joe', 'Document returned did not contain expected data.');
        $cursor->next();
        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test2' && $result->firstName=='Jane', 'Document returned did not contain expected data.');
   }


   /**
     * test for import of documents by giving an array of documents
     */
    public function testImportFromStringWithValuesAndHeaders()
    {
        $collectionHandler = $this->collectionHandler;

        $data='[ "firstName", "lastName", "age", "gender", "_key"]
               [ "Joe", "Public", 42, "male", "test1" ]
               [ "Jane", "Doe", 31, "female", "test2" ]';

        $result = $collectionHandler->import('importCollection_01_arango_unittests', $data, $options = array('createCollection'=>true));

        $this->assertTrue($result['error'] === false && $result['created']==2);

        $statement = new \triagens\ArangoDb\Statement($this->connection, array(
                                                                        "query" => '',
                                                                        "count" => true,
                                                                        "batchSize" => 1000,
                                                                        "sanitize" => true,
                                                                   ));
        $query='FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test1' && $result->firstName=='Joe', 'Document returned did not contain expected data.');
        $cursor->next();
        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test2' && $result->firstName=='Jane', 'Document returned did not contain expected data.');
   }


   /**
     * test for import of documents by giving an array of documents
     */
    public function testImportFromStringUsingDocumentsLineByLine()
    {
        $collectionHandler = $this->collectionHandler;

        $data='{ "firstName" : "Joe", "lastName" : "Public", "age" : 42, "gender" : "male", "_key" : "test1"}
               { "firstName" : "Jane", "lastName" : "Doe", "age" : 31, "gender" : "female", "_key" : "test2"}';

        $result = $collectionHandler->import('importCollection_01_arango_unittests', $data, $options = array('createCollection'=>true, 'type'=>'documents'));

        $this->assertTrue($result['error'] === false && $result['created']==2);

        $statement = new \triagens\ArangoDb\Statement($this->connection, array(
                                                                        "query" => '',
                                                                        "count" => true,
                                                                        "batchSize" => 1000,
                                                                        "sanitize" => true,
                                                                   ));
        $query='FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test1' && $result->firstName=='Joe', 'Document returned did not contain expected data.');
        $cursor->next();
        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test2' && $result->firstName=='Jane', 'Document returned did not contain expected data.');
   }


   /**
     * test for import of documents by giving an array of documents
     */
    public function testImportFromStringUsingDocumentsUsingResultset()
    {
        $collectionHandler = $this->collectionHandler;

        $data='[{ "firstName" : "Joe", "lastName" : "Public", "age" : 42, "gender" : "male", "_key" : "test1"},
{ "firstName" : "Jane", "lastName" : "Doe", "age" : 31, "gender" : "female", "_key" : "test2"}]';

        $result = $collectionHandler->import('importCollection_01_arango_unittests', $data, $options = array('createCollection'=>true, 'type'=>'array'));

        $this->assertTrue($result['error'] === false && $result['created']==2);

        $statement = new \triagens\ArangoDb\Statement($this->connection, array(
                                                                        "query" => '',
                                                                        "count" => true,
                                                                        "batchSize" => 1000,
                                                                        "sanitize" => true,
                                                                   ));
        $query='FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test1' && $result->firstName=='Joe', 'Document returned did not contain expected data.');
        $cursor->next();
        $result = $cursor->current();

        $this->assertTrue($result->getKey() == 'test2' && $result->firstName=='Jane', 'Document returned did not contain expected data.');
   }


    /**
     * test for creation, getAllIds, and delete of a collection given its settings through createFromArray()
     */
    public function testCreateGetAllIdsAndDeleteCollectionThroughCreateFromArray()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $response = $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2'));
        $documentId = $documentHandler->add($collection->getId(), $document);

        $arrayOfDocuments = $collectionHandler->getAllIds($collection->getId());
       
        $this->assertTrue(true === (is_array($arrayOfDocuments) && (count($arrayOfDocuments)==2)), 'Should return an array of 2 document ids!');

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

   /**
     * test for creating, filling with documents and truncating the collection.
     */
    public function testCreateFillAndTruncateCollection()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $response = $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2'));
        $documentId = $documentHandler->add($collection->getId(), $document);

        $arrayOfDocuments = $collectionHandler->getAllIds($collection->getId());

        $this->assertTrue(true === (is_array($arrayOfDocuments) && (count($arrayOfDocuments)==2)), 'Should return an array of 2 document ids!');

        //truncate, given the collection object
        $collectionHandler->truncate($collection);


        $document = Document::createFromArray(array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue'));
        $documentId = $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2'));
        $documentId = $documentHandler->add($collection->getId(), $document);

        $arrayOfDocuments = $collectionHandler->getAllIds($collection->getId());

        $this->assertTrue(true === (is_array($arrayOfDocuments) && (count($arrayOfDocuments)==2)), 'Should return an array of 2 document ids!');

        //truncate, given the collection id
        $collectionHandler->truncate($collection->getId());



        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

    /**
     * test to set some attributes and get all attributes of the collection through getAll()
     */
    public function testGetAll()
    {
        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true));
        $result = $collection->getAll();

        $this->assertArrayHasKey('id', $result, 'Id field should exist, empty or with an id');
        $this->assertTrue(true === ($result['name'] == 'ArangoDB_PHP_TestSuite_TestCollection_01'), 'name should return ArangoDB_PHP_TestSuite_TestCollection_01!');
        $this->assertTrue(true === ($result['waitForSync'] == true), 'waitForSync should return true!');

    }
    

   /**
     * test for creation of a skip-list indexed collection and querying by range (first level and nested), with closed, skip and limit options
     */
     
    public function testCreateSkipListIndexedCollectionAddDocumentsAndQueryRange()
    {
        // set up collections, indexes and test-documents     
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $response = $collectionHandler->add($collection);
 
        $indexRes= $collectionHandler->index($collection->getId(), 'skiplist', array('index'));
        $nestedIndexRes= $collectionHandler->index($collection->getId(), 'skiplist', array('nested.index'));
        $this->assertArrayHasKey('isNewlyCreated', $indexRes, "index creation result should have the isNewlyCreated key !");    
        $this->assertArrayHasKey('isNewlyCreated', $nestedIndexRes, "index creation result should have the isNewlyCreated key !");    
         

        $documentHandler = $this->documentHandler;
        
        $document1 = Document::createFromArray(array('index' => 2, 'someOtherAttribute' => 'someValue2', 'nested' => array('index'=>3, 'someNestedAttribute3'=>'someNestedValue3')));
        $documentId1 = $documentHandler->add($collection->getId(), $document1);
        $document2 = Document::createFromArray(array('index' => 1, 'someOtherAttribute' => 'someValue1', 'nested' => array('index'=>2, 'someNestedAttribute3'=>'someNestedValue2')));
        $documentId2 = $documentHandler->add($collection->getId(), $document2);

        $document3 = Document::createFromArray(array('index' => 3, 'someOtherAttribute' => 'someValue3', 'nested' => array('index'=>1, 'someNestedAttribute3'=>'someNestedValue1')));
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        
        // first level attribute range test
        $rangeResult = $collectionHandler->range($collection->getId(),'index', 1, 2, array('closed'=>false));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue($resultArray[0]->index==1, "This value should be 1 !");
        $this->assertArrayNotHasKey(1, $resultArray, "Should not have a second key !");

                
        $rangeResult = $collectionHandler->range($collection->getId(),'index', 2, 3, array('closed'=>true));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue($resultArray[0]->index==2, "This value should be 2 !");
        $this->asserttrue($resultArray[1]->index==3, "This value should be 3 !");

                
        $rangeResult = $collectionHandler->range($collection->getId(),'index', 2, 3, array('closed'=>true, 'limit'=>1));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue($resultArray[0]->index==2, "This value should be 2 !");
        $this->assertArrayNotHasKey(1, $resultArray, "Should not have a second key !");

                
        $rangeResult = $collectionHandler->range($collection->getId(),'index', 2, 3, array('closed'=>true, 'skip'=>1));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue($resultArray[0]->index==3, "This value should be 3 !");
        $this->assertArrayNotHasKey(1, $resultArray, "Should not have a second key !");

        
        // nested attribute range test
        $rangeResult = $collectionHandler->range($collection->getId(),'nested.index', 1, 2, array('closed'=>false));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue($resultArray[0]->nested['index']==1, "This value should be 1 !");
        $this->assertArrayNotHasKey(1, $resultArray, "Should not have a second key !");

                
        $rangeResult = $collectionHandler->range($collection->getId(),'nested.index', 2, 3, array('closed'=>true));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue($resultArray[0]->nested['index']==2, "This value should be 2 !");
        $this->asserttrue($resultArray[1]->nested['index']==3, "This value should be 3 !");

                
        $rangeResult = $collectionHandler->range($collection->getId(),'nested.index', 2, 3, array('closed'=>true, 'limit'=>1));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue($resultArray[0]->nested['index']==2, "This value should be 2 !");
        $this->assertArrayNotHasKey(1, $resultArray, "Should not have a second key !");

                
        $rangeResult = $collectionHandler->range($collection->getId(),'nested.index', 2, 3, array('closed'=>true, 'skip'=>1));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue($resultArray[0]->nested['index']==3, "This value should be 3 !");
        $this->assertArrayNotHasKey(1, $resultArray, "Should not have a second key !");

        
        // Clean up...
        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }
    

   /**
     * test for creation of a geo indexed collection and querying by near, with distance, skip and limit options
     */
    public function testCreateGeoIndexedCollectionAddDocumentsAndQueryNear()
    {
        // set up collections, indexes and test-documents     
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $response = $collectionHandler->add($collection);
 
        $indexRes= $collectionHandler->index($collection->getId(), 'geo', array('loc'));
        $this->assertArrayHasKey('isNewlyCreated', $indexRes, "index creation result should have the isNewlyCreated key !");    
        
         

        $documentHandler = $this->documentHandler;

        $document1 = Document::createFromArray(array('loc' => array(0,0), 'someOtherAttribute' => '0 0'));
        $documentId1 = $documentHandler->add($collection->getId(), $document1);
        $document2 = Document::createFromArray(array('loc' => array(1,1), 'someOtherAttribute' => '1 1'));
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3 = Document::createFromArray(array('loc' => array(+30,-30), 'someOtherAttribute' => '30 -30'));
        $documentId3 = $documentHandler->add($collection->getId(), $document3);
        $response= $documentHandler->getById($collection->getId(), $documentId3);

        

        $rangeResult = $collectionHandler->near($collection->getId(), 0, 0);
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==0 && $resultArray[0]->loc[1]==0), "This value should be 0 0!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->asserttrue(($resultArray[1]->loc[0]==1 && $resultArray[1]->loc[1]==1), "This value should be 1 1!, is :" .$resultArray[1]->loc[0].' '.$resultArray[1]->loc[1]);

                
        $rangeResult = $collectionHandler->near($collection->getId(), 0, 0, array('distance'=>'distance'));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==0 && $resultArray[0]->loc[1]==0), "This value should be 0 0 !, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->asserttrue(($resultArray[1]->loc[0]==1 && $resultArray[1]->loc[1]==1), "This value should be 1 1!, is :" .$resultArray[1]->loc[0].' '.$resultArray[1]->loc[1]);
        $this->asserttrue(($resultArray[2]->loc[0]==30 && $resultArray[2]->loc[1]==-30), "This value should be 30 30!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->asserttrue($resultArray[0]->distance==0, "This value should be 0 ! It is :". $resultArray[0]->distance);
             
             
                
        $rangeResult = $collectionHandler->near($collection->getId(), 0, 0, array('limit'=>1));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==0 && $resultArray[0]->loc[1]==0), "This value should be 0 0!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->assertArrayNotHasKey(1, $resultArray, "Should not have a second key !");

        
                
        $rangeResult = $collectionHandler->near($collection->getId(), 0, 0, array('skip'=>1));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==1 && $resultArray[0]->loc[1]==1), "This value should be 1 1!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->asserttrue(($resultArray[1]->loc[0]==30 && $resultArray[1]->loc[1]==-30), "This value should be 30 30!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->assertArrayNotHasKey(2, $resultArray, "Should not have a third key !");

        
        
        $rangeResult = $collectionHandler->near($collection->getId(), +30, -30);
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==30 && $resultArray[0]->loc[1]==-30), "This value should be 30 30!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->asserttrue(($resultArray[1]->loc[0]==1 && $resultArray[1]->loc[1]==1), "This value should be 1 1!, is :" .$resultArray[1]->loc[0].' '.$resultArray[1]->loc[1]);
        $this->asserttrue(($resultArray[2]->loc[0]==0 && $resultArray[2]->loc[1]==0), "This value should be 0 0!, is :" .$resultArray[1]->loc[0].' '.$resultArray[1]->loc[1]);
      
      
        
        // Clean up...
        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }
    

   /**
     * test for creation of a geo indexed collection and querying by within, with distance, skip and limit options
     */
    public function testCreateGeoIndexedCollectionAddDocumentsAndQueryWithin()
    {
        // set up collections, indexes and test-documents     
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $response = $collectionHandler->add($collection);
 
        $indexRes= $collectionHandler->index($collection->getId(), 'geo', array('loc'));
        $this->assertArrayHasKey('isNewlyCreated', $indexRes, "index creation result should have the isNewlyCreated key !");    
        
         

        $documentHandler = $this->documentHandler;

        $document1 = Document::createFromArray(array('loc' => array(0,0), 'someOtherAttribute' => '0 0'));
        $documentId1 = $documentHandler->add($collection->getId(), $document1);
        $document2 = Document::createFromArray(array('loc' => array(1,1), 'someOtherAttribute' => '1 1'));
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3 = Document::createFromArray(array('loc' => array(+30,-30), 'someOtherAttribute' => '30 -30'));
        $documentId3 = $documentHandler->add($collection->getId(), $document3);
        $response= $documentHandler->getById($collection->getId(), $documentId3);

         
        
        $rangeResult = $collectionHandler->within($collection->getId(), 0, 0, 0);
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==0 && $resultArray[0]->loc[1]==0), "This value should be 0 0!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        

                
        $rangeResult = $collectionHandler->within($collection->getId(), 0, 0, 200 * 1000, array('distance'=>'distance'));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==0 && $resultArray[0]->loc[1]==0), "This value should be 0 0 !, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->asserttrue(($resultArray[1]->loc[0]==1 && $resultArray[1]->loc[1]==1), "This value should be 1 1!, is :" .$resultArray[1]->loc[0].' '.$resultArray[1]->loc[1]);
        $this->assertArrayNotHasKey(2, $resultArray, "Should not have a third key !");
        $this->asserttrue($resultArray[0]->distance==0, "This value should be 0 ! It is :". $resultArray[0]->distance);
             
             
                
        $rangeResult = $collectionHandler->within($collection->getId(), 0, 0, 200 * 1000, array('limit'=>1));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==0 && $resultArray[0]->loc[1]==0), "This value should be 0 0!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->assertArrayNotHasKey(1, $resultArray, "Should not have a second key !");

        
                
        $rangeResult = $collectionHandler->within($collection->getId(), 0, 0, 20000 * 1000, array('skip'=>1));
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==1 && $resultArray[0]->loc[1]==1), "This value should be 1 1!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->asserttrue(($resultArray[1]->loc[0]==30 && $resultArray[1]->loc[1]==-30), "This value should be 30 30!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->assertArrayNotHasKey(2, $resultArray, "Should not have a third key !");

        
        
        $rangeResult = $collectionHandler->within($collection->getId(), +30, -30, 20000 * 1000);
        $resultArray = $rangeResult->getAll();
        $this->asserttrue(($resultArray[0]->loc[0]==30 && $resultArray[0]->loc[1]==-30), "This value should be 30 30!, is :" .$resultArray[0]->loc[0].' '.$resultArray[0]->loc[1]);
        $this->asserttrue(($resultArray[1]->loc[0]==1 && $resultArray[1]->loc[1]==1), "This value should be 1 1!, is :" .$resultArray[1]->loc[0].' '.$resultArray[1]->loc[1]);
        $this->asserttrue(($resultArray[2]->loc[0]==0 && $resultArray[2]->loc[1]==0), "This value should be 0 0!, is :" .$resultArray[1]->loc[0].' '.$resultArray[1]->loc[1]);
        
        
        
        // Clean up...
        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    /**
     * test for creation of a geo indexed collection and querying by within, with distance, skip and limit options
     */
    public function testCreateFulltextIndexedCollectionAddDocumentsAndQuery()
    {
        // set up collections and index
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $response = $collectionHandler->add($collection);

        $indexRes= $collectionHandler->index($collection->getName(), 'fulltext', array('name'));
        $this->assertArrayHasKey('isNewlyCreated', $indexRes, "index creation result should have the isNewlyCreated key !");

        // Check if the index is returned in the indexes of the collection
        $indexes = $collectionHandler->getIndexes($collection->getName());
        $this->assertTrue($indexes['indexes'][1]['fields'][0] === 'name', 'The index should be on field "name"!');

        // Drop the index
        $collectionHandler->dropIndex($indexes['indexes'][1]['id']);
        $indexes = $collectionHandler->getIndexes($collection->getName());

        // Check if the index is not in the indexes of the collection anymore
        $this->assertArrayNotHasKey(1,$indexes['indexes'], 'There should not be an index on field "name"!');

        // Clean up...
        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }


    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
       try {
           $response = $this->collectionHandler->drop('importCollection_01_arango_unittests');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
       try {
           $response = $this->collectionHandler->drop('_ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
