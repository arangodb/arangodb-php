<?php

/**
 * ArangoDB PHP client testsuite
 * File: CollectionBasicTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * @property Connection             connection
 * @property Collection             collection
 * @property CollectionHandler      collectionHandler
 * @property DocumentHandler        documentHandler
 */
class CollectionBasicTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        $this->collectionHandler->create('ArangoDB_PHP_TestSuite_IndexTestCollection');
    }


    /**
     * Test default collection type
     */
    public function testDefaultCollectionType()
    {
        $this->assertEquals(Collection::TYPE_DOCUMENT, Collection::getDefaultType());
    }


    /**
     * Test if Collection and CollectionHandler instances can be initialized
     */
    public function testInitializeCollection()
    {
        $connection = $this->connection;
        $collection = new Collection();
        $this->assertInstanceOf('triagens\ArangoDb\Collection', $collection);
        new CollectionHandler($connection);
        $this->assertInstanceOf('triagens\ArangoDb\Collection', $collection);
    }


    /**
     * Test setting and getting collection types
     */
    public function testInitializeCollectionWithDocumentType()
    {
        $collection = new Collection();
        $collection->setType(Collection::TYPE_DOCUMENT);

        $this->assertEquals(Collection::TYPE_DOCUMENT, $collection->getType());
    }


    /**
     * Test setting and getting collection types
     */
    public function testInitializeCollectionWithEdgeType()
    {
        $collection = new Collection();
        $collection->setType(Collection::TYPE_EDGE);

        $this->assertEquals(Collection::TYPE_EDGE, $collection->getType());
    }


    /**
     * Try to create and delete a collection
     */
    public function testCreateAndDeleteCollectionPre1_2()
    {
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);
        
        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Did not return a numeric id!');

        $resultingCollection = $collectionHandler->get($response);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue(
             $name === $resultingAttribute,
             'The created collection name and resulting collection name do not match!'
        );

        $this->assertEquals(Collection::getDefaultType(), $resultingCollection->getType());

        $collectionHandler->delete($collection);
    }

    /**
     * Try to create a collection with keyOptions and then retrieve it to confirm.
     */
    public function testCreateCollectionWithKeyOptionsAndVerifyProperties()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }
        
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);
   
        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setKeyOptions(
                   array("type" => "autoincrement", "allowUserKeys" => false, "increment" => 5, "offset" => 10)
        );
        $response = $collectionHandler->add($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        $this->assertEquals($properties[Collection::ENTRY_STATUS], 3, 'Status does not match.');
        $this->assertEquals(
             $properties[Collection::ENTRY_KEY_OPTIONS]['type'],
             'autoincrement',
             'Key options type does not match'
        );
        $this->assertEquals(
             $properties[Collection::ENTRY_KEY_OPTIONS]['allowUserKeys'],
             false,
             'Key options allowUserKeys does not match'
        );
        $this->assertEquals(
             $properties[Collection::ENTRY_KEY_OPTIONS]['increment'],
             5,
             'Key options increment does not match'
        );
        $this->assertEquals(
             $properties[Collection::ENTRY_KEY_OPTIONS]['offset'],
             10,
             'Key options offset does not match'
        );
        $collectionHandler->delete($collection);
    }
    

    /**
     * Try to create a collection with keyOptions and then retrieve it to confirm.
     */
    public function testCreateCollectionWithKeyOptionsCluster()
    {
        if (! isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            return;
        }
        
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);
   
        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setKeyOptions(
                   array("type" => "autoincrement", "allowUserKeys" => false, "increment" => 5, "offset" => 10)
        );

        try {
            $response = $collectionHandler->add($collection);
        }
        catch (\Exception $e) {
        }
        
        $this->assertEquals($e->getCode() , 501);
    }
    
    
    /**
     * Try to create a collection with number of shards
     */
    public function testCreateCollectionWithNumberOfShardsCluster()
    {
        if (! isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            return;
        }
        
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);
   
        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setNumberOfShards(4);

        $response = $collectionHandler->add($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        $this->assertEquals($properties[Collection::ENTRY_NUMBER_OF_SHARDS], 4, 'Number of shards does not match.');
        $this->assertEquals($properties[Collection::ENTRY_SHARD_KEYS], array("_key"), 'Shard keys do not match.');
    }
   
    
    /**
     * Try to create a collection with specified shard keys
     */
    public function testCreateCollectionWithShardKeysCluster()
    {
        if (! isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            return;
        }
        
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);
   
        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setShardKeys(array("_key", "a", "b"));

        $response = $collectionHandler->add($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        $this->assertEquals($properties[Collection::ENTRY_NUMBER_OF_SHARDS], 1, 'Number of shards does not match.');
        $this->assertEquals($properties[Collection::ENTRY_SHARD_KEYS], array("_key", "a", "b"), 'Shard keys do not match.');
    }


    /**
     * Try to create and delete a collection
     */
    public function testCreateAndDeleteCollection()
    {
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);
        
        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collectionHandler->add($collection);

        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue(
             $name === $resultingAttribute,
             'The created collection name and resulting collection name do not match!'
        );

        $this->assertEquals(Collection::getDefaultType(), $resultingCollection->getType());

        $collectionHandler->delete($collection);
    }


    /**
     * Try to create and delete an edge collection
     */
    public function testCreateAndDeleteEdgeCollection()
    {
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);
        
        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02';

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setType(3);
        $collectionHandler->add($collection);

        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue(
             $name === $resultingAttribute,
             'The created collection name and resulting collection name do not match!'
        );

        $this->assertEquals(Collection::TYPE_EDGE, $resultingCollection->getType());

        $collectionHandler->delete($collection);
    }


    /**
     * Try to create and delete an edge collection not using an edge object
     */
    public function testCreateAndDeleteEdgeCollectionWithoutCreatingObject()
    {
        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $name    = 'ArangoDB_PHP_TestSuite_TestCollection_02';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $options = array('type' => 3);
        $collectionHandler->create($name, $options);

        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue(
             $name === $resultingAttribute,
             'The created collection name and resulting collection name do not match!'
        );

        $this->assertEquals(Collection::TYPE_EDGE, $resultingCollection->getType());

        $collectionHandler->delete($name);
    }


    /**
     * Try to create and delete an edge collection not using an edge object
     */
    public function testCreateAndDeleteVolatileCollectionWithoutCreatingObject()
    {
        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $name    = 'ArangoDB_PHP_TestSuite_TestCollection_02';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $options = array('isVolatile' => true);
        $collectionHandler->create($name, $options);
        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue(
             $name === $resultingAttribute,
             'The created collection name and resulting collection name do not match!'
        );
        $resultingCollectionProperties = $collectionHandler->getProperties($name);
        $this->assertTrue($resultingCollectionProperties->getIsVolatile());

        $collectionHandler->delete($name);
    }


    /**
     * Try to create and delete an edge collection not using an edge object
     */
    public function testCreateAndDeleteSystemCollectionWithoutCreatingObject()
    {
        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $name    = '_ArangoDB_PHP_TestSuite_TestCollection_02';
        
        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $options = array('isSystem' => true, 'waitForSync' => true);
        $collectionHandler->create($name, $options);

        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue(
             $name === $resultingAttribute,
             'The created collection name and resulting collection name do not match!'
        );
        $resultingCollectionProperties = $collectionHandler->getProperties($name);
        $this->assertTrue($resultingCollectionProperties->getIsSystem());
        $this->assertTrue($resultingCollectionProperties->getWaitForSync());


        $collectionHandler->delete($name);
    }


    /**
     * Create a cap constraint and verify it by getting information about the constraint from the server
     */
    public function testCreateCapConstraint()
    {
        $result = $this->collectionHandler->createCapConstraint('ArangoDB_PHP_TestSuite_IndexTestCollection', 50);

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $indicesByIdentifiers = $indices['identifiers'];

        $this->assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Cap constraint was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        $this->assertEquals(
             CollectionHandler::OPTION_CAP_CONSTRAINT,
             $indexInfo[CollectionHandler::OPTION_TYPE],
             "Index type is not 'cap'!"
        );

        $this->assertEquals(
             50,
             $indexInfo[CollectionHandler::OPTION_SIZE],
             'Size of the cap constrain does not match!'
        );
    }


    /**
     * Create a geo index with 1 field and verify it by getting information about the index from the server
     */
    public function testCreateGeo1Index()
    {
        $result = $this->collectionHandler->createGeoIndex(
                                          'ArangoDB_PHP_TestSuite_IndexTestCollection',
                                          array('combinedGeo'),
                                          true,
                                          true,
                                          true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $indicesByIdentifiers = $indices['identifiers'];

        $this->assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Geo index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        $this->assertEquals("geo1", $indexInfo[CollectionHandler::OPTION_TYPE], "Index type is not 'geo1'!");
        $this->assertCount(1, $indexInfo['fields'], "There should only be 1 indexed field");
        $this->assertEquals("combinedGeo", $indexInfo['fields'][0], "The indexed field is not 'combinedGeo'");
        $this->assertEquals(true, $indexInfo[CollectionHandler::OPTION_GEOJSON], 'geoJson was not set to true!');
        $this->assertEquals(true, $indexInfo[CollectionHandler::OPTION_CONSTRAINT], 'constraint was not set to true!');
        $this->assertEquals(true, $indexInfo[CollectionHandler::OPTION_IGNORE_NULL], 'ignoreNull was not set to true!');
    }


    /**
     * Create a geo index with 2 fields and verify it by getting information about the index from the server
     */
    public function testCreateGeo2Index()
    {
        $result = $this->collectionHandler->createGeoIndex(
                                          'ArangoDB_PHP_TestSuite_IndexTestCollection',
                                          array('lat', 'long'),
                                          false,
                                          false,
                                          false
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $indicesByIdentifiers = $indices['identifiers'];

        $this->assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Geo index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        $this->assertEquals("geo2", $indexInfo[CollectionHandler::OPTION_TYPE], "Index type is not 'geo2'!");
        $this->assertCount(2, $indexInfo['fields'], "There should only be 2 indexed fields");
        $this->assertEquals("lat", $indexInfo['fields'][0], "The first indexed field is not 'lat'");
        $this->assertEquals("long", $indexInfo['fields'][1], "The second indexed field is not 'long'");
        $this->assertArrayNotHasKey(CollectionHandler::OPTION_GEOJSON, $indexInfo, 'geoJson was set!');
        $this->assertEquals(
             false,
             $indexInfo[CollectionHandler::OPTION_CONSTRAINT],
             'constraint was not set to false!'
        );

        if (! array_key_exists(CollectionHandler::OPTION_IGNORE_NULL, $indexInfo)) {
            // downwards-compatibility
            $indexInfo[CollectionHandler::OPTION_IGNORE_NULL] = false;
        }
        $this->assertEquals(false, $indexInfo[CollectionHandler::OPTION_IGNORE_NULL], 'ignoreNull was not false!');
    }


    /**
     * Create a hash index and verify it by getting information about the index from the server
     */
    public function testCreateHashIndex()
    {
        $result = $this->collectionHandler->createHashIndex(
                                          'ArangoDB_PHP_TestSuite_IndexTestCollection',
                                          array('hashfield1', 'hashfield2'),
                                          true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $indicesByIdentifiers = $indices['identifiers'];

        $this->assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Hash index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        $this->assertEquals(
             CollectionHandler::OPTION_HASH_INDEX,
             $indexInfo[CollectionHandler::OPTION_TYPE],
             "Index type is not 'hash'!"
        );
        $this->assertCount(2, $indexInfo['fields'], "There should only be 2 indexed fields");
        $this->assertEquals("hashfield1", $indexInfo['fields'][0], "The first indexed field is not 'hashfield1'");
        $this->assertEquals("hashfield2", $indexInfo['fields'][1], "The second indexed field is not 'hashfield2'");
        $this->assertEquals(true, $indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to true!');
    }


    /**
     * Create a fulltext index and verify it by getting information about the index from the server
     */
    public function testCreateFulltextIndex()
    {
        $result = $this->collectionHandler->createFulltextIndex(
                                          'ArangoDB_PHP_TestSuite_IndexTestCollection',
                                          array('fulltextfield'),
                                          5
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $indicesByIdentifiers = $indices['identifiers'];

        $this->assertArrayHasKey($result['id'], $indicesByIdentifiers, 'fulltext index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        $this->assertEquals(
             CollectionHandler::OPTION_FULLTEXT_INDEX,
             $indexInfo[CollectionHandler::OPTION_TYPE],
             "Index type is not 'fulltext'!"
        );
        $this->assertCount(1, $indexInfo['fields'], "There should only be 1 indexed field");
        $this->assertEquals("fulltextfield", $indexInfo['fields'][0], "The indexed field is not 'fulltextfield'");
        $this->assertEquals(5, $indexInfo[CollectionHandler::OPTION_MIN_LENGTH], 'minLength was not set to 5!');
    }


    /**
     * Create a skiplist index and verify it by getting information about the index from the server
     */
    public function testCreateSkipListIndex()
    {
        $result = $this->collectionHandler->createSkipListIndex(
                                          'ArangoDB_PHP_TestSuite_IndexTestCollection',
                                          array('skiplistfield1', 'skiplistfield2'),
                                          true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $indicesByIdentifiers = $indices['identifiers'];

        $this->assertArrayHasKey($result['id'], $indicesByIdentifiers, 'skip-list index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        $this->assertEquals(
             CollectionHandler::OPTION_SKIPLIST_INDEX,
             $indexInfo[CollectionHandler::OPTION_TYPE],
             "Index type is not 'skip-list'!"
        );
        $this->assertCount(2, $indexInfo['fields'], "There should only be 2 indexed field");
        $this->assertEquals("skiplistfield1", $indexInfo['fields'][0], "The indexed field is not 'skiplistfield1'");
        $this->assertEquals("skiplistfield2", $indexInfo['fields'][1], "The indexed field is not 'skiplistfield2'");
        $this->assertEquals(true, $indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to true!');
    }


    /**
     * Test creating an index and getting it to verify.
     */
    public function testGetIndex()
    {
        $result = $this->collectionHandler->createFulltextIndex(
                                          'ArangoDB_PHP_TestSuite_IndexTestCollection',
                                          array('testGetIndexField'),
                                          100
        );

        //Parse for the index's key
        $key = str_replace('ArangoDB_PHP_TestSuite_IndexTestCollection/', "", $result['id']);

        $indexInfo = $this->collectionHandler->getIndex('ArangoDB_PHP_TestSuite_IndexTestCollection', $key);

        $this->assertEquals(
             CollectionHandler::OPTION_FULLTEXT_INDEX,
             $indexInfo[CollectionHandler::OPTION_TYPE],
             "Index type does not match!"
        );
        $this->assertCount(1, $indexInfo['fields'], "There should only be 1 indexed field!");
        $this->assertEquals("testGetIndexField", $indexInfo['fields'][0], "Index field does not match!");
        $this->assertEquals(100, $indexInfo[CollectionHandler::OPTION_MIN_LENGTH], 'Min length does not match!');
    }


    public function tearDown()
    {
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_IndexTestCollection');
        } catch (Exception $e) {
            //Silence the exception
        }
        unset($this->collectionHandler);
        unset($this->connection);
    }
}
