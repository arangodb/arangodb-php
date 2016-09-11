<?php
/**
 * ArangoDB PHP client testsuite
 * File: GraphBasicTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

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
 * @package triagens\ArangoDb
 */
class GraphBasicTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
    }


    /**
     * Test if Edge and EdgeHandler instances can be initialized
     */
    public function testCreateAndDeleteGraphs()
    {
        $this->graph = new Graph();
        $this->graph->set('_key', 'Graph1');
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection01');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->graphHandler = new GraphHandler($this->connection);
        $result = $this->graphHandler->createGraph($this->graph);
        $this->assertTrue($result['_key'] == 'Graph1', 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1');
        $this->assertTrue($properties['_key'] == 'Graph1', 'Did not return Graph1!');

        $result = $this->graphHandler->dropGraph('Graph1');
        $this->assertTrue($result, 'Did not return true!');
    }
    
    /**
     * Test creation of graph with defintions
     */
    public function testCreateAndDeleteGraphsWithDefintions()
    {	
    	$param1 = array ();
    	$param1[] = "lba";
    	$param1[] = "blub";
    	$param2 = array ();
    	$param2[] = "bla";
    	$param2[] = "blob";
    	$ed1 = EdgeDefinition::createDirectedRelation("directed", $param1, $param2);
    	$ed2 = EdgeDefinition::createUndirectedRelation("undirected", "singleV");
    	$this->graph = new Graph();
    	$this->graph->set('_key', 'Graph1');
    	$this->graph->addEdgeDefinition($ed1);
    	$this->graph->addEdgeDefinition($ed2);
    	$this->graph->addOrphanCollection("orphan");
    	$this->graphHandler = new GraphHandler($this->connection);
    	$result = $this->graphHandler->createGraph($this->graph);
    	$this->assertTrue($result['_key'] == 'Graph1', 'Did not return Graph1!');
    	$properties = $this->graphHandler->properties('Graph1');
    	$this->assertTrue($properties['_key'] == 'Graph1', 'Did not return Graph1!');
    
    	$result = $this->graphHandler->dropGraph('Graph1');
    	$this->assertTrue($result, 'Did not return true!');
    }
    
    /**
     * Test creation of graph with defintions and with old structure
     */
    public function testCreationOfGraphObject()
    {
    	 
    	$this->graph = new Graph('Graph1');
        $this->assertTrue($this->graph->getVerticesCollection() === null);
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection01');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->assertTrue($this->graph->getEdgesCollection() === 'ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->assertTrue($this->graph->getVerticesCollection() === 'ArangoDBPHPTestSuiteTestCollection01');
        $this->assertTrue(count($this->graph->getEdgeDefinitions()) === 1);
        $ed = $this->graph->getEdgeDefinitions();
        $ed = $ed[0];
        $a = $ed->getToCollections();
        $b = $ed->getFromCollections();
        $this->assertTrue($ed->getRelation() === 'ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->assertTrue($a[0] === 'ArangoDBPHPTestSuiteTestCollection01');
        $this->assertTrue($b[0] === 'ArangoDBPHPTestSuiteTestCollection01');
        $ed = $this->graph->getEdgeDefinitions();
        $ed = $ed[0];
        $ed->addFromCollection("newFrom");
        $ed->addToCollection("newTo");
        
        $this->assertTrue(count($ed->getFromCollections()) === 2);
        $this->assertTrue(count($ed->getToCollections()) === 2);
        
        $exception = null;
    	try {
            $this->graph->getVerticesCollection();
        } catch (Exception $e) {
        	$exception = $e->getMessage();
        }
        $this->assertTrue("This operation only supports graphs with one undirected single collection relation" === $exception);
        $exception = null;
        try {
        	$this->graph->getEdgesCollection();
        } catch (Exception $e) {
        	$exception = $e->getMessage();
        }
        $this->assertTrue('This operation only supports graphs with one undirected single collection relation' === $exception);
        
        $this->graph->addOrphanCollection('o1');
        $this->graph->addOrphanCollection('o2');
        $this->assertTrue(count($this->graph->getOrphanCollections()) === 2);
        
    }

    /**
     * Test if Edge and EdgeHandler instances can be initialized when we directly set the graph name in the constructor
     */
    public function testCreateAndDeleteGraphByName()
    {
        $this->graph = new Graph('Graph2');
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection02');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection02');
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        $this->assertTrue($result['_key'] == 'Graph2', 'Did not return Graph2!');

        $properties = $this->graphHandler->properties('Graph2');
        $this->assertTrue($properties['_key'] == 'Graph2', 'Did not return Graph2!');

        $result = $this->graphHandler->dropGraph('Graph2');
        $this->assertTrue($result, 'Did not return true!');
    }

    /**
     * Test if we can create a graph and then retrieve it from the server
     */
    public function testCreateRetrieveAndDeleteGraph1()
    {
        $this->graph = new Graph('Graph3');
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection03');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection03');
        $this->graph->addOrphanCollection("orphan");
        $this->graphHandler = new GraphHandler($this->connection);
        $this->graphHandler->createGraph($this->graph);
        $graph = $this->graphHandler->getGraph('Graph3');
        $this->assertTrue($graph->getKey() == 'Graph3', 'Did not return Graph3!');
        $result = $this->graphHandler->dropGraph('Graph3');
        $this->assertTrue($result, 'Did not return true!');
    }
    
    
    /**
     * Test if a graph can be created and then destroyed by giving an instance of Graph
     */
    public function testGetPropertiesAndDeleteGraphByInstance()
    {
        $this->graph = new Graph('Graph4');
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection04');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection04');
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        $this->assertTrue($result['_key'] == 'Graph4', 'Did not return Graph4!');

        $properties = $this->graphHandler->properties($this->graph);
        $this->assertTrue($properties['_key'] == 'Graph4', 'Did not return Graph4!');

        $result = $this->graphHandler->dropGraph($this->graph);
        $this->assertTrue($result, 'Did not return true!');
    }

    /**
     * Test get non existing graph
     */
    public function testGetNonExistingGraph()
    {
    	$this->graphHandler = new GraphHandler($this->connection);
       	$result = $this->graphHandler->getGraph("not a graph");
    	$this->assertFalse($result);
    }
    
    
    /**
    * Test adding, getting and deleting of collections
    */
    public function testAddGetDeleteCollections()
    {
    	$this->graph = new Graph('Graph1');
    	$ed1 = EdgeDefinition::createUndirectedRelation("undirected", "singleV");
    	$this->graph->addOrphanCollection('ArangoDBPHPTestSuiteTestCollection04');
    	$this->graph->addEdgeDefinition($ed1);
    	$this->graphHandler = new GraphHandler($this->connection);
    
    	$result = $this->graphHandler->createGraph($this->graph);
    	$this->assertTrue($result['_key'] == 'Graph1', 'Did not return Graph1!');
    	
    	$this->graph = $this->graphHandler->addOrphanCollection($this->graph, "orphan1");
    	$this->graph = $this->graphHandler->addOrphanCollection($this->graph, "orphan2");
    	
    	$this->assertTrue($this->graphHandler->getVertexCollections($this->graph) === array(
    			0 => 'ArangoDBPHPTestSuiteTestCollection04',
    			1 => 'orphan1',
    			2 => 'orphan2',
         		3 => 'singleV'
    
    	));
    	$this->graph = $this->graphHandler->deleteOrphanCollection($this->graph, "orphan2");
    	$this->assertTrue($this->graphHandler->getVertexCollections($this->graph) === array(
    			0 => 'ArangoDBPHPTestSuiteTestCollection04',
    			1 => 'orphan1',
    			2 => 'singleV'
    	
    	));
    	$error = null;
    	try {
    		$this->graph = $this->graphHandler->deleteOrphanCollection($this->graph, "singleV");
    	} catch (\Exception $e) {
    		$error = $e->getMessage();	
    	}
    	$this->assertTrue($error === "not in orphan collection"); 
    	
    	$error = null;
    	try {
    		$this->graph = $this->graphHandler->addOrphanCollection($this->graph, "undirected");
    	} catch (\Exception $e) {
    		$error = $e->getMessage();
    	}
    	
    	$this->assertTrue($error === "not a vertex collection");
    	
    	$error = null;
    	try {
    		$this->graph = $this->graphHandler->getVertexCollections("notExisting");
    	} catch (\Exception $e) {
    		$error = $e->getMessage();
    	}
    	$this->assertTrue($error === "graph not found");
    	
    	$result = $this->graphHandler->dropGraph($this->graph);
    	$this->assertTrue($result, 'Did not return true!');
    }
    
    /**
     * Test adding, getting and deleting of edgecollections
     */
    public function testAddGetDeleteEdgeCollections()
    {
    	$this->graph = new Graph('Graph1');
    	$ed1 = EdgeDefinition::createUndirectedRelation("undirected", "singleV");
    	$this->graph->addEdgeDefinition($ed1);
    	$this->graphHandler = new GraphHandler($this->connection);
    
    	$result = $this->graphHandler->createGraph($this->graph);
    	$this->assertTrue($result['_key'] == 'Graph1', 'Did not return Graph1!');

    	
    	$this->graph = $this->graphHandler->addEdgeDefinition(
    			$this->graph, 
    			EdgeDefinition::createUndirectedRelation("undirected2", "singleV2")
    	);

    	$this->assertTrue($this->graphHandler->getEdgeCollections($this->graph) === array(
    			0 => 'undirected',
    			1 => 'undirected2'
    
    	));
    	
    	$error = null;
    	try {
    		$this->graph = $this->graphHandler->addEdgeDefinition(
    			$this->graph,
    			EdgeDefinition::createUndirectedRelation("undirected2", "singleV2") 
    		);
		} catch (\Exception $e) {
    		$error = $e->getMessage();
		}
		$this->assertTrue($error === "multi use of edge collection in edge def");
    	$error = null;
    	try {
    		$this->graph = $this->graphHandler->getEdgeCollections("bla");
    	} catch (\Exception $e) {
    		$error = $e->getMessage();
    	}
    	$this->assertTrue($error === "graph not found");
    	
    	$this->graph = $this->graphHandler->deleteEdgeDefinition(
    			$this->graph, 
    			"undirected"
    	);
    	
    	$this->assertTrue($this->graphHandler->getEdgeCollections($this->graph) === array(
    			0 => 'undirected2'
    	
    	));
    	
    	$error = null;
    	try {
    		$this->graph = $this->graphHandler->deleteEdgeDefinition("bla", "undefined");
    	} catch (\Exception $e) {
    		$error = $e->getMessage();
    	}
    	$this->assertTrue($error === "graph not found");
    	
    	$this->graph = $this->graphHandler->replaceEdgeDefinition(
    			$this->graph,
    			EdgeDefinition::createUndirectedRelation("undirected2", "singleV3")
    	);
    	
    	$ed = $this->graph->getEdgeDefinitions();
    	$ed = $ed[0];
    	$ed = $ed->getToCollections();
    	$this->assertTrue($ed[0] === "singleV3");
    	
    	$error = null;
    	try {
    		$this->graph = $this->graphHandler->replaceEdgeDefinition(
    				$this->graph, 
    				EdgeDefinition::createUndirectedRelation("notExisting", "singleV3")
    	);
    	} catch (\Exception $e) {
    		$error = $e->getMessage();
    	}
    	$this->assertTrue($error === "edge collection not used in graph");
    	
    }
    
    
    public function tearDown()
    {
        $this->graphHandler = new GraphHandler($this->connection);
        try {
            $this->graphHandler->dropGraph('Graph1');
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('Graph2');
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('Graph3');
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('Graph4');
        } catch (Exception $e) {
        }
        unset($this->graph);
        unset($this->graphHandler);
        unset($this->connection);
    }
}
