<?php
/**
 * ArangoDB PHP client testsuite
 * File: GraphExtendedTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class GraphExtendedTest
 * Extended Tests for the Graph API implementation
 *
 * @property Connection        $connection
 * @property Graph             $graph
 * @property Collection        $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property GraphHandler      $graphHandler
 * @property DocumentHandler   $documentHandler
 * @property EdgeHandler       $edgeHandler
 * @property string            vertex1Name
 * @property string            vertex2Name
 * @property string            vertex3Name
 * @property string            vertex4Name
 * @property string            vertex1aName
 * @property string            edge1Name
 * @property string            edge2Name
 * @property string            edge3Name
 * @property string            edge1aName
 * @property string            edgeLabel1
 * @property string            edgeLabel2
 * @property string            edgeLabel3
 * @property mixed             vertex1Array
 * @property mixed             vertex2Array
 * @property mixed             vertex3Array
 * @property mixed             vertex4Array
 * @property mixed             vertex1aArray
 * @property mixed             edge1Array
 * @property mixed             edge2Array
 * @property mixed             edge3Array
 * @property mixed             edge1aArray
 * @property string            graphName
 * @property string            vertexCollectionName
 * @property string            edgeCollectionName
 *
 * @package triagens\ArangoDb
 */
class GraphExtendedTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->vertex1Name  = 'vertex1';
        $this->vertex2Name  = 'vertex2';
        $this->vertex3Name  = 'vertex3';
        $this->vertex4Name  = 'vertex4';
        $this->vertex1aName = 'vertex1';
        $this->edge1Name    = 'edge1';
        $this->edge2Name    = 'edge2';
        $this->edge3Name    = 'edge3';
        $this->edge1aName   = 'edge1';
        $this->edgeLabel1   = 'edgeLabel1';
        $this->edgeLabel2   = 'edgeLabel2';
        $this->edgeLabel3   = 'edgeLabel3';


        $this->vertex1Array  = array(
            '_key'     => $this->vertex1Name,
            'someKey1' => 'someValue1'
        );
        $this->vertex2Array  = array(
            '_key'     => $this->vertex2Name,
            'someKey2' => 'someValue2'
        );
        $this->vertex3Array  = array(
            '_key'     => $this->vertex3Name,
            'someKey3' => 'someValue3'
        );
        $this->vertex4Array  = array(
            '_key'     => $this->vertex4Name,
            'someKey4' => 'someValue4'
        );
        $this->vertex1aArray = array(
            'someKey1' => 'someValue1a'
        );
        $this->edge1Array    = array(
            '_key'         => $this->edge1Name,
            'someEdgeKey1' => 'someEdgeValue1'
        );
        $this->edge2Array    = array(
            '_key'         => $this->edge2Name,
            'someEdgeKey2' => 'someEdgeValue2'
        );
        $this->edge3Array    = array(
            '_key'         => $this->edge3Name,
            'someEdgeKey3' => 'someEdgeValue3'
        );
        $this->edge1aArray   = array(
            '_key'         => $this->edge1Name,
            'someEdgeKey1' => 'someEdgeValue1a'
        );


        $this->graphName  = 'Graph1';
        $this->connection = getConnection();
        $this->graph      = new Graph();
        $this->graph->set('_key', $this->graphName);


        $this->vertexCollectionName = 'ArangoDBPHPTestSuiteVertexTestCollection01';
        $this->edgeCollectionName   = 'ArangoDBPHPTestSuiteTestEdgeCollection01';
        $this->graph->setVerticesCollection($this->vertexCollectionName);
        $this->graph->setEdgesCollection($this->edgeCollectionName);
        $this->graphHandler = new GraphHandler($this->connection);
        $this->graphHandler->createGraph($this->graph);
    }


    // Helper method to setup a graph
    public function createGraph()
    {
        $vertex1 = Vertex::createFromArray($this->vertex1Array);
        $vertex2 = Vertex::createFromArray($this->vertex2Array);
        $vertex3 = Vertex::createFromArray($this->vertex3Array);
        // This one is just an array to test if a vertex can be created with an array instead of a vertex object
        $vertex4 = $this->vertex4Array;
        $edge1   = Edge::createFromArray($this->edge1Array);
        $edge2   = Edge::createFromArray($this->edge2Array);
        // This one is just an array to test if an edge can be created with an array instead of an edge object
        $edge3 = $this->edge3Array;


        $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $this->graphHandler->saveVertex($this->graphName, $vertex2);
        $this->graphHandler->saveVertex($this->graphName, $vertex3);
        $this->graphHandler->saveVertex($this->graphName, $vertex4);
        $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        $this->graphHandler->getVertex($this->graphName, $this->vertex3Name);
        $this->graphHandler->getVertex($this->graphName, $this->vertex4Name);
        $this->graphHandler->saveEdge(
                           $this->graphName,
                           $this->vertex1Name,
                           $this->vertex2Name,
                           $this->edgeLabel1,
                           $edge1
        );
        $this->graphHandler->saveEdge(
                           $this->graphName,
                           $this->vertex2Name,
                           $this->vertex3Name,
                           $this->edgeLabel2,
                           $edge2
        );
        $this->graphHandler->saveEdge(
                           $this->graphName,
                           $this->vertex3Name,
                           $this->vertex4Name,
                           $this->edgeLabel3,
                           $edge3
        );
        $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->graphHandler->getEdge($this->graphName, $this->edge2Name);
        $this->graphHandler->getEdge($this->graphName, $this->edge3Name);
    }


    /**
     * Test if 2 Vertices can be saved and an edge can be saved connecting them
     * Then remove in this order Edge, Vertex1, Vertex2
     */
    public function testSaveVerticesAndEdgeBetweenThemAndRemoveOneByOne()
    {
        // Setup Objects
        $vertex1 = Vertex::createFromArray($this->vertex1Array);
        $vertex2 = Vertex::createFromArray($this->vertex2Array);
        $edge1   = Edge::createFromArray($this->edge1Array);


        // Save vertices
        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');

        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');


        // Get vertices
        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');

        $result2 = $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');


        // Save edge
        $resultE = $this->graphHandler->saveEdge(
                                      $this->graphName,
                                      $this->vertex1Name,
                                      $this->vertex2Name,
                                      $this->edgeLabel1,
                                      $edge1
        );
        $this->assertTrue($resultE == 'edge1', 'Did not return edge1!');


        // Get edge
        $resultE = $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($resultE->getKey() == 'edge1', 'Did not return edge1!');


        // Try to get the edge using GraphHandler
        $resultE = $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $resultE);


        // Remove the edge
        $resultE = $this->graphHandler->removeEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($resultE, 'Did not return true!');


        // Remove one vertex using GraphHandler
        $result1 = $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1, 'Did not return true!');


        // Remove one vertex using GraphHandler | Testing
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to get vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Remove the other vertex using GraphHandler
        $result2 = $this->graphHandler->removeVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');


        // Try to get vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
    }


    /**
     * Test if 2 Vertices can be saved and an edge can be saved connecting them, but remove the first vertex first
     * This should throw an exception on removing the edge, because it will be removed with
     */
    public function testSaveVerticesAndEdgeBetweenThemAndRemoveFirstVertexFirst()
    {
        // Setup Objects
        $vertex1 = Vertex::createFromArray($this->vertex1Array);
        $vertex2 = Vertex::createFromArray($this->vertex2Array);
        $edge1   = Edge::createFromArray($this->edge1Array);


        // Save vertices
        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');

        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');


        // Get vertices
        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');

        $result2 = $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');


        // Save edge
        $result1 = $this->graphHandler->saveEdge(
                                      $this->graphName,
                                      $this->vertex1Name,
                                      $this->vertex2Name,
                                      $this->edgeLabel1,
                                      $edge1
        );
        $this->assertTrue($result1 == 'edge1', 'Did not return edge1!');


        // Get edge
        $result1 = $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($result1->getKey() == 'edge1', 'Did not return edge1!');


        // Remove one vertex using GraphHandler
        $result1a = $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');


        // Remove the same vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to get vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to get the edge using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to remove the edge using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->removeEdge($this->graphName, $this->edge1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Remove the other vertex using GraphHandler
        $result2 = $this->graphHandler->removeVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');


        // Try to get vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
    }


    /**
     * Test for correct exception codes if nonexistent objects are tried to be gotten, replaced, updated or removed
     */
    public function testGetReplaceUpdateAndRemoveOnNonExistentObjects()
    {
        // Setup objects
        $vertex1 = Vertex::createFromArray($this->vertex1Array);
        $edge1   = Edge::createFromArray($this->edge1Array);


        // Try to get vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to update vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->updateVertex($this->graphName, $this->vertex1Name, $vertex1);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to replace vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->replaceVertex($this->graphName, $this->vertex1Name, $vertex1);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Remove a vertex using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to get the edge using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to update edge using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->updateEdge($this->graphName, $this->edge1Name, 'label', $edge1);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to replace edge using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->replaceEdge($this->graphName, $this->edge1Name, 'label', $edge1);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to remove the edge using GraphHandler
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $this->graphHandler->removeEdge($this->graphName, $this->edge1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
    }


    /**
     * Test if a Vertex can be saved, replaced, updated, and finally removed
     */
    public function testSaveVertexReplaceUpdateAndRemove()
    {
        // Setup Objects
        $vertex1  = Vertex::createFromArray($this->vertex1Array);
        $vertex2  = Vertex::createFromArray($this->vertex2Array);
        $vertex1a = Vertex::createFromArray($this->vertex1aArray);


        // Save vertices
        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');

        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');


        // Get vertices
        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');

        $result2 = $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');


        // Replace vertex
        $result1a = $this->graphHandler->replaceVertex($this->graphName, $this->vertex1Name, $vertex1a);
        $this->assertTrue($result1a, 'Did not return true!');


        // Get vertex
        $result1a = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a->someKey1 == 'someValue1a', 'Did not return someValue1a!');


        // Replace vertex
        $result1 = $this->graphHandler->replaceVertex($this->graphName, $this->vertex1Name, $vertex1);
        $this->assertTrue($result1, 'Did not return true!');


        // Get vertex
        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->someKey1 == 'someValue1', 'Did not return someValue1!');


        $result1a = $this->graphHandler->updateVertex($this->graphName, $this->vertex1Name, $vertex1a);
        $this->assertTrue($result1a, 'Did not return true!');


        $result1a = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a->someKey1 == 'someValue1a', 'Did not return someValue1a!');


        $result1 = $this->graphHandler->updateVertex($this->graphName, $this->vertex1Name, $vertex1);
        $this->assertTrue($result1, 'Did not return true!');


        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->someKey1 == 'someValue1', 'Did not return someValue1!');


        $result1a = $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');


        try {
            $e = null;
            $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);


        $result2 = $this->graphHandler->removeVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');


        try {
            $e = null;
            $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
    }

    /**
     * Test if a Vertex can be saved, replaced, updated, and finally removed with conditions
     */
    public function testSaveVertexConditionalReplaceUpdateAndRemove()
    {
        // Setup Objects
        $vertex1  = Vertex::createFromArray($this->vertex1Array);
        $vertex2  = Vertex::createFromArray($this->vertex2Array);
        $vertex1a = Vertex::createFromArray($this->vertex1aArray);


        // Save vertices
        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');

        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');


        // Get vertices
        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');

        $result2 = $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');


        // Replace vertex
        $result1a = $this->graphHandler->replaceVertex(
                                       $this->graphName,
                                       $this->vertex1Name,
                                       $vertex1a,
                                       array('revision' => $result1->getRevision())
        );
        $this->assertTrue($result1a, 'Did not return true!');


        // Get vertex
        $result1a = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a->someKey1 == 'someValue1a', 'Did not return someValue1a!');


        // Replace vertex
        $result1 = $this->graphHandler->replaceVertex($this->graphName, $this->vertex1Name, $vertex1);
        $this->assertTrue($result1, 'Did not return true!');


        // Get vertex
        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->someKey1 == 'someValue1', 'Did not return someValue1!');


        $result1a = $this->graphHandler->updateVertex($this->graphName, $this->vertex1Name, $vertex1a);
        $this->assertTrue($result1a, 'Did not return true!');


        $result1a = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a->someKey1 == 'someValue1a', 'Did not return someValue1a!');

        $e = null;

        try {
            $result1 = $this->graphHandler->updateVertex(
                                          $this->graphName,
                                          $this->vertex1Name,
                                          $vertex1,
                                          array('revision' => true)
            );
            $this->assertTrue($result1, 'Did not return true!');
        } catch (Exception $e) {
            //just give us the $e
        }
        $this->assertInstanceOf(
             'triagens\ArangoDb\ServerException',
             $e,
             "An exception should be thrown by the mis-matching revision!"
        );

        $result1a = $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');


        $result2 = $this->graphHandler->removeVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');
    }

    /**
     * Test if 2 Vertices can be saved and an Edge between them can be saved, replaced, updated, and finally removed
     */
    public function testSaveVerticesAndSaveReplaceUpdateAndRemoveEdge()
    {
        $vertex1 = Vertex::createFromArray($this->vertex1Array);
        $vertex2 = Vertex::createFromArray($this->vertex2Array);
        $edge1   = Edge::createFromArray($this->edge1Array);
        $edge1a  = Edge::createFromArray($this->edge1aArray);


        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');


        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');


        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');


        $result2 = $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');


        $result1 = $this->graphHandler->saveEdge(
                                      $this->graphName,
                                      $this->vertex1Name,
                                      $this->vertex2Name,
                                      $this->edgeLabel1,
                                      $edge1
        );
        $this->assertTrue($result1 == 'edge1', 'Did not return edge1!');


        $result1 = $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($result1->getKey() == 'edge1', 'Did not return edge1!');


        $result1a = $this->graphHandler->replaceEdge($this->graphName, $this->edge1Name, $this->edgeLabel1, $edge1a);
        $this->assertTrue($result1a, 'Did not return true!');


        $result1a = $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($result1a->someEdgeKey1 == 'someEdgeValue1a', 'Did not return someEdgeValue1a!');


        $result1a = $this->graphHandler->updateEdge($this->graphName, $this->edge1Name, $this->edgeLabel1, $edge1);
        $this->assertTrue($result1a, 'Did not return true!');


        $result1 = $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($result1->someEdgeKey1 == 'someEdgeValue1', 'Did not return someEdgeValue1!');


        $result1a = $this->graphHandler->removeEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($result1a, 'Did not return true!');

        $e = null;
        try {
            $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);


        $result1a = $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');


        $e = null;
        try {
            $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);


        $result2 = $this->graphHandler->removeVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');


        $e = null;
        try {
            $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
    }

    /**
     * Test if 2 Vertices can be saved and an Edge between them can be saved, replaced, updated, and finally removed conditionally
     */
    public function testSaveVerticesAndConditionalSaveReplaceUpdateAndRemoveEdge()
    {
        $vertex1 = Vertex::createFromArray($this->vertex1Array);
        $vertex2 = Vertex::createFromArray($this->vertex2Array);
        $edge1   = Edge::createFromArray($this->edge1Array);
        $edge1a  = Edge::createFromArray($this->edge1aArray);


        $result1 = $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');


        $result2 = $this->graphHandler->saveVertex($this->graphName, $vertex2);
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');


        $result1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');


        $result2 = $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');


        $result1 = $this->graphHandler->saveEdge(
                                      $this->graphName,
                                      $this->vertex1Name,
                                      $this->vertex2Name,
                                      $this->edgeLabel1,
                                      $edge1
        );
        $this->assertTrue($result1 == 'edge1', 'Did not return edge1!');


        $result1 = $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($result1->getKey() == 'edge1', 'Did not return edge1!');


        $result1a = $this->graphHandler->replaceEdge(
                                       $this->graphName,
                                       $this->edge1Name,
                                       $this->edgeLabel1,
                                       $edge1a,
                                       array('revision' => $result1->getRevision())
        );
        $this->assertTrue($result1a, 'Did not return true!');


        $result1a = $this->graphHandler->getEdge($this->graphName, $this->edge1Name);
        $this->assertTrue($result1a->someEdgeKey1 == 'someEdgeValue1a', 'Did not return someEdgeValue1a!');

        $e = null;
        try {
            $this->graphHandler->updateEdge(
                               $this->graphName,
                               $this->edge1Name,
                               $this->edgeLabel1,
                               $edge1,
                               array('revision' => true)
            );
        } catch (Exception $e) {
            //Just give the $e
        }

        $this->assertInstanceOf(
             'triagens\ArangoDb\ServerException',
             $e,
             "An exception should be thrown by the mis-matching revision!"
        );

        $result1a = $this->graphHandler->removeVertex($this->graphName, $this->vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');

        $result2 = $this->graphHandler->removeVertex($this->graphName, $this->vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');
    }


    /**
     * Test if two Vertices can be saved and an edge can be saved connecting them but with the document & edge-handlers instead of the graphHandler
     * Then remove all starting with vertex1 first
     * There is no need for another test with handlers other than as the GraphHandler since there is no automatic edge-removal functionality when removing a vertex
     */
    public function testSaveVerticesFromVertexHandlerAndEdgeFromEdgeHandlerBetweenThemAndRemoveFirstVertexFirst()
    {
        $vertex1 = Vertex::createFromArray($this->vertex1Array);
        $vertex2 = Vertex::createFromArray($this->vertex2Array);
        $edge1   = Edge::createFromArray($this->edge1Array);

        $vertexHandler = new VertexHandler($this->connection);

        // Save vertices using VertexHandler
        $result1 = $vertexHandler->save($this->vertexCollectionName, $vertex1);
        $this->assertTrue($result1 == 'vertex1', 'Did not return vertex1!');


        $result2 = $vertexHandler->save($this->vertexCollectionName, $vertex2);
        $this->assertTrue($result2 == 'vertex2', 'Did not return vertex2!');


        // Get vertices using VertexHandler
        $result1 = $vertexHandler->getById($this->vertexCollectionName, $this->vertex1Name);
        $this->assertTrue($result1->getKey() == 'vertex1', 'Did not return vertex1!');


        $result2 = $vertexHandler->getById($this->vertexCollectionName, $this->vertex2Name);
        $this->assertTrue($result2->getKey() == 'vertex2', 'Did not return vertex2!');


        // Save edge using EdgeHandler
        $edgeHandler = new EdgeHandler($this->connection);
        $result1     = $edgeHandler->saveEdge(
                                   $this->edgeCollectionName,
                                   $this->vertexCollectionName . '/' . $this->vertex1Name,
                                   $this->vertexCollectionName . '/' . $this->vertex2Name,
                                   $edge1
        );
        $this->assertTrue($result1 == 'edge1', 'Did not return edge1!');


        // Get edge using EdgeHandler
        $result1 = $edgeHandler->getById($this->edgeCollectionName, $this->edge1Name);
        $this->assertTrue($result1->getKey() == 'edge1', 'Did not return edge1!');


        // Remove one vertex using VertexHandler
        $result1a = $vertexHandler->removeById($this->vertexCollectionName, $this->vertex1Name);
        $this->assertTrue($result1a, 'Did not return true!');


        // Try to get vertex using VertexHandler
        // This should cause an exception with a code of 404
        $e = null;
        try {
            $vertexHandler->getById($this->vertexCollectionName, $this->vertex1Name);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);


        // Try to get the edge using EdgeHandler
        // This should cause an exception with a code of 404, because connecting edges should be removed when a vertex is removed
        $e = null;
        try {
            $edgeHandler->getById($this->edgeCollectionName, $this->edge1Name);
        } catch (\Exception $e) {
            $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
            $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
        }


        // Try to remove the edge using EdgeHandler
        // This should not cause an exception with a code of 404, because the we removed the vertex through the VertexHandler, not the GraphHandler
        try {
            $result = $edgeHandler->removeById($this->edgeCollectionName, $this->edge1Name);
        } catch (\Exception $e) {
            $result = $e;
        }
        $this->assertTrue($result, 'Should be true, instead got: ' . $result);


        // Try to remove the edge using VertexHandler again
        // This should not cause an exception with code 404 because we just had removed this edge
        $e = null;
        try {
            $edgeHandler->removeById($this->edgeCollectionName, $this->edge1Name);
        } catch (\Exception $e) {
            $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
            $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
        }


        // Remove the other vertex using VertexHandler
        $result2 = $vertexHandler->removeById($this->vertexCollectionName, $this->vertex2Name);
        $this->assertTrue($result2, 'Did not return true!');


        // Try to get vertex using VertexHandler
        // This should cause an exception with a code of 404
        $e = null;
        try {
            $vertexHandler->getById($this->vertexCollectionName, $this->vertex2Name);
        } catch (\Exception $e) {
            $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
            $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
        }
    }


    /**
     * Test for creation of a graph and query vertex neighbors
     */
    public function testCreateGraphAndQueryVertexNeighbors()
    {
        $this->createGraph();

        // Test without options
        $cursor = $this->graphHandler->getNeighborVertices($this->graphName, $this->vertex2Name);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someKey1 == 'someValue1',
             'Should return "someValue1", returned: ' . $resultingDocument[0]->someKey1
        );
        $this->assertTrue(
             $resultingDocument[1]->someKey3 == 'someValue3',
             'Should return "someValue3", returned: ' . $resultingDocument[1]->someKey3
        );
        $this->assertTrue(count($resultingDocument) == 2, 'Should be 2, was: ' . count($resultingDocument));


        // Test options->batchSize
        unset($resultingDocument);
        $options = array('batchSize' => 1);
        $cursor  = $this->graphHandler->getNeighborVertices($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);

        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someKey1 == 'someValue1',
             'Should return "someValue1", returned: ' . $resultingDocument[0]->someKey1
        );
        $this->assertTrue(
             $resultingDocument[1]->someKey3 == 'someValue3',
             'Should return "someValue3", returned: ' . $resultingDocument[1]->someKey3
        );
        $this->assertTrue(count($resultingDocument) == 2, 'Should be 2, was: ' . count($resultingDocument));


        // Test options->limit
        unset($resultingDocument);
        $options = array('limit' => 1);
        $cursor  = $this->graphHandler->getNeighborVertices($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someKey1 == 'someValue1',
             'Should return "someValue1", returned: ' . $resultingDocument[0]->someKey1
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));


        // Test options->count
        unset($resultingDocument);
        $options = array('count' => true);
        $cursor  = $this->graphHandler->getNeighborVertices($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someKey1 == 'someValue1',
             'Should return "someValue1", returned: ' . $resultingDocument[0]->someKey1
        );
        $this->assertInstanceOf('triagens\ArangoDb\Vertex', $resultingDocument[0]);

        $this->assertTrue(
             $resultingDocument[1]->someKey3 == 'someValue3',
             'Should return "someValue3", returned: ' . $resultingDocument[1]->someKey3
        );
        $this->assertInstanceOf('triagens\ArangoDb\Vertex', $resultingDocument[1]);

        $this->assertTrue(count($resultingDocument) == 2, 'Should be 2, was: ' . count($resultingDocument));

        $metaData = $cursor->getMetadata();
        $this->assertTrue($metaData['count'] == 2, 'Should be 2, was: ' . count($resultingDocument));


        // Test options->filter
        unset($resultingDocument);
        $filter  = array('labels' => array($this->edgeLabel2));
        $options = array('filter' => $filter);

        $cursor = $this->graphHandler->getNeighborVertices($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someKey3 == 'someValue3',
             'Should return "someValue3", returned: ' . $resultingDocument[0]->someKey3
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));


        // Test options->direction
        unset($resultingDocument);
        $filter  = array('direction' => 'out');
        $options = array('filter' => $filter);

        $cursor = $this->graphHandler->getNeighborVertices($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someKey3 == 'someValue3',
             'Should return "someValue3", returned: ' . $resultingDocument[0]->someKey3
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));


        // Test options->properties
        unset($resultingDocument);
        $properties = array('key' => 'someEdgeKey2', 'value' => 'someEdgeValue2', 'compare' => '==');
        $filter     = array('properties' => $properties);
        $options    = array('filter' => $filter);

        $cursor = $this->graphHandler->getNeighborVertices($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someKey3 == 'someValue3',
             'Should return "someValue3", returned: ' . $resultingDocument[0]->someKey3
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));
    }


    /**
     * Test for creation of a graph and query vertex neighbors
     */
    public function testCreateGraphAndQueryConnectedEdges()
    {
        $this->createGraph();

        $cursor = $this->graphHandler->getConnectedEdges($this->graphName, $this->vertex2Name);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someEdgeKey1 == 'someEdgeValue1',
             'Should return "someEdgeValue1", returned: ' . $resultingDocument[0]->someEdgeKey1
        );
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $resultingDocument[0]);
        $this->assertTrue(
             $resultingDocument[1]->someEdgeKey2 == 'someEdgeValue2',
             'Should return "someEdgeValue2", returned: ' . $resultingDocument[1]->someEdgeKey1
        );
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $resultingDocument[1]);

        $this->assertTrue(count($resultingDocument) == 2, 'Should be 2, was: ' . count($resultingDocument));


        // Test options->batchSize
        unset($resultingDocument);
        $options = array('batchSize' => 1);
        $cursor  = $this->graphHandler->getConnectedEdges($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someEdgeKey1 == 'someEdgeValue1',
             'Should return "someEdgeValue1", returned: ' . $resultingDocument[0]->someEdgeKey1
        );
        $this->assertTrue(
             $resultingDocument[1]->someEdgeKey2 == 'someEdgeValue2',
             'Should return "someEdgeValue2", returned: ' . $resultingDocument[1]->someEdgeKey1
        );
        $this->assertTrue(count($resultingDocument) == 2, 'Should be 2, was: ' . count($resultingDocument));


        // Test options->limit
        unset($resultingDocument);
        $options = array('limit' => 1);
        $cursor  = $this->graphHandler->getConnectedEdges($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someEdgeKey1 == 'someEdgeValue1',
             'Should return "someEdgeValue1", returned: ' . $resultingDocument[0]->someEdgeKey1
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 2, was: ' . count($resultingDocument));


        // Test options->count
        unset($resultingDocument);
        $options = array('count' => true);
        $cursor  = $this->graphHandler->getConnectedEdges($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someEdgeKey1 == 'someEdgeValue1',
             'Should return "someEdgeValue1", returned: ' . $resultingDocument[0]->someEdgeKey1
        );
        $this->assertTrue(count($resultingDocument) == 2, 'Should be 2, was: ' . count($resultingDocument));

        $metaData = $cursor->getMetadata();
        $this->assertTrue($metaData['count'] == 2, 'Should be 2, was: ' . count($resultingDocument));


        // Test options->filter
        unset($resultingDocument);
        $filter  = array('labels' => array($this->edgeLabel2));
        $options = array('filter' => $filter);

        $cursor = $this->graphHandler->getConnectedEdges($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        $this->assertTrue(
             $resultingDocument[0]->someEdgeKey2 == 'someEdgeValue2',
             'Should return "someEdgeValue2", returned: ' . $resultingDocument[0]->someEdgeKey2
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));


        // Test options->direction
        unset($resultingDocument);
        $filter  = array('direction' => 'out');
        $options = array('filter' => $filter);

        $cursor = $this->graphHandler->getConnectedEdges($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        $this->assertTrue(
             $resultingDocument[0]->someEdgeKey2 == 'someEdgeValue2',
             'Should return "someEdgeValue2", returned: ' . $resultingDocument[0]->someEdgeKey2
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));


        // Test options->properties
        unset($resultingDocument);
        $properties = array('key' => 'someEdgeKey2', 'value' => 'someEdgeValue2', 'compare' => '==');
        $filter     = array('properties' => $properties);
        $options    = array('filter' => $filter);

        $cursor = $this->graphHandler->getConnectedEdges($this->graphName, $this->vertex2Name, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(
             $resultingDocument[0]->someEdgeKey2 == 'someEdgeValue2',
             'Should return "someEdgeValue2", returned: ' . $resultingDocument[0]->someEdgeKey1
        );
        $this->assertTrue(count($resultingDocument) == 1, 'Should be 1, was: ' . count($resultingDocument));
    }







    /**
     * Test for creation of a graph and query vertices.
     */
    public function testCreateGraphAndQueryVertices()
    {
        $this->createGraph();

        // Test without options
        $cursor = $this->graphHandler->getVertices($this->graphName);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $resultingDocument = null;
        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }
        $this->assertTrue(count($resultingDocument) == 4, 'Should be 4, was: ' . count($resultingDocument));


        // Test options->batchSize
        unset($resultingDocument);
        unset($cursor);
        $options = array('batchSize' => 1);
        $cursor  = $this->graphHandler->getVertices($this->graphName, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);

        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 1);
        $this->assertTrue($m["hasMore"] == true);


        // Test options->limit
        unset($resultingDocument);
        $options = array('limit' => 1);
        $cursor  = $this->graphHandler->getVertices($this->graphName, $options);
        $m = $cursor->getMetadata();
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $this->assertTrue(count($m["result"]) == 1);
        $this->assertTrue($m["hasMore"] == false);;


        // Test options->count
        unset($resultingDocument);
        $options = array('count' => true);
        $cursor  = $this->graphHandler->getVertices($this->graphName, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);

        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 4);
        $this->assertTrue($m["hasMore"] == false);

        // Test options->properties
        unset($resultingDocument);
        $properties = array('key' => 'someKey1', 'value' => 'someValue1', 'compare' => '==');
        $filter     = array('properties' => $properties);
        $options    = array('filter' => $filter);

        $cursor = $this->graphHandler->getVertices($this->graphName, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 1);
        $this->assertTrue($m["hasMore"] == false);
    }


    /**
     * Test for creation of a graph and query edges
     */
    public function testCreateGraphAndQueryEdges()
    {
        $this->createGraph();

        $cursor = $this->graphHandler->getEdges($this->graphName);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 3);
        $this->assertTrue($m["hasMore"] == false);


        // Test options->batchSize
        unset($resultingDocument);
        $options = array('batchSize' => 1);
        $cursor  = $this->graphHandler->getEdges($this->graphName, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 1);
        $this->assertTrue($m["hasMore"] == true);

        // Test options->limit
        unset($resultingDocument);
        $options = array('limit' => 1);
        $cursor  = $this->graphHandler->getEdges($this->graphName, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 1);
        $this->assertTrue($m["hasMore"] == false);


        // Test options->count
        unset($resultingDocument);
        $options = array('count' => true);
        $cursor  = $this->graphHandler->getEdges($this->graphName, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 3);
        $this->assertTrue($m["hasMore"] == false);

        // Test options->filter
        unset($resultingDocument);
        $filter  = array('labels' => array($this->edgeLabel2));
        $options = array('filter' => $filter);

        $cursor = $this->graphHandler->getEdges($this->graphName, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 1);
        $this->assertTrue($m["hasMore"] == false);


        // Test options->properties
        unset($resultingDocument);
        $properties = array('key' => 'someEdgeKey2', 'value' => 'someEdgeValue2', 'compare' => '==');
        $filter     = array('properties' => $properties);
        $options    = array('filter' => $filter);

        $cursor = $this->graphHandler->getEdges($this->graphName, $options);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
        $m = $cursor->getMetadata();
        $this->assertTrue(count($m["result"]) == 1);
        $this->assertTrue($m["hasMore"] == false);
    }

    /**
     * Tests if the saveVertex method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testSaveVertexWithGraphInstance()
    {
        $id = $this->graphHandler->saveVertex($this->graph, $this->vertex1Array);
        $this->assertEquals('vertex1', $id);
    }

    /**
     * Tests if the getVertex method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testGetVertexWithGraphInstance()
    {
        $this->createGraph();
        $this->assertEquals('vertex1', $this->graphHandler->getVertex($this->graph, 'vertex1')->getKey());
    }

    /**
     * Tests if the replaceVertex method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testReplaceVertexWithGraphInstance()
    {
        $this->createGraph();
        $new = Vertex::createFromArray(array(
            '_key'     => 'testreplacewithgraphinstancekey',
            'someKey' => 'someValue'
        ));
        $this->assertTrue($this->graphHandler->replaceVertex($this->graph, 'vertex1', $new));
    }

    /**
     * Tests if the updateVertex method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testUpdateVertexWithGraphInstance()
    {
        $this->createGraph();
        $new = Vertex::createFromArray(array(
            '_key'     => 'vertex1',
            'someKey' => 'foobar'
        ));
        $this->assertTrue($this->graphHandler->updateVertex($this->graph, 'vertex1', $new));
    }

    /**
     * Tests if the removeVertex method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testRemoveVertexWithGraphInstance()
    {
        $this->createGraph();
        $this->assertTrue($this->graphHandler->removeVertex($this->graph, 'vertex1'));
    }

    /**
     * Tests if the saveEde method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testSaveEdgeWithGraphInstance()
    {
        $this->createGraph();
        $id = $this->graphHandler->saveEdge($this->graph, $this->vertex1Name, $this->vertex2Name, 'foobaredge', array('_key' => 'foobaredgekey'));
        $this->assertEquals('foobaredgekey', $id);
    }

    /**
     * Tests if the getEdge method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testGetEdgeWithGraphInstance()
    {
        $this->createGraph();
        $edge = $this->graphHandler->getEdge($this->graph, $this->edge1Name);
        $this->assertEquals($this->edge1Name, $edge->getKey());
    }

    /**
     * Tests if the replaceEdge method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testReplaceEdgeWithGraphInstance()
    {
        $this->createGraph();
        $result = $this->graphHandler->replaceEdge($this->graph, $this->edge1Name, '', Edge::createFromArray(array('_key' => 'foobar')));
        $this->assertTrue($result);
    }

    /**
     * Tests if the updateEdge method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testUpdateEdgeWithGraphInstance()
    {
        $this->createGraph();
        $result = $this->graphHandler->updateEdge($this->graph, $this->edge1Name, '', Edge::createFromArray(array('_key' => 'foobar')));
        $this->assertTrue($result);
    }

    /**
     * Tests if the removeEdge method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testRemoveEdgeWithGraphInstance()
    {
        $this->createGraph();
        $result = $this->graphHandler->removeEdge($this->graph, $this->edge1Name);
        $this->assertTrue($result);
    }

    /**
     * Tests if the getNeighborVertices method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testNeighborVerticesWithGraphInstance()
    {
        $this->createGraph();
        $cursor = $this->graphHandler->getNeighborVertices($this->graph, $this->vertex2Name);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    }

    /**
     * Tests if the getConnectedEdges method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testConnectedEdgesWithGraphInstance()
    {
        $this->createGraph();
        $cursor = $this->graphHandler->getConnectedEdges($this->graph, $this->vertex2Name);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    }

    /**
     * Tests if the getVertices method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testGetVerticesWithGraphInstance()
    {
        $this->createGraph();
        $cursor = $this->graphHandler->getVertices($this->graph);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    }

    /**
     * Tests if the getEdges method accepts an instance of Graph as first argument and extracts the graph name out of it.
     */
    public function testGetEdgesWithGraphInstance()
    {
        $this->createGraph();
        $cursor = $this->graphHandler->getEdges($this->graph);
        $this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    }

    public function tearDown()
    {
        try {
            $result = $this->graphHandler->dropGraph($this->graphName);
            $this->assertTrue($result, 'Did not return true!');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->graph);
        unset($this->graphHandler);
        unset($this->connection);
    }
}
