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
 * Class GraphExtendedTest
 * Basic Tests for the Graph API implementation
 *
 * @package triagens\ArangoDb
 */
class GraphExtendedTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->graphName  = 'Graph1';
        $this->connection = getConnection();
        $this->graph      = new \triagens\ArangoDb\Graph();
        $this->graph->set('_key', $this->graphName);
        $this->graph->setVerticesCollection('ArangoDBPHPTestSuiteTestCollection01');
        $this->graph->setEdgesCollection('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->graphHandler = new \triagens\ArangoDb\GraphHandler($this->connection);
        $this->graphHandler->createGraph($this->graph);
    }

    /**
     * Test if 2 Vertices can be saved and an edge can be saved connecting them
     */
    public function testSaveVerticesAndEdgeBetweenThemAndRemoveOneByOne()
    {

        $vertex1Name  = 'vertex1';
        $vertex2Name  = 'vertex2';
        $vertex1aName = 'vertex1';
        $edge1Name    = 'edge1';
        $edge1aName   = 'edge1';
        $edgeLabel1   = 'edgeLabel1';

        $vertex1Array  = array(
            '_key'     => $vertex1Name,
            'someKey1' => 'someValue1'
        );
        $vertex2Array  = array(
            '_key'     => $vertex2Name,
            'someKey2' => 'someValue2'
        );
        $vertex1aArray = array(
            'someKey1' => 'someValue1a'
        );
        $edge1Array    = array(
            '_key'         => $edge1Name,
            'someEdgeKey1' => 'someEdgeValue1'
        );
        $edge1aArray   = array(
            '_key'         => $edge1Name,
            'someEdgeKey1' => 'someEdgeValue1a'
        );

        $vertex1  = Vertex::createFromArray($vertex1Array);
        $vertex2  = Vertex::createFromArray($vertex2Array);
        $vertex1a = Vertex::createFromArray($vertex1aArray);
        $edge1    = Edge::createFromArray($edge1Array);
        $edge1a   = Edge::createFromArray($edge1aArray);


        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);

        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');

        $result1 = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        $result2 = $this->graphHandler->getVertex($this->graphName, $vertex2Name);

        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');

        $result1 = $this->graphHandler->saveEdge($this->graphName, $vertex1Name, $vertex2Name, $edgeLabel1, $edge1);

        $this->assertTrue($result1 == 'edge1', 'Did not return edge1!');

        $result1 = $this->graphHandler->getEdge($this->graphName, $edge1Name);

        $this->assertTrue($result1->getKey() == 'edge1', 'Did not return edge1!');

        $result1 = $this->graphHandler->removeEdge($this->graphName, $edge1Name);
        $this->assertTrue($result1, 'Did not return true!');

        try {
            $result1a = $this->graphHandler->getEdge($this->graphName, $edge1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);


        $result1a = $this->graphHandler->removeVertex($this->graphName, $vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');

        try {
            $result1a = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);

        $result2 = $this->graphHandler->removeVertex($this->graphName, $vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');

        try {
            $result2 = $this->graphHandler->getVertex($this->graphName, $vertex2Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
    }

    /**
     * Test if a Vertex can be saved, replaced, updated, and finally removed
     */
    public function testSaveVertexReplaceUpdateAndRemove()
    {

        $vertex1Name  = 'vertex1';
        $vertex2Name  = 'vertex2';
        $vertex1aName = 'vertex1';

        $vertex1Array  = array(
            '_key'     => $vertex1Name,
            'someKey1' => 'someValue1'
        );
        $vertex2Array  = array(
            '_key'     => $vertex2Name,
            'someKey2' => 'someValue2'
        );
        $vertex1aArray = array(
            'someKey1' => 'someValue1a'
        );


        $vertex1  = Vertex::createFromArray($vertex1Array);
        $vertex2  = Vertex::createFromArray($vertex2Array);
        $vertex1a = Vertex::createFromArray($vertex1aArray);

        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);

        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');

        $result1 = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        $result2 = $this->graphHandler->getVertex($this->graphName, $vertex2Name);

        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');

        $result1a = $this->graphHandler->replaceVertex($this->graphName, $vertex1Name, $vertex1a);
        $this->assertTrue($result1a, 'Did not return true!');

        $result1a = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        $this->assertTrue($result1a->someKey1 == 'someValue1a', 'Did not return someValue1a!');

        $result1 = $this->graphHandler->replaceVertex($this->graphName, $vertex1Name, $vertex1);
        $this->assertTrue($result1, 'Did not return true!');

        $result1 = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        $this->assertTrue($result1->someKey1 == 'someValue1', 'Did not return someValue1!');

        $result1a = $this->graphHandler->updateVertex($this->graphName, $vertex1Name, $vertex1a);
        $this->assertTrue($result1a, 'Did not return true!');

        $result1a = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        $this->assertTrue($result1a->someKey1 == 'someValue1a', 'Did not return someValue1a!');

        $result1 = $this->graphHandler->updateVertex($this->graphName, $vertex1Name, $vertex1);
        $this->assertTrue($result1, 'Did not return true!');

        $result1 = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        $this->assertTrue($result1->someKey1 == 'someValue1', 'Did not return someValue1!');

        $result1a = $this->graphHandler->removeVertex($this->graphName, $vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');

        try {
            $result1a = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);

        $result2 = $this->graphHandler->removeVertex($this->graphName, $vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');

        try {
            $result2 = $this->graphHandler->getVertex($this->graphName, $vertex2Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
    }


    /**
     * Test if 2 Vertices can be saved and an Edge between them can be saved, replaced, updated, and finally removed
     */
    public function testSaveVerticesAndReplaceUpdateAndRemoveEdge()
    {

        $vertex1Name  = 'vertex1';
        $vertex2Name  = 'vertex2';
        $vertex1aName = 'vertex1';
        $edge1Name    = 'edge1';
        $edge1aName   = 'edge1';
        $edgeLabel1   = 'edgeLabel1';

        $vertex1Array  = array(
            '_key'     => $vertex1Name,
            'someKey1' => 'someValue1'
        );
        $vertex2Array  = array(
            '_key'     => $vertex2Name,
            'someKey2' => 'someValue2'
        );
        $vertex1aArray = array(
            'someKey1' => 'someValue1a'
        );
        $edge1Array    = array(
            '_key'         => $edge1Name,
            'someEdgeKey1' => 'someEdgeValue1'
        );
        $edge1aArray   = array(
            '_key'         => $edge1Name,
            'someEdgeKey1' => 'someEdgeValue1a'
        );

        $vertex1  = Vertex::createFromArray($vertex1Array);
        $vertex2  = Vertex::createFromArray($vertex2Array);
        $vertex1a = Vertex::createFromArray($vertex1aArray);
        $edge1    = Edge::createFromArray($edge1Array);
        $edge1a   = Edge::createFromArray($edge1aArray);


        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);

        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');

        $result1 = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        $result2 = $this->graphHandler->getVertex($this->graphName, $vertex2Name);

        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');

        $result1 = $this->graphHandler->saveEdge($this->graphName, $vertex1Name, $vertex2Name, $edgeLabel1, $edge1);

        $this->assertTrue($result1 == 'edge1', 'Did not return edge1!');

        $result1 = $this->graphHandler->getEdge($this->graphName, $edge1Name);

        $this->assertTrue($result1->getKey() == 'edge1', 'Did not return edge1!');

        $result1 = $this->graphHandler->getEdge($this->graphName, $edge1Name);

        $this->assertTrue($result1->getKey() == 'edge1', 'Did not return edge1!');

        $result1a = $this->graphHandler->replaceEdge($this->graphName, $edge1Name, $edgeLabel1, $edge1a);
        $this->assertTrue($result1a, 'Did not return true!');

        $result1a = $this->graphHandler->getEdge($this->graphName, $edge1Name);
        $this->assertTrue($result1a->someEdgeKey1 == 'someEdgeValue1a', 'Did not return someEdgeValue1a!');

        $result1a = $this->graphHandler->updateEdge($this->graphName, $edge1Name, $edgeLabel1, $edge1);
        $this->assertTrue($result1a, 'Did not return true!');

        $result1 = $this->graphHandler->getEdge($this->graphName, $edge1Name);
        $this->assertTrue($result1->someEdgeKey1 == 'someEdgeValue1', 'Did not return someEdgeValue1!');

        $result1a = $this->graphHandler->removeEdge($this->graphName, $edge1Name);
        $this->assertTrue($result1a, 'Did not return true!');

        try {
            $result1a = $this->graphHandler->getEdge($this->graphName, $edge1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);


        $result1a = $this->graphHandler->removeVertex($this->graphName, $vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');

        try {
            $result1a = $this->graphHandler->getVertex($this->graphName, $vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);

        $result2 = $this->graphHandler->removeVertex($this->graphName, $vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');

        try {
            $result2 = $this->graphHandler->getVertex($this->graphName, $vertex2Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
    }





    public function tearDown()
    {
        try {

            $result = $this->graphHandler->dropGraph('Graph1');
            $this->assertTrue($result, 'Did not return true!');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->graph);
        unset($this->graphHandler);
        unset($this->connection);
    }
}
