<?php
/**
 * ArangoDB PHP client testsuite
 * File: GraphBasicTest.php
 *
 * @package ArangoDBClient
 * @author  Frank Mayer
 */

namespace ArangoDBClient;

/**
 * Class GraphBasicTest
 * Basic Tests for the Graph API implementation
 *
 * @property Connection        $connection
 * @property Graph             $graph
 * @property Collection        $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property GraphHandler      $graphHandler
 * @property DocumentHandler   $documentHandler
 * @property EdgeHandler       $edgeHandler
 *
 * @package ArangoDBClient
 */
class GraphBasicTest extends
    \PHPUnit_Framework_TestCase
{
    protected static $testsTimestamp;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        static::$testsTimestamp = str_replace('.', '_', (string) microtime(true));
    }


    public function setUp(): void
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);
    }
    
    
    public function testInstantiateSmartGraphWithoutSmartGraphAttribute()
    {
        try {
            new SmartGraph('Graph1' . '_' . static::$testsTimestamp);
            static::assertTrue(false, "should not get here");
        } catch (\Exception $e) {
            static::assertInstanceOf(ClientException::class, $e);
        } 
    }
    
    
    public function testInstantiateRegularSmartGraphWithSmartGraphAttribute()
    {
        $graph = new SmartGraph('Graph1' . '_' . static::$testsTimestamp, [ "smartGraphAttribute" => "testi" ]);
        static::assertEquals("testi", $graph->getSmartGraphAttribute());
        static::assertTrue($graph->isSmart());
        static::assertFalse($graph->isDisjoint());
    }
    
    
    public function testUnsetSmartnessOfSmartGraph()
    {
        $graph = new SmartGraph('Graph1' . '_' . static::$testsTimestamp, [ "smartGraphAttribute" => "testi" ]);
        try {
            $graph->setIsSmart(false);
            static::assertTrue(false, "should not get here");
        } catch (\Exception $e) {
            static::assertInstanceOf(ClientException::class, $e);
        } 
    }
    
    
    public function testInstantiateDisjointSmartGraph()
    {
        $graph = new SmartGraph('Graph1' . '_' . static::$testsTimestamp, [ "smartGraphAttribute" => "testi", "isDisjoint" => true ]);
        static::assertEquals("testi", $graph->getSmartGraphAttribute());
        static::assertTrue($graph->isSmart());
        static::assertTrue($graph->isDisjoint());
    }
    
    
    public function testCreateAndDeleteSmartRegularGraph()
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
        
        $param1      = [];
        $param1[]    = 'lba' . '_' . static::$testsTimestamp;
        $param1[]    = 'blub' . '_' . static::$testsTimestamp;
        $param2      = [];
        $param2[]    = 'bla' . '_' . static::$testsTimestamp;
        $param2[]    = 'blob' . '_' . static::$testsTimestamp;
        $ed1         = EdgeDefinition::createDirectedRelation('directed' . '_' . static::$testsTimestamp, $param1, $param2);
        $ed2         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph = new SmartGraph('Graph1' . '_' . static::$testsTimestamp, [ "smartGraphAttribute" => "testi" ]);
        $this->graph->addEdgeDefinition($ed1);
        $this->graph->addEdgeDefinition($ed2);
        static::assertEquals("testi", $this->graph->getSmartGraphAttribute());
        static::assertTrue($this->graph->isSmart());
        static::assertFalse($this->graph->isDisjoint());
        $this->graphHandler = new GraphHandler($this->connection);
        $result             = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1' . '_' . static::$testsTimestamp);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $properties['_key'], 'Did not return Graph1!');
        static::assertTrue($properties['isSmart']);
        static::assertFalse($properties['isDisjoint']);
        static::assertEquals('testi', $properties['smartGraphAttribute']);
    }
    
    
    public function testCreateAndDeleteDisjointSmartGraph()
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
        
        $param1      = [];
        $param1[]    = 'lba' . '_' . static::$testsTimestamp;
        $param1[]    = 'blub' . '_' . static::$testsTimestamp;
        $param2      = [];
        $param2[]    = 'bla' . '_' . static::$testsTimestamp;
        $param2[]    = 'blob' . '_' . static::$testsTimestamp;
        $ed1         = EdgeDefinition::createDirectedRelation('directed' . '_' . static::$testsTimestamp, $param1, $param2);
        $ed2         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph = new SmartGraph('Graph1' . '_' . static::$testsTimestamp, [ "smartGraphAttribute" => "testi", "isDisjoint" => true ]);
        $this->graph->addEdgeDefinition($ed1);
        $this->graph->addEdgeDefinition($ed2);
        static::assertEquals("testi", $this->graph->getSmartGraphAttribute());
        static::assertTrue($this->graph->isSmart());
        static::assertTrue($this->graph->isDisjoint());
        $this->graphHandler = new GraphHandler($this->connection);
        $result             = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1' . '_' . static::$testsTimestamp);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $properties['_key'], 'Did not return Graph1!');
        static::assertTrue($properties['isSmart']);
        static::assertTrue($properties['isDisjoint']);
        static::assertEquals('testi', $properties['smartGraphAttribute']);
    }


    public function testCreateAndDeleteHybridSmartGraph()
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
        
        $param1      = [];
        $param1[]    = 'lba' . '_' . static::$testsTimestamp;
        $param1[]    = 'blub' . '_' . static::$testsTimestamp;
        $param2      = [];
        $param2[]    = 'bla' . '_' . static::$testsTimestamp;
        $param2[]    = 'blob' . '_' . static::$testsTimestamp;
        $ed1         = EdgeDefinition::createDirectedRelation('directed' . '_' . static::$testsTimestamp, $param1, $param2, ['directed_' . static::$testsTimestamp]);
        $ed2         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph = new SmartGraph('Graph1' . '_' . static::$testsTimestamp, [ "smartGraphAttribute" => "testi" ]);
        $this->graph->addEdgeDefinition($ed1);
        $this->graph->addEdgeDefinition($ed2);

        static::assertEquals("testi", $this->graph->getSmartGraphAttribute());
        static::assertTrue($this->graph->isSmart());
        static::assertFalse($this->graph->isDisjoint());
        $this->graphHandler = new GraphHandler($this->connection);
        $result             = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1' . '_' . static::$testsTimestamp);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $properties['_key'], 'Did not return Graph1!');
        static::assertTrue($properties['isSmart']);
        static::assertFalse($properties['isDisjoint']);
        static::assertEquals('testi', $properties['smartGraphAttribute']);
    }
    
    
    public function testCreateAndDeleteSatelliteGraph()
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
        
        $param1      = [];
        $param1[]    = 'lba' . '_' . static::$testsTimestamp;
        $param1[]    = 'blub' . '_' . static::$testsTimestamp;
        $param2      = [];
        $param2[]    = 'bla' . '_' . static::$testsTimestamp;
        $param2[]    = 'blob' . '_' . static::$testsTimestamp;
        $ed1         = EdgeDefinition::createDirectedRelation('directed' . '_' . static::$testsTimestamp, $param1, $param2);
        $ed2         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph = new Graph('Graph1' . '_' . static::$testsTimestamp, [ "replicationFactor" => "satellite" ]);
        $this->graph->addEdgeDefinition($ed1);
        $this->graph->addEdgeDefinition($ed2);
        static::assertFalse($this->graph->isSmart());
        static::assertFalse($this->graph->isDisjoint());
        static::assertEquals(1, $this->graph->getNumberOfShards());
        static::assertEquals("satellite", $this->graph->getReplicationFactor());
        $this->graphHandler = new GraphHandler($this->connection);
        $result             = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1' . '_' . static::$testsTimestamp);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $properties['_key'], 'Did not return Graph1!');
        static::assertFalse($properties['isSmart']);
        static::assertTrue($properties['isSatellite']);
        static::assertEquals("satellite", $properties['replicationFactor']);
        static::assertEquals(1, $properties['numberOfShards']);
    }
    
    
    public function testCreateAndDeleteGraphWithClusterOptions()
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
        
        $param1      = [];
        $param1[]    = 'lba' . '_' . static::$testsTimestamp;
        $param1[]    = 'blub' . '_' . static::$testsTimestamp;
        $param2      = [];
        $param2[]    = 'bla' . '_' . static::$testsTimestamp;
        $param2[]    = 'blob' . '_' . static::$testsTimestamp;
        $ed1         = EdgeDefinition::createDirectedRelation('directed' . '_' . static::$testsTimestamp, $param1, $param2);
        $ed2         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph = new Graph('Graph1' . '_' . static::$testsTimestamp, [ "replicationFactor" => 2, "numberOfShards" => 4 ]);
        $this->graph->addEdgeDefinition($ed1);
        $this->graph->addEdgeDefinition($ed2);
        static::assertFalse($this->graph->isSmart());
        static::assertFalse($this->graph->isDisjoint());
        static::assertEquals(2, $this->graph->getReplicationFactor());
        static::assertEquals(4, $this->graph->getNumberOfShards());
        $this->graphHandler = new GraphHandler($this->connection);
        $result             = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1' . '_' . static::$testsTimestamp);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $properties['_key'], 'Did not return Graph1!');
        static::assertFalse($properties['isSmart']);
        static::assertEquals(2, $properties['replicationFactor']);
        static::assertEquals(4, $properties['numberOfShards']);
    }
    
    
    /**
     * Test creation of graph with definitions
     */
    public function testCreateAndDeleteGraphsWithDefinitions()
    {
        $param1      = [];
        $param1[]    = 'lba' . '_' . static::$testsTimestamp;
        $param1[]    = 'blub' . '_' . static::$testsTimestamp;
        $param2      = [];
        $param2[]    = 'bla' . '_' . static::$testsTimestamp;
        $param2[]    = 'blob' . '_' . static::$testsTimestamp;
        $ed1         = EdgeDefinition::createDirectedRelation('directed' . '_' . static::$testsTimestamp, $param1, $param2);
        $ed2         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph = new Graph();
        $this->graph->set('_key', 'Graph1' . '_' . static::$testsTimestamp);
        $this->graph->addEdgeDefinition($ed1);
        $this->graph->addEdgeDefinition($ed2);
        $this->graph->addOrphanCollection('orphan' . '_' . static::$testsTimestamp);
        $this->graphHandler = new GraphHandler($this->connection);
        $result             = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1' . '_' . static::$testsTimestamp);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $properties['_key'], 'Did not return Graph1!');

        $result = $this->graphHandler->dropGraph('Graph1' . '_' . static::$testsTimestamp);
        static::assertTrue($result, 'Did not return true!');
    }

    /**
     * Test creation of graph with definitions and with old structure
     */
    public function testCreationOfGraphObject()
    {

        $ed1         = EdgeDefinition::createUndirectedRelation('ArangoDB_PHP_TestSuite_TestEdgeCollection_01' . '_' . static::$testsTimestamp, ['ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp]);
        $this->graph = new Graph('Graph1' . '_' . static::$testsTimestamp);
        static::assertCount(0, $this->graph->getEdgeDefinitions());
        $this->graph->addEdgeDefinition($ed1);
        static::assertCount(1, $this->graph->getEdgeDefinitions());
        $ed = $this->graph->getEdgeDefinitions();
        $ed = $ed[0];
        $a  = $ed->getToCollections();
        $b  = $ed->getFromCollections();
        static::assertSame('ArangoDB_PHP_TestSuite_TestEdgeCollection_01' . '_' . static::$testsTimestamp, $ed->getRelation());
        static::assertSame('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp, $a[0]);
        static::assertSame('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp, $b[0]);
        $ed = $this->graph->getEdgeDefinitions();
        $ed = $ed[0];
        $ed->addFromCollection('newFrom' . '_' . static::$testsTimestamp);
        $ed->addToCollection('newTo' . '_' . static::$testsTimestamp);

        static::assertCount(2, $ed->getFromCollections());
        static::assertCount(2, $ed->getToCollections());

        $this->graph->addOrphanCollection('o1' . '_' . static::$testsTimestamp);
        $this->graph->addOrphanCollection('o2' . '_' . static::$testsTimestamp);
        static::assertCount(2, $this->graph->getOrphanCollections());

    }

    /**
     * Test if Graph and GraphHandler instances can be initialized when we directly set the graph name in the constructor
     */
    public function testCreateAndDeleteGraphByName()
    {
        $ed1         = EdgeDefinition::createUndirectedRelation('ArangoDB_PHP_TestSuite_TestEdgeCollection_02' . '_' . static::$testsTimestamp, ['ArangoDB_PHP_TestSuite_TestCollection_02' . '_' . static::$testsTimestamp]);
        $this->graph = new Graph('Graph2' . '_' . static::$testsTimestamp);
        $this->graph->addEdgeDefinition($ed1);
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph2' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph2!');

        $properties = $this->graphHandler->properties('Graph2' . '_' . static::$testsTimestamp);
        static::assertEquals('Graph2' . '_' . static::$testsTimestamp, $properties['_key'], 'Did not return Graph2!');

        $result = $this->graphHandler->dropGraph('Graph2' . '_' . static::$testsTimestamp);
        static::assertTrue($result, 'Did not return true!');
    }

    /**
     * Test if we can create a graph and then retrieve it from the server
     */
    public function testCreateRetrieveAndDeleteGraph1()
    {
        $ed1         = EdgeDefinition::createUndirectedRelation('ArangoDB_PHP_TestSuite_TestEdge_Collection_03' . '_' . static::$testsTimestamp, ['ArangoDB_PHP_TestSuite_TestCollection_03' . '_' . static::$testsTimestamp]);
        $this->graph = new Graph('Graph3' . '_' . static::$testsTimestamp);
        $this->graph->addEdgeDefinition($ed1);
        $this->graph->addOrphanCollection('orphan' . '_' . static::$testsTimestamp);
        $this->graphHandler = new GraphHandler($this->connection);
        $this->graphHandler->createGraph($this->graph);
        $graph = $this->graphHandler->getGraph('Graph3' . '_' . static::$testsTimestamp);
        static::assertEquals('Graph3' . '_' . static::$testsTimestamp, $graph->getKey(), 'Did not return Graph3!');
        $result = $this->graphHandler->dropGraph('Graph3' . '_' . static::$testsTimestamp);
        static::assertTrue($result, 'Did not return true!');
    }


    /**
     * Test if a graph can be created and then destroyed by giving an instance of Graph
     */
    public function testGetPropertiesAndDeleteGraphByInstance()
    {
        $ed1         = EdgeDefinition::createUndirectedRelation('ArangoDB_PHP_TestSuite_TestEdge_Collection_04' . '_' . static::$testsTimestamp, ['ArangoDB_PHP_TestSuite_TestCollection_04']);
        $this->graph = new Graph('Graph4' . '_' . static::$testsTimestamp);
        $this->graph->addEdgeDefinition($ed1);
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph4' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph4!');

        $properties = $this->graphHandler->properties($this->graph);
        static::assertEquals('Graph4' . '_' . static::$testsTimestamp, $properties['_key'], 'Did not return Graph4!');

        $result = $this->graphHandler->dropGraph($this->graph);
        static::assertTrue($result, 'Did not return true!');
    }

    /**
     * Test get non existing graph
     */
    public function testGetNonExistingGraph()
    {
        $this->graphHandler = new GraphHandler($this->connection);
        $result             = $this->graphHandler->getGraph('not a graph');
        static::assertFalse($result);
    }


    /**
     * Test adding, getting and deleting of collections
     */
    public function testAddGetDeleteCollections()
    {
        $this->graph = new Graph('Graph1' . '_' . static::$testsTimestamp);
        $ed1         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph->addOrphanCollection('ArangoDB_PHP_TestSuite_TestCollection_04' . '_' . static::$testsTimestamp);
        $this->graph->addEdgeDefinition($ed1);
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');

        $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'orphan1' . '_' . static::$testsTimestamp);
        $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'orphan2' . '_' . static::$testsTimestamp);

        static::assertSame(
            [
                0 => 'ArangoDB_PHP_TestSuite_TestCollection_04' . '_' . static::$testsTimestamp,
                1 => 'orphan1' . '_' . static::$testsTimestamp,
                2 => 'orphan2' . '_' . static::$testsTimestamp,
                3 => 'singleV' . '_' . static::$testsTimestamp
            ],
            $this->graphHandler->getVertexCollections($this->graph)
        );
        $this->graph = $this->graphHandler->deleteOrphanCollection($this->graph, 'orphan2' . '_' . static::$testsTimestamp);
        static::assertSame(
            [
                0 => 'ArangoDB_PHP_TestSuite_TestCollection_04' . '_' . static::$testsTimestamp,
                1 => 'orphan1' . '_' . static::$testsTimestamp,
                2 => 'singleV' . '_' . static::$testsTimestamp
            ],
            $this->graphHandler->getVertexCollections($this->graph)
        );
        $error = null;
        try {
            $this->graph = $this->graphHandler->deleteOrphanCollection($this->graph, 'singleV' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertMatchesRegularExpression('/orphan collection/', $error);

        $error = null;
        try {
            $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'undirected' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        static::assertMatchesRegularExpression('/not a vertex collection/', $error);

        $error = null;
        try {
            $this->graph = $this->graphHandler->getVertexCollections('notExisting');
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame('graph \'notExisting\' not found', $error);

        $result = $this->graphHandler->dropGraph($this->graph);
        static::assertTrue($result, 'Did not return true!');
    }

    /**
     * Test adding, getting and deleting of collections
     */
    public function testAddGetDeleteCollectionsWithCache()
    {
        $this->graph = new Graph('Graph1' . '_' . static::$testsTimestamp);
        $ed1         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph->addOrphanCollection('ArangoDB_PHP_TestSuite_TestCollection_04' . '_' . static::$testsTimestamp);
        $this->graph->addEdgeDefinition($ed1);
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');

        $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'orphan1' . '_' . static::$testsTimestamp);
        $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'orphan2' . '_' . static::$testsTimestamp);

        $this->graphHandler->setCacheEnabled(true);
        static::assertSame(
            [
                0 => 'ArangoDB_PHP_TestSuite_TestCollection_04' . '_' . static::$testsTimestamp,
                1 => 'orphan1' . '_' . static::$testsTimestamp,
                2 => 'orphan2' . '_' . static::$testsTimestamp,
                3 => 'singleV' . '_' . static::$testsTimestamp

            ],
            $this->graphHandler->getVertexCollections($this->graph)
        );

        $this->graph = $this->graphHandler->deleteOrphanCollection($this->graph, 'orphan2' . '_' . static::$testsTimestamp);
        static::assertSame(
            [
                0 => 'ArangoDB_PHP_TestSuite_TestCollection_04' . '_' . static::$testsTimestamp,
                1 => 'orphan1' . '_' . static::$testsTimestamp,
                2 => 'orphan2' . '_' . static::$testsTimestamp,
                3 => 'singleV' . '_' . static::$testsTimestamp

            ],
            $this->graphHandler->getVertexCollections($this->graph)
        );

        $this->graphHandler->setCacheEnabled(false);
        static::assertSame(
            [
                0 => 'ArangoDB_PHP_TestSuite_TestCollection_04' . '_' . static::$testsTimestamp,
                1 => 'orphan1' . '_' . static::$testsTimestamp,
                2 => 'singleV' . '_' . static::$testsTimestamp

            ],
            $this->graphHandler->getVertexCollections($this->graph)
        );
        $error = null;
        try {
            $this->graph = $this->graphHandler->deleteOrphanCollection($this->graph, 'singleV' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertMatchesRegularExpression('/orphan collection/', $error);

        $error = null;
        try {
            $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'undirected' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertMatchesRegularExpression('/not a vertex collection/', $error);

        $error = null;
        try {
            $this->graph = $this->graphHandler->getVertexCollections('notExisting');
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame('graph \'notExisting\' not found', $error);

        $result = $this->graphHandler->dropGraph($this->graph);
        static::assertTrue($result, 'Did not return true!');
    }


    /**
     * Test adding, getting and deleting of edgecollections
     */
    public function testAddGetDeleteEdgeCollections()
    {
        $this->graph = new Graph('Graph1' . '_' . static::$testsTimestamp);
        $ed1         = EdgeDefinition::createUndirectedRelation('undirected' . '_' . static::$testsTimestamp, 'singleV' . '_' . static::$testsTimestamp);
        $this->graph->addEdgeDefinition($ed1);
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        static::assertEquals('Graph1' . '_' . static::$testsTimestamp, $result['_key'], 'Did not return Graph1!');


        $this->graph = $this->graphHandler->addEdgeDefinition(
            $this->graph,
            EdgeDefinition::createUndirectedRelation('undirected2' . '_' . static::$testsTimestamp, 'singleV2' . '_' . static::$testsTimestamp)
        );

        $edgeCollections = $this->graphHandler->getEdgeCollections($this->graph);

        static::assertContains('undirected' . '_' . static::$testsTimestamp, $edgeCollections);
        static::assertContains('undirected2' . '_' . static::$testsTimestamp, $edgeCollections);


        $error = null;
        try {
            $this->graph = $this->graphHandler->addEdgeDefinition(
                $this->graph,
                EdgeDefinition::createUndirectedRelation('undirected2' . '_' . static::$testsTimestamp, 'singleV2' . '_' . static::$testsTimestamp)
            );
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertStringContainsString('multi use of edge collection in edge def', $error);
        $error = null;
        try {
            $this->graph = $this->graphHandler->getEdgeCollections('bla' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame('graph \'bla_' . static::$testsTimestamp . '\' not found', $error);

        $this->graph = $this->graphHandler->deleteEdgeDefinition(
            $this->graph,
            'undirected' . '_' . static::$testsTimestamp
        );

        static::assertSame(
            [
                0 => 'undirected2' . '_' . static::$testsTimestamp
            ],
            $this->graphHandler->getEdgeCollections($this->graph)
        );

        $error = null;
        try {
            $this->graph = $this->graphHandler->deleteEdgeDefinition('bla' . '_' . static::$testsTimestamp, 'undefined' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame('graph \'bla_' . static::$testsTimestamp . '\' not found', $error);

        $this->graph = $this->graphHandler->replaceEdgeDefinition(
            $this->graph,
            EdgeDefinition::createUndirectedRelation('undirected2' . '_' . static::$testsTimestamp, 'singleV3' . '_' . static::$testsTimestamp)
        );

        $ed = $this->graph->getEdgeDefinitions();
        $ed = $ed[0];
        $ed = $ed->getToCollections();
        static::assertSame('singleV3' . '_' . static::$testsTimestamp, $ed[0]);

        $error = null;
        try {
            $this->graph = $this->graphHandler->replaceEdgeDefinition(
                $this->graph,
                EdgeDefinition::createUndirectedRelation('notExisting' . '_' . static::$testsTimestamp, 'singleV3' . '_' . static::$testsTimestamp)
            );
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame('edge collection not used in graph', $error);

    }


    public function tearDown(): void
    {
        $this->graphHandler = new GraphHandler($this->connection);
        try {
            $this->graphHandler->dropGraph('Graph1' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('Graph2' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('Graph3' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('Graph4' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->collectionHandler->drop('orphan' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->collectionHandler->drop('orphan1' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->collectionHandler->drop('orphan2' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->collectionHandler->drop('undirected' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->collectionHandler->drop('undirected2' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        unset($this->graph, $this->graphHandler, $this->connection);
    }
}
