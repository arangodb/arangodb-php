<?php

/**
 * ArangoDB PHP client testsuite
 * File: CollectionBasicTest.php
 *
 * @package ArangoDBClient
 * @author  Frank Mayer
 */

namespace ArangoDBClient;

/**
 * @property Connection        connection
 * @property Collection        collection
 * @property CollectionHandler collectionHandler
 */
class CollectionBasicTest extends
    \PHPUnit_Framework_TestCase
{
    protected static $testsTimestamp;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        static::$testsTimestamp = str_replace('.', '_', (string) microtime(true));
    }


    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
            //Silence the exception
        }
        $this->collectionHandler->create('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $adminHandler = new AdminHandler($this->connection);
        $this->isMMFilesEngine         = ($adminHandler->getEngine()["name"] == "mmfiles"); 
    }


    /**
     * Test default collection type
     */
    public function testDefaultCollectionType()
    {
        static::assertEquals(Collection::TYPE_DOCUMENT, Collection::getDefaultType());
    }


    /**
     * Test if Collection and CollectionHandler instances can be initialized
     */
    public function testInitializeCollection()
    {
        $connection = $this->connection;
        $collection = new Collection();
        static::assertInstanceOf(Collection::class, $collection);
        new CollectionHandler($connection);
        static::assertInstanceOf(Collection::class, $collection);
    }


    /**
     * Test setting and getting collection types
     */
    public function testInitializeCollectionWithDocumentType()
    {
        $collection = new Collection();
        $collection->setType(Collection::TYPE_DOCUMENT);

        static::assertEquals(Collection::TYPE_DOCUMENT, $collection->getType());
    }


    /**
     * Test setting and getting collection types
     */
    public function testInitializeCollectionWithEdgeType()
    {
        $collection = new Collection();
        $collection->setType(Collection::TYPE_EDGE);

        static::assertEquals(Collection::TYPE_EDGE, $collection->getType());
    }


    /**
     * Try to create and delete a collection
     */
    public function testCreateAndDeleteCollectionPre1_2()
    {
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $response = $collectionHandler->create($collection);

        static::assertTrue(is_numeric($response), 'Did not return a numeric id!');

        $resultingCollection = $collectionHandler->get($response);

        $resultingAttribute = $resultingCollection->getName();
        static::assertSame(
            $name, $resultingAttribute, 'The created collection name and resulting collection name do not match!'
        );

        static::assertEquals(Collection::getDefaultType(), $resultingCollection->getType());

        $collectionHandler->drop($collection);
    }

    /**
     * Try to create a collection with keyOptions and then retrieve it to confirm.
     */
    public function testCreateCollectionWithKeyOptionsAndVerifyProperties()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            $this->markTestSkipped("test is only meaningful in single server");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setKeyOptions(
            ['type' => 'autoincrement', 'allowUserKeys' => false, 'increment' => 5, 'offset' => 10]
        );
        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals(3, $properties[Collection::ENTRY_STATUS], 'Status does not match.');
        static::assertEquals(
            'autoincrement',
            $properties[Collection::ENTRY_KEY_OPTIONS]['type'],
            'Key options type does not match'
        );
        static::assertEquals(
            false,
            $properties[Collection::ENTRY_KEY_OPTIONS]['allowUserKeys'],
            'Key options allowUserKeys does not match'
        );
        static::assertEquals(
            5,
            $properties[Collection::ENTRY_KEY_OPTIONS]['increment'],
            'Key options increment does not match'
        );
        static::assertEquals(
            10,
            $properties[Collection::ENTRY_KEY_OPTIONS]['offset'],
            'Key options offset does not match'
        );
        $collectionHandler->drop($collection);
    }


    /**
     * Try to create a collection with keyOptions and then retrieve it to confirm.
     */
    public function testCreateCollectionWithKeyOptionsCluster()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setKeyOptions(
            ['type' => 'autoincrement', 'allowUserKeys' => false, 'increment' => 5, 'offset' => 10]
        );

        try {
            $collectionHandler->create($collection);
        } catch (\Exception $e) {
        }

        static::assertEquals(501, $e->getCode());
    }
    
    
    /**
     * Try to create a collection with distributeShardsLike
     */
    public function testCreateCollectionWithDistributeShardsLike()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }
        
        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $collection1       = new Collection();
        $name1 = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;
        $collection1->setName($name1);
        $response = $collectionHandler->create($collection1);
        
        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();
        static::assertFalse(array_key_exists(Collection::ENTRY_DISTRIBUTE_SHARDS_LIKE, $properties));
        
        $collection2       = new Collection();
        $name2 = 'ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp;
        $collection2->setName($name2);
        $collection2->setDistributeShardsLike($name1);
        $response = $collectionHandler->create($collection2);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals($name1, $properties[Collection::ENTRY_DISTRIBUTE_SHARDS_LIKE]);
    }


    /**
     * Try to create a collection with number of shards
     */
    public function testCreateCollectionWithNumberOfShardsCluster()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setNumberOfShards(4);

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals(4, $properties[Collection::ENTRY_NUMBER_OF_SHARDS], 'Number of shards does not match.');
        static::assertEquals(['_key'], $properties[Collection::ENTRY_SHARD_KEYS], 'Shard keys do not match.');
    }
    
    /**
     * Try to create a collection with replication factor 1
     */
    public function testCreateCollectionWithReplicationFactor1()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setReplicationFactor(1);
        $collection->setWriteConcern(1);

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals(1, $properties[Collection::ENTRY_REPLICATION_FACTOR]);
        static::assertEquals(1, $properties[Collection::ENTRY_WRITE_CONCERN]);
    }
    
    
    /**
     * Try to create a collection with replication factor 2
     */
    public function testCreateCollectionWithReplicationFactor2()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setReplicationFactor(2);
        $collection->setWriteConcern(2);

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals(2, $properties[Collection::ENTRY_REPLICATION_FACTOR]);
        static::assertEquals(2, $properties[Collection::ENTRY_WRITE_CONCERN]);
    }
    
    /**
     * Try to create a collection with replication factor "satellite"
     */
    public function testCreateCollectionWithReplicationFactorSatellite()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }
        
        if (!isEnterprise($this->connection)) {
          // don't execute this test in community version
            $this->markTestSkipped("test is only meaningful in enterprise version");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setReplicationFactor("satellite");

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals("satellite", $properties[Collection::ENTRY_REPLICATION_FACTOR]);
        static::assertEquals(0, $properties[Collection::ENTRY_WRITE_CONCERN]);
    }
    
    
    /**
     * Try to create a collection with an explicit sharding strategy
     */
    public function testCreateCollectionWithShardingStrategyCommunityCompat()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setShardingStrategy('community-compat');

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals('community-compat', $properties[Collection::ENTRY_SHARDING_STRATEGY]);
    }
    
    
    /**
     * Try to create a collection with an explicit sharding strategy
     */
    public function testCreateCollectionWithShardingStrategyHash()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setShardingStrategy('hash');

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals('hash', $properties[Collection::ENTRY_SHARDING_STRATEGY]);
    }
    
    
    /**
     * Try to create a collection without an explicit sharding strategy
     */
    public function testCreateCollectionWithoutShardingStrategy()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals('hash', $properties[Collection::ENTRY_SHARDING_STRATEGY]);
    }


    /**
     * Try to create a collection with specified shard keys
     */
    public function testCreateCollectionWithShardKeysCluster()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setShardKeys(['_key', 'a', 'b']);

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals(1, $properties[Collection::ENTRY_NUMBER_OF_SHARDS], 'Number of shards does not match.');
        static::assertEquals(
            [
                '_key',
                'a',
                'b'
            ],
            $properties[Collection::ENTRY_SHARD_KEYS],
            'Shard keys do not match.'
        );
    }
    
    /**
     * Try to create a collection with smart join attribute
     */
    public function testCreateCollectionWithSmartJoinAttribute() 
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }
        
        if (!isEnterprise($this->connection)) {
          // don't execute this test in community version
            $this->markTestSkipped("test is only meaningful in enterprise version");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setShardKeys(['_key:']);
        $collection->setSmartJoinAttribute("myAttribute");

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals([ '_key:' ], $properties[Collection::ENTRY_SHARD_KEYS]);
        static::assertEquals("myAttribute", $properties[Collection::ENTRY_SMART_JOIN_ATTRIBUTE]);
    }


    /**
     * Try to create and delete a collection
     */
    public function testCreateAndDeleteCollection()
    {
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        static::assertSame(
            $name, $resultingAttribute, 'The created collection name and resulting collection name do not match!'
        );

        static::assertEquals(Collection::getDefaultType(), $resultingCollection->getType());

        $collectionHandler->drop($collection);
    }


    /**
     * Try to create and delete an edge collection
     */
    public function testCreateAndDeleteEdgeCollection()
    {
        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setType(3);
        $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        static::assertSame(
            $name, $resultingAttribute, 'The created collection name and resulting collection name do not match!'
        );

        static::assertEquals(Collection::TYPE_EDGE, $resultingCollection->getType());

        $collectionHandler->drop($collection);
    }


    /**
     * Try to create and delete an edge collection not using an edge object
     */
    public function testCreateAndDeleteEdgeCollectionWithoutCreatingObject()
    {
        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $options = ['type' => 3];
        $collectionHandler->create($name, $options);

        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        static::assertSame(
            $name, $resultingAttribute, 'The created collection name and resulting collection name do not match!'
        );

        static::assertEquals(Collection::TYPE_EDGE, $resultingCollection->getType());

        $collectionHandler->drop($name);
    }


    /**
     * Try to create and delete an edge collection not using an edge object
     */
    public function testCreateAndDeleteVolatileCollectionWithoutCreatingObject()
    {
        if (!$this->isMMFilesEngine) {
            $this->markTestSkipped("test is only meaningful with the mmfiles engine");
        }

        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $options = ['isVolatile' => true];
        $collectionHandler->create($name, $options);
        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        static::assertSame(
            $name, $resultingAttribute, 'The created collection name and resulting collection name do not match!'
        );
        $resultingCollectionProperties = $collectionHandler->getProperties($name);
        static::assertTrue((!$this->isMMFilesEngine) || $resultingCollectionProperties->getIsVolatile());

        $collectionHandler->drop($name);
    }


    /**
     * Try to create and delete an edge collection not using an edge object
     */
    public function testCreateAndDeleteSystemCollectionWithoutCreatingObject()
    {
        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $name = '_ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name, ['isSystem' => true]);
        } catch (Exception $e) {
            //Silence the exception
        }

        $options = ['isSystem' => true, 'waitForSync' => true];
        $collectionHandler->create($name, $options);

        $resultingCollection = $collectionHandler->get($name);

        $resultingAttribute = $resultingCollection->getName();
        static::assertSame(
            $name, $resultingAttribute, 'The created collection name and resulting collection name do not match!'
        );
        $resultingCollectionProperties = $collectionHandler->getProperties($name);
        static::assertTrue($resultingCollectionProperties->getIsSystem());
        static::assertTrue($resultingCollectionProperties->getWaitForSync());


        $collectionHandler->drop($name, ['isSystem' => true]);
    }

    
    /**
     * Creates an index using createIndex
     */
    public function testCreateIndex()
    {
        $result = $this->collectionHandler->createIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, [
                'type' => 'hash',
                'name' => 'mr-hash',
                'fields' => ['a', 'b'],
                'unique' => true,
                'sparse' => true,
                'inBackground' => true
            ]
        ); 
        
        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers);

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals('hash', $indexInfo[CollectionHandler::OPTION_TYPE]);
        static::assertEquals(['a', 'b'], $indexInfo['fields']);
        static::assertTrue($indexInfo['unique']);
        static::assertTrue($indexInfo['sparse']);
        static::assertEquals('mr-hash', $indexInfo['name']);
    }
    
    
    /**
     * Gets an index by id
     */
    public function testGetIndexById()
    {
        $result = $this->collectionHandler->createIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, [
                'type' => 'persistent',
                'name' => 'abc',
                'fields' => ['b', 'a', 'c'],
                'unique' => false,
                'sparse' => true,
                'inBackground' => false
            ]
        ); 
        
        $indexInfo = $this->collectionHandler->getIndex('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, $result['id']);

        static::assertEquals('persistent', $indexInfo[CollectionHandler::OPTION_TYPE]);
        static::assertEquals(['b', 'a', 'c'], $indexInfo['fields']);
        static::assertFalse($indexInfo['unique']);
        static::assertTrue($indexInfo['sparse']);
        static::assertEquals('abc', $indexInfo['name']);
    }

    
    /**
     * Gets an index by name
     */
    public function testGetIndexByName()
    {
        $result = $this->collectionHandler->createIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, [
                'type' => 'fulltext',
                'name' => 'this-is-an-index',
                'fields' => ['c'],
                'minLength' => 4,
            ]
        ); 
        
        $indexInfo = $this->collectionHandler->getIndex('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, $result['id']);

        static::assertEquals('fulltext', $indexInfo[CollectionHandler::OPTION_TYPE]);
        static::assertEquals(['c'], $indexInfo['fields']);
        static::assertFalse($indexInfo['unique']);
        static::assertTrue($indexInfo['sparse']);
        static::assertEquals(4, $indexInfo['minLength']);
        static::assertEquals('this-is-an-index', $indexInfo['name']);
    }
    
    /**
     * Drops an index by id
     */
    public function testDropIndexById()
    {
        $result = $this->collectionHandler->createIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, [
                'type' => 'fulltext',
                'name' => 'this-is-an-index',
                'fields' => ['c'],
                'minLength' => 4,
            ]
        ); 
        
        $result = $this->collectionHandler->dropIndex('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, $result['id']);
        static::assertTrue($result);
    }


    /**
     * Drops an index by name
     */
    public function testDropIndexByName()
    {
        $result = $this->collectionHandler->createIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, [
                'type' => 'fulltext',
                'name' => 'this-is-an-index',
                'fields' => ['c'],
                'minLength' => 4,
            ]
        ); 
        
        $result = $this->collectionHandler->dropIndex('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, 'this-is-an-index');
        static::assertTrue($result);
    }

    /**
     * Create a geo index with 1 field and verify it by getting information about the index from the server
     */
    public function testCreateGeo1Index()
    {
        $result = $this->collectionHandler->createGeoIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['combinedGeo'],
            true,
            true,
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Geo index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertTrue(in_array($indexInfo[CollectionHandler::OPTION_TYPE], ["geo", "geo1"]), "Index type is not 'geo1'!");
        static::assertCount(1, $indexInfo['fields'], 'There should only be 1 indexed field');
        static::assertEquals('combinedGeo', $indexInfo['fields'][0], "The indexed field is not 'combinedGeo'");
        static::assertEquals(true, $indexInfo[CollectionHandler::OPTION_GEOJSON], 'geoJson was not set to true!');
    }


    /**
     * Create a geo index with 2 fields and verify it by getting information about the index from the server
     */
    public function testCreateGeo2Index()
    {
        $result = $this->collectionHandler->createGeoIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['lat', 'long'],
            false,
            false,
            false
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Geo index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertTrue(in_array($indexInfo[CollectionHandler::OPTION_TYPE], ["geo", "geo2"]), "Index type is not 'geo2'!");
        static::assertCount(2, $indexInfo['fields'], 'There should only be 2 indexed fields');
        static::assertEquals('lat', $indexInfo['fields'][0], "The first indexed field is not 'lat'");
        static::assertEquals('long', $indexInfo['fields'][1], "The second indexed field is not 'long'");
    }


    /**
     * Create a hash index and verify it by getting information about the index from the server
     */
    public function testCreateHashIndex()
    {
        $result = $this->collectionHandler->createHashIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['hashfield1', 'hashfield2'],
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Hash index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_HASH_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            "Index type is not 'hash'!"
        );
        static::assertCount(2, $indexInfo['fields'], 'There should only be 2 indexed fields');
        static::assertEquals('hashfield1', $indexInfo['fields'][0], "The first indexed field is not 'hashfield1'");
        static::assertEquals('hashfield2', $indexInfo['fields'][1], "The second indexed field is not 'hashfield2'");
        static::assertTrue($indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to true!');

        static::assertFalse($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to false!');
        static::assertTrue(isset($indexInfo['selectivityEstimate']), 'selectivity estimate not present!');
    }


    /**
     * Create a sparse hash index and verify it by getting information about the index from the server
     */
    public function testCreateSparseHashIndex()
    {
        $result = $this->collectionHandler->createHashIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['hashfield1', 'hashfield2'],
            false,
            ['sparse' => true]
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Hash index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_HASH_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            "Index type is not 'hash'!"
        );
        static::assertCount(2, $indexInfo['fields'], 'There should only be 2 indexed fields');
        static::assertEquals('hashfield1', $indexInfo['fields'][0], "The first indexed field is not 'hashfield1'");
        static::assertEquals('hashfield2', $indexInfo['fields'][1], "The second indexed field is not 'hashfield2'");
        static::assertFalse($indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to false!');

        static::assertTrue($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to true!');
        static::assertTrue(isset($indexInfo['selectivityEstimate']), 'selectivity estimate not present!');
    }


    /**
     * Create a fulltext index and verify it by getting information about the index from the server
     */
    public function testCreateFulltextIndex()
    {
        $result = $this->collectionHandler->createFulltextIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['fulltextfield'],
            5
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'fulltext index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_FULLTEXT_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            "Index type is not 'fulltext'!"
        );
        static::assertCount(1, $indexInfo['fields'], 'There should only be 1 indexed field');
        static::assertEquals('fulltextfield', $indexInfo['fields'][0], "The indexed field is not 'fulltextfield'");
        static::assertEquals(5, $indexInfo[CollectionHandler::OPTION_MIN_LENGTH], 'minLength was not set to 5!');
    }


    /**
     * Create a skiplist index and verify it by getting information about the index from the server
     */
    public function testCreateSkipListIndex()
    {
        $result = $this->collectionHandler->createSkipListIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['skiplistfield1', 'skiplistfield2'],
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'skip-list index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_SKIPLIST_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            "Index type is not 'skip-list'!"
        );
        static::assertCount(2, $indexInfo['fields'], 'There should only be 2 indexed field');
        static::assertEquals('skiplistfield1', $indexInfo['fields'][0], "The indexed field is not 'skiplistfield1'");
        static::assertEquals('skiplistfield2', $indexInfo['fields'][1], "The indexed field is not 'skiplistfield2'");
        static::assertTrue($indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to true!');
        static::assertFalse($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to false!');
    }


    /**
     * Create a sparse skiplist index and verify it by getting information about the index from the server
     */
    public function testCreateSparseSkipListIndex()
    {
        $result = $this->collectionHandler->createSkipListIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['skiplistfield1', 'skiplistfield2'],
            false,
            ['sparse' => true]
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'skip-list index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_SKIPLIST_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            "Index type is not 'skip-list'!"
        );
        static::assertCount(2, $indexInfo['fields'], 'There should only be 2 indexed field');
        static::assertEquals('skiplistfield1', $indexInfo['fields'][0], "The indexed field is not 'skiplistfield1'");
        static::assertEquals('skiplistfield2', $indexInfo['fields'][1], "The indexed field is not 'skiplistfield2'");
        static::assertFalse($indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to false!');
        static::assertTrue($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to true!');
    }


    /**
     * Create a persistent index and verify it by getting information about the index from the server
     */
    public function testCreatePersistentIndex()
    {
        $result = $this->collectionHandler->createPersistentIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['field1', 'field2'],
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'persistent index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_PERSISTENT_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            "Index type is not 'persistent'!"
        );
        static::assertCount(2, $indexInfo['fields'], 'There should only be 2 indexed fields');
        static::assertEquals('field1', $indexInfo['fields'][0], "The indexed field is not 'field1'");
        static::assertEquals('field2', $indexInfo['fields'][1], "The indexed field is not 'field2'");
        static::assertTrue($indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to true!');
        static::assertFalse($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to false!');
    }


    /**
     * Create a sparse persistent index and verify it by getting information about the index from the server
     */
    public function testCreateSparsePersistentIndex()
    {
        $result = $this->collectionHandler->createPersistentIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['field1', 'field2'],
            false,
            ['sparse' => true]
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'persistent index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_PERSISTENT_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            "Index type is not 'persistent'!"
        );
        static::assertCount(2, $indexInfo['fields'], 'There should only be 2 indexed fields');
        static::assertEquals('field1', $indexInfo['fields'][0], "The indexed field is not 'field1'");
        static::assertEquals('field2', $indexInfo['fields'][1], "The indexed field is not 'field2'");
        static::assertFalse($indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to false!');
        static::assertTrue($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to true!');
    }
    
    
    /**
     * Create a TTL index and verify it by getting information about the index from the server
     */
    public function testCreateTtlIndex()
    {
        $result = $this->collectionHandler->createTtlIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['expireStamp'],
            60
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'TTL index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_TTL_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            "Index type is not 'ttl'!"
        );
        static::assertCount(1, $indexInfo['fields'], 'There should only be 1 indexed field');
        static::assertEquals('expireStamp', $indexInfo['fields'][0], "The indexed field is not 'expireStamp'");
        static::assertEquals(60, $indexInfo[CollectionHandler::OPTION_EXPIRE_AFTER]);
        static::assertFalse($indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to false!');
        static::assertTrue($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to false!');
    }


    /**
     * Test creating an index and getting it to verify.
     */
    public function testGetIndex()
    {
        $result = $this->collectionHandler->createFulltextIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['testGetIndexField'],
            100
        );

        //Parse for the index's key
        $key = str_replace('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp . '/', '', $result['id']);

        $indexInfo = $this->collectionHandler->getIndex('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp, $key);

        static::assertEquals(
            CollectionHandler::OPTION_FULLTEXT_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            'Index type does not match!'
        );
        static::assertCount(1, $indexInfo['fields'], 'There should only be 1 indexed field!');
        static::assertEquals('testGetIndexField', $indexInfo['fields'][0], 'Index field does not match!');
        static::assertEquals(100, $indexInfo[CollectionHandler::OPTION_MIN_LENGTH], 'Min length does not match!');
    }
    
    
    /**
     * Create an index in background and verify it by getting information about the index from the server
     */
    public function testCreateIndexInBackground()
    {
        $result = $this->collectionHandler->createHashIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp,
            ['test'],
            false, 
            false, 
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers);

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals(
            CollectionHandler::OPTION_HASH_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE]
        );
        static::assertEquals(['test'], $indexInfo['fields']);
        static::assertFalse($indexInfo[CollectionHandler::OPTION_UNIQUE], 'unique was not set to false!');
        static::assertFalse($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to false!');
    }

    public function testHasCollectionReturnsFalseIfCollectionDoesNotExist()
    {
        static::assertFalse($this->collectionHandler->has('just_a_stupid_collection_id_which_does_not_exist'));
    }

    public function testHasCollectionReturnsTrueIfCollectionExists()
    {
        static::assertTrue($this->collectionHandler->has('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp));
    }
    
    
    /**
     * get shards
     */
    public function testGetShards() 
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setNumberOfShards(5);

        $collectionHandler->create($collection);

        $shardIds = $collectionHandler->getShards($collection);
        static::assertEquals(5, count($shardIds));

        foreach ($shardIds as $shardId) {
            static::assertTrue(is_string($shardId));
        }
    }
    
    /**
     * find responsible shard
     */
    public function testGetResponsibleShard()
    {
        if (!isCluster($this->connection)) {
            // don't execute this test in a non-cluster
            $this->markTestSkipped("test is only meaningful in cluster");
            return;
        }

        $connection        = $this->connection;
        $collection        = new Collection();
        $collectionHandler = new CollectionHandler($connection);
        $documentHandler   = new DocumentHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp;

        try {
            $collectionHandler->drop($name);
        } catch (Exception $e) {
            //Silence the exception
        }

        $collection->setName($name);
        $collection->setNumberOfShards(5);

        $response = $collectionHandler->create($collection);
        
        $shardIds = $collectionHandler->getShards($collection);

        for ($i = 0; $i < 100; ++$i) {
            $doc = new Document();
            $doc->setInternalKey('test' . $i);

            $documentHandler->save($collection, $doc);
            
            $responsible = $collectionHandler->getResponsibleShard($collection, $doc);
            static::assertTrue(in_array($responsible, $shardIds));
        }
    }

    public function tearDown()
    {
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_IndexTestCollection' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
            //Silence the exception
        }
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
            //Silence the exception
        }
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
            //Silence the exception
        }
        unset($this->collectionHandler, $this->connection);
    }
}
