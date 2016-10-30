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
 * @property Connection        connection
 * @property Collection        collection
 * @property CollectionHandler collectionHandler
 * @property bool              hasSparseIndexes
 * @property bool              hasSelectivityEstimates
 */
class CollectionBasicTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_IndexTestCollection');
        } catch (Exception $e) {
            //Silence the exception
        }
        $this->collectionHandler->create('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $adminHandler = new AdminHandler($this->connection);
        $version      = preg_replace("/-[a-z0-9]+$/", '', $adminHandler->getServerVersion());

        $this->hasSparseIndexes        = (version_compare($version, '2.5.0') >= 0);
        $this->hasSelectivityEstimates = (version_compare($version, '2.5.0') >= 0);
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
        static::assertInstanceOf('triagens\ArangoDb\Collection', $collection);
        new CollectionHandler($connection);
        static::assertInstanceOf('triagens\ArangoDb\Collection', $collection);
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

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';

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
            ['type' => 'autoincrement', 'allowUserKeys' => false, 'increment' => 5, 'offset' => 10]
        );
        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals($properties[Collection::ENTRY_STATUS], 3, 'Status does not match.');
        static::assertEquals(
            $properties[Collection::ENTRY_KEY_OPTIONS]['type'],
            'autoincrement',
            'Key options type does not match'
        );
        static::assertEquals(
            $properties[Collection::ENTRY_KEY_OPTIONS]['allowUserKeys'],
            false,
            'Key options allowUserKeys does not match'
        );
        static::assertEquals(
            $properties[Collection::ENTRY_KEY_OPTIONS]['increment'],
            5,
            'Key options increment does not match'
        );
        static::assertEquals(
            $properties[Collection::ENTRY_KEY_OPTIONS]['offset'],
            10,
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
            ['type' => 'autoincrement', 'allowUserKeys' => false, 'increment' => 5, 'offset' => 10]
        );

        try {
            $collectionHandler->create($collection);
        } catch (\Exception $e) {
        }

        static::assertEquals($e->getCode(), 501);
    }


    /**
     * Try to create a collection with number of shards
     */
    public function testCreateCollectionWithNumberOfShardsCluster()
    {
        if (!isCluster($this->connection)) {
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

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals($properties[Collection::ENTRY_NUMBER_OF_SHARDS], 4, 'Number of shards does not match.');
        static::assertEquals($properties[Collection::ENTRY_SHARD_KEYS], ['_key'], 'Shard keys do not match.');
    }


    /**
     * Try to create a collection with specified shard keys
     */
    public function testCreateCollectionWithShardKeysCluster()
    {
        if (!isCluster($this->connection)) {
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
        $collection->setShardKeys(['_key', 'a', 'b']);

        $response = $collectionHandler->create($collection);

        $resultingCollection = $collectionHandler->getProperties($response);
        $properties          = $resultingCollection->getAll();

        static::assertEquals($properties[Collection::ENTRY_NUMBER_OF_SHARDS], 1, 'Number of shards does not match.');
        static::assertEquals(
            $properties[Collection::ENTRY_SHARD_KEYS], [
            '_key',
            'a',
            'b'
        ], 'Shard keys do not match.'
        );
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

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02';

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

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02';

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
        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_02';

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
        static::assertTrue($resultingCollectionProperties->getIsVolatile());

        $collectionHandler->drop($name);
    }


    /**
     * Try to create and delete an edge collection not using an edge object
     */
    public function testCreateAndDeleteSystemCollectionWithoutCreatingObject()
    {
        $connection        = $this->connection;
        $collectionHandler = new CollectionHandler($connection);

        $name = '_ArangoDB_PHP_TestSuite_TestCollection_02';

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
     * Create a geo index with 1 field and verify it by getting information about the index from the server
     */
    public function testCreateGeo1Index()
    {
        $result = $this->collectionHandler->createGeoIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['combinedGeo'],
            true,
            true,
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Geo index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals('geo1', $indexInfo[CollectionHandler::OPTION_TYPE], "Index type is not 'geo1'!");
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
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['lat', 'long'],
            false,
            false,
            false
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

        $indicesByIdentifiers = $indices['identifiers'];

        static::assertArrayHasKey($result['id'], $indicesByIdentifiers, 'Geo index was not created!');

        $indexInfo = $indicesByIdentifiers[$result['id']];

        static::assertEquals('geo2', $indexInfo[CollectionHandler::OPTION_TYPE], "Index type is not 'geo2'!");
        static::assertCount(2, $indexInfo['fields'], 'There should only be 2 indexed fields');
        static::assertEquals('lat', $indexInfo['fields'][0], "The first indexed field is not 'lat'");
        static::assertEquals('long', $indexInfo['fields'][1], "The second indexed field is not 'long'");
        static::assertArrayNotHasKey(CollectionHandler::OPTION_GEOJSON, $indexInfo, 'geoJson was set!');
        static::assertEquals(
            false,
            $indexInfo[CollectionHandler::OPTION_CONSTRAINT],
            'constraint was not set to false!'
        );

        if (!array_key_exists(CollectionHandler::OPTION_IGNORE_NULL, $indexInfo)) {
            // downwards-compatibility
            $indexInfo[CollectionHandler::OPTION_IGNORE_NULL] = false;
        }
    }


    /**
     * Create a hash index and verify it by getting information about the index from the server
     */
    public function testCreateHashIndex()
    {
        $result = $this->collectionHandler->createHashIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['hashfield1', 'hashfield2'],
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

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

        if ($this->hasSparseIndexes) {
            static::assertFalse($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to false!');
        }
        if ($this->hasSelectivityEstimates) {
            static::assertTrue(isset($indexInfo['selectivityEstimate']), 'selectivity estimate not present!');
        }
    }


    /**
     * Create a sparse hash index and verify it by getting information about the index from the server
     */
    public function testCreateSparseHashIndex()
    {
        $result = $this->collectionHandler->createHashIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['hashfield1', 'hashfield2'],
            false,
            ['sparse' => true]
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

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

        if ($this->hasSparseIndexes) {
            static::assertTrue($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to true!');
        }
        if ($this->hasSelectivityEstimates) {
            static::assertTrue(isset($indexInfo['selectivityEstimate']), 'selectivity estimate not present!');
        }
    }


    /**
     * Create a fulltext index and verify it by getting information about the index from the server
     */
    public function testCreateFulltextIndex()
    {
        $result = $this->collectionHandler->createFulltextIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['fulltextfield'],
            5
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

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
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['skiplistfield1', 'skiplistfield2'],
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

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
        if ($this->hasSparseIndexes) {
            static::assertFalse($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to false!');
        }
    }


    /**
     * Create a sparse skiplist index and verify it by getting information about the index from the server
     */
    public function testCreateSparseSkipListIndex()
    {
        $result = $this->collectionHandler->createSkipListIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['skiplistfield1', 'skiplistfield2'],
            false,
            ['sparse' => true]
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

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
        if ($this->hasSparseIndexes) {
            static::assertTrue($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to true!');
        }
    }


    /**
     * Create a persistent index and verify it by getting information about the index from the server
     */
    public function testCreatePersistentIndex()
    {
        $result = $this->collectionHandler->createPersistentIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['field1', 'field2'],
            true
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

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
        if ($this->hasSparseIndexes) {
            static::assertFalse($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to false!');
        }
    }


    /**
     * Create a sparse persistent index and verify it by getting information about the index from the server
     */
    public function testCreateSparsePersistentIndex()
    {
        $result = $this->collectionHandler->createPersistentIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['field1', 'field2'],
            false,
            ['sparse' => true]
        );

        $indices = $this->collectionHandler->getIndexes('ArangoDB_PHP_TestSuite_IndexTestCollection');

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
        if ($this->hasSparseIndexes) {
            static::assertTrue($indexInfo[CollectionHandler::OPTION_SPARSE], 'sparse flag was not set to true!');
        }
    }


    /**
     * Test creating an index and getting it to verify.
     */
    public function testGetIndex()
    {
        $result = $this->collectionHandler->createFulltextIndex(
            'ArangoDB_PHP_TestSuite_IndexTestCollection',
            ['testGetIndexField'],
            100
        );

        //Parse for the index's key
        $key = str_replace('ArangoDB_PHP_TestSuite_IndexTestCollection/', '', $result['id']);

        $indexInfo = $this->collectionHandler->getIndex('ArangoDB_PHP_TestSuite_IndexTestCollection', $key);

        static::assertEquals(
            CollectionHandler::OPTION_FULLTEXT_INDEX,
            $indexInfo[CollectionHandler::OPTION_TYPE],
            'Index type does not match!'
        );
        static::assertCount(1, $indexInfo['fields'], 'There should only be 1 indexed field!');
        static::assertEquals('testGetIndexField', $indexInfo['fields'][0], 'Index field does not match!');
        static::assertEquals(100, $indexInfo[CollectionHandler::OPTION_MIN_LENGTH], 'Min length does not match!');
    }

    public function testHasCollectionReturnsFalseIfCollectionDoesNotExist()
    {
        static::assertFalse($this->collectionHandler->has('just_a_stupid_collection_id_which_does_not_exist'));
    }

    public function testHasCollectionReturnsTrueIfCollectionExists()
    {
        static::assertTrue($this->collectionHandler->has('ArangoDB_PHP_TestSuite_IndexTestCollection'));
    }

    public function tearDown()
    {
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_IndexTestCollection');
        } catch (Exception $e) {
            //Silence the exception
        }
        unset($this->collectionHandler, $this->connection);
    }
}
