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
    public function testCreateAndDeleteGraph()
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
    public function testCreateRetrieveAndDeleteGraph()
    {
        $this->graph = new Graph('Graph3');
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection03');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection03');
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
