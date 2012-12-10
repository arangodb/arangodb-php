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

        $collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
       

        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($response);

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

    /**
     * test for creation, get, and delete of a collection with waitForSync set to true
     */
    public function testCreateGetAndDeleteCollectionWithWaitForSyncTrue()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $collection->setWaitForSync(true);
        $resultingAttribute = $collection->getWaitForSync();
        
        $this->assertTrue(true === $resultingAttribute, 'WaitForSync should be true!');
        $collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');

        $response = $collectionHandler->add($collection);
        
        #$collection->properties();                        
        $resultingAttribute = $collection->getWaitForSync();
        $this->assertTrue(true === $resultingAttribute, 'Server waitForSync should return true!');

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
        
        #$collection->properties();                        
        $resultingAttribute = $collection->getWaitForSync();
        $this->assertTrue(true === $resultingAttribute, 'Server waitForSync should return true!');

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
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
