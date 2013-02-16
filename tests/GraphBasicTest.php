<?php
/**
 * ArangoDB PHP client testsuite
 * File: documentbasictest.php
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
        $this->connection        = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->edgeCollection    = new \triagens\ArangoDb\Collection();
        $this->edgeCollection->setName('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->edgeCollection->set('type', 3);
        $this->collection = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDBPHPTestSuiteTestCollection01');

        $this->collectionHandler->add($this->edgeCollection);

        $this->collectionHandler->add($this->collection);
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

        $result = $this->graphHandler->deleteGraph('Graph1');
        $this->assertTrue($result, 'Did not return true!');
    }

    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        try {
            $response = $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestCollection01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }


        unset($this->documentHandler);
        unset($this->document);
        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
