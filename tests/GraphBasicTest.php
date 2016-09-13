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
 * @property Connection $connection
 * @property Graph $graph
 * @property Collection $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property GraphHandler $graphHandler
 * @property DocumentHandler $documentHandler
 * @property EdgeHandler $edgeHandler
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
        $result             = $this->graphHandler->createGraph($this->graph);
        static::assertEquals($result['_key'], 'Graph1', 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1');
        static::assertEquals($properties['_key'], 'Graph1', 'Did not return Graph1!');

        $result = $this->graphHandler->dropGraph('Graph1');
        static::assertTrue($result, 'Did not return true!');
    }

    /**
     * Test creation of graph with definitions
     */
    public function testCreateAndDeleteGraphsWithDefinitions()
    {
        $param1      = array();
        $param1[]    = 'lba';
        $param1[]    = 'blub';
        $param2      = array();
        $param2[]    = 'bla';
        $param2[]    = 'blob';
        $ed1         = EdgeDefinition::createDirectedRelation('directed', $param1, $param2);
        $ed2         = EdgeDefinition::createUndirectedRelation('undirected', 'singleV');
        $this->graph = new Graph();
        $this->graph->set('_key', 'Graph1');
        $this->graph->addEdgeDefinition($ed1);
        $this->graph->addEdgeDefinition($ed2);
        $this->graph->addOrphanCollection('orphan');
        $this->graphHandler = new GraphHandler($this->connection);
        $result             = $this->graphHandler->createGraph($this->graph);
        static::assertEquals($result['_key'], 'Graph1', 'Did not return Graph1!');
        $properties = $this->graphHandler->properties('Graph1');
        static::assertEquals($properties['_key'], 'Graph1', 'Did not return Graph1!');

        $result = $this->graphHandler->dropGraph('Graph1');
        static::assertTrue($result, 'Did not return true!');
    }

    /**
     * Test creation of graph with definitions and with old structure
     */
    public function testCreationOfGraphObject()
    {

        $this->graph = new Graph('Graph1');
        static::assertNull($this->graph->getVerticesCollection());
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection01');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection01');
        static::assertSame($this->graph->getEdgesCollection(), 'ArangoDBPHPTestSuiteTestEdgeCollection01');
        static::assertSame($this->graph->getVerticesCollection(), 'ArangoDBPHPTestSuiteTestCollection01');
        static::assertCount(1, $this->graph->getEdgeDefinitions());
        $ed = $this->graph->getEdgeDefinitions();
        $ed = $ed[0];
        $a  = $ed->getToCollections();
        $b  = $ed->getFromCollections();
        static::assertSame($ed->getRelation(), 'ArangoDBPHPTestSuiteTestEdgeCollection01');
        static::assertSame($a[0], 'ArangoDBPHPTestSuiteTestCollection01');
        static::assertSame($b[0], 'ArangoDBPHPTestSuiteTestCollection01');
        $ed = $this->graph->getEdgeDefinitions();
        $ed = $ed[0];
        $ed->addFromCollection('newFrom');
        $ed->addToCollection('newTo');

        static::assertCount(2, $ed->getFromCollections());
        static::assertCount(2, $ed->getToCollections());

        $exception = null;
        try {
            $this->graph->getVerticesCollection();
        } catch (Exception $e) {
            $exception = $e->getMessage();
        }
        static::assertSame('This operation only supports graphs with one undirected single collection relation', $exception);
        $exception = null;
        try {
            $this->graph->getEdgesCollection();
        } catch (Exception $e) {
            $exception = $e->getMessage();
        }
        static::assertSame('This operation only supports graphs with one undirected single collection relation', $exception);

        $this->graph->addOrphanCollection('o1');
        $this->graph->addOrphanCollection('o2');
        static::assertCount(2, $this->graph->getOrphanCollections());

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
        static::assertEquals($result['_key'], 'Graph2', 'Did not return Graph2!');

        $properties = $this->graphHandler->properties('Graph2');
        static::assertEquals($properties['_key'], 'Graph2', 'Did not return Graph2!');

        $result = $this->graphHandler->dropGraph('Graph2');
        static::assertTrue($result, 'Did not return true!');
    }

    /**
     * Test if we can create a graph and then retrieve it from the server
     */
    public function testCreateRetrieveAndDeleteGraph1()
    {
        $this->graph = new Graph('Graph3');
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection03');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection03');
        $this->graph->addOrphanCollection('orphan');
        $this->graphHandler = new GraphHandler($this->connection);
        $this->graphHandler->createGraph($this->graph);
        $graph = $this->graphHandler->getGraph('Graph3');
        static::assertEquals($graph->getKey(), 'Graph3', 'Did not return Graph3!');
        $result = $this->graphHandler->dropGraph('Graph3');
        static::assertTrue($result, 'Did not return true!');
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
        static::assertEquals($result['_key'], 'Graph4', 'Did not return Graph4!');

        $properties = $this->graphHandler->properties($this->graph);
        static::assertEquals($properties['_key'], 'Graph4', 'Did not return Graph4!');

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
        $this->graph = new Graph('Graph1');
        $ed1         = EdgeDefinition::createUndirectedRelation('undirected', 'singleV');
        $this->graph->addOrphanCollection('ArangoDBPHPTestSuiteTestCollection04');
        $this->graph->addEdgeDefinition($ed1);
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        static::assertEquals($result['_key'], 'Graph1', 'Did not return Graph1!');

        $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'orphan1');
        $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'orphan2');

        static::assertSame(
            $this->graphHandler->getVertexCollections($this->graph), array(
                0 => 'ArangoDBPHPTestSuiteTestCollection04',
                1 => 'orphan1',
                2 => 'orphan2',
                3 => 'singleV'

            )
        );
        $this->graph = $this->graphHandler->deleteOrphanCollection($this->graph, 'orphan2');
        static::assertSame(
            $this->graphHandler->getVertexCollections($this->graph), array(
                0 => 'ArangoDBPHPTestSuiteTestCollection04',
                1 => 'orphan1',
                2 => 'singleV'

            )
        );
        $error = null;
        try {
            $this->graph = $this->graphHandler->deleteOrphanCollection($this->graph, 'singleV');
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame($error, 'not in orphan collection');

        $error = null;
        try {
            $this->graph = $this->graphHandler->addOrphanCollection($this->graph, 'undirected');
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        static::assertSame($error, 'not a vertex collection');

        $error = null;
        try {
            $this->graph = $this->graphHandler->getVertexCollections('notExisting');
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame($error, 'graph not found');

        $result = $this->graphHandler->dropGraph($this->graph);
        static::assertTrue($result, 'Did not return true!');
    }

    /**
     * Test adding, getting and deleting of edgecollections
     */
    public function testAddGetDeleteEdgeCollections()
    {
        $this->graph = new Graph('Graph1');
        $ed1         = EdgeDefinition::createUndirectedRelation('undirected', 'singleV');
        $this->graph->addEdgeDefinition($ed1);
        $this->graphHandler = new GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        static::assertEquals($result['_key'], 'Graph1', 'Did not return Graph1!');


        $this->graph = $this->graphHandler->addEdgeDefinition(
            $this->graph,
            EdgeDefinition::createUndirectedRelation('undirected2', 'singleV2')
        );

        static::assertSame(
            $this->graphHandler->getEdgeCollections($this->graph), array(
                0 => 'undirected',
                1 => 'undirected2'

            )
        );

        $error = null;
        try {
            $this->graph = $this->graphHandler->addEdgeDefinition(
                $this->graph,
                EdgeDefinition::createUndirectedRelation('undirected2', 'singleV2')
            );
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame($error, 'multi use of edge collection in edge def');
        $error = null;
        try {
            $this->graph = $this->graphHandler->getEdgeCollections('bla');
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame($error, 'graph not found');

        $this->graph = $this->graphHandler->deleteEdgeDefinition(
            $this->graph,
            'undirected'
        );

        static::assertSame(
            $this->graphHandler->getEdgeCollections($this->graph), array(
                0 => 'undirected2'

            )
        );

        $error = null;
        try {
            $this->graph = $this->graphHandler->deleteEdgeDefinition('bla', 'undefined');
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame($error, 'graph not found');

        $this->graph = $this->graphHandler->replaceEdgeDefinition(
            $this->graph,
            EdgeDefinition::createUndirectedRelation('undirected2', 'singleV3')
        );

        $ed = $this->graph->getEdgeDefinitions();
        $ed = $ed[0];
        $ed = $ed->getToCollections();
        static::assertSame($ed[0], 'singleV3');

        $error = null;
        try {
            $this->graph = $this->graphHandler->replaceEdgeDefinition(
                $this->graph,
                EdgeDefinition::createUndirectedRelation('notExisting', 'singleV3')
            );
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        static::assertSame($error, 'edge collection not used in graph');

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
        try {
            $this->graphHandler->dropGraph('orphan');
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('orphan1');
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('orphan2');
        } catch (Exception $e) {
        }
        try {
            $this->graphHandler->dropGraph('undirected');
        } catch (Exception $e) {
        }
        unset($this->graph, $this->graphHandler, $this->connection);
    }
}
