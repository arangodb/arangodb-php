<?php
/**
 * ArangoDB PHP client testsuite
 * File: GraphBasicTest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class GraphBasicTest
 * Basic Tests for the Graph API implementation
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
        $this->graph = new \triagens\ArangoDb\Graph();
        $this->graph->set('_key', 'Graph1');
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection01');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->graphHandler = new \triagens\ArangoDb\GraphHandler($this->connection);

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
        $this->graph = new \triagens\ArangoDb\Graph('Graph2');
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection02');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection02');
        $this->graphHandler = new \triagens\ArangoDb\GraphHandler($this->connection);

        $result = $this->graphHandler->createGraph($this->graph);
        $this->assertTrue($result['_key'] == 'Graph2', 'Did not return Graph2!');
		 
        $properties = $this->graphHandler->properties('Graph1');
        $this->assertTrue($properties['_key'] == 'Graph2', 'Did not return Graph2!');
		 
        $result = $this->graphHandler->dropGraph('Graph2');
        $this->assertTrue($result, 'Did not return true!');
    }


    public function tearDown()
    {
        unset($this->graph);
        unset($this->graphHandler);
        unset($this->connection);
    }
}
