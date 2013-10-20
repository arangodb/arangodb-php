<?php
/**
 * ArangoDB PHP client testsuite
 * File: TraversalTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class TraversalTest
 * Tests for the Traversal API implementation
 *
 * These tests are modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
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
 * @property string            vertex5Name
 * @property string            edge1Name
 * @property string            edge2Name
 * @property string            edge3Name
 * @property string            edge4Name
 * @property string            edge5Name
 * @property string            edgeLabel1
 * @property string            edgeLabel2
 * @property string            edgeLabel3
 * @property string            edgeLabel4
 * @property string            edgeLabel5
 * @property mixed             vertex1Array
 * @property mixed             vertex2Array
 * @property mixed             vertex3Array
 * @property mixed             vertex4Array
 * @property mixed             vertex5Array
 * @property mixed             edge1Array
 * @property mixed             edge2Array
 * @property mixed             edge3Array
 * @property mixed             edge4Array
 * @property mixed             edge5Array
 * @property string            graphName
 * @property string            vertexCollectionName
 * @property string            edgeCollectionName
 *
 * @package triagens\ArangoDb
 */
class TraversalTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->vertex1Name = 'vertex_alice';
        $this->vertex2Name = 'vertex_bob';
        $this->vertex3Name = 'vertex_charlie';
        $this->vertex4Name = 'vertex_dave';
        $this->vertex5Name = 'vertex_eve';
        $this->edge1Name   = 'edge_alice_bob';
        $this->edge2Name   = 'edge_bob_charlie';
        $this->edge3Name   = 'edge_bob_dave';
        $this->edge4Name   = 'edge_eve_alice';
        $this->edge5Name   = 'edge_eve_bob';
        $this->edgeLabel1  = 'edge_alice_bob';
        $this->edgeLabel2  = 'edge_bob_charlie';
        $this->edgeLabel3  = 'edge_bob_dave';
        $this->edgeLabel4  = 'edge_eve_alice';
        $this->edgeLabel5  = 'edge_eve_bob';

        $this->vertex1Array = array(
            '_key' => $this->vertex1Name,
            'name' => 'Alice'
        );
        $this->vertex2Array = array(
            '_key' => $this->vertex2Name,
            'name' => 'Bob'
        );
        $this->vertex3Array = array(
            '_key' => $this->vertex3Name,
            'name' => 'Charlie'
        );
        $this->vertex4Array = array(
            '_key' => $this->vertex4Name,
            'name' => 'Dave'
        );
        $this->vertex5Array = array(
            '_key' => $this->vertex5Name,
            'name' => 'Eve'
        );
        $this->edge1Array   = array(
            '_key' => $this->edge1Name
        );
        $this->edge2Array   = array(
            '_key' => $this->edge2Name
        );
        $this->edge3Array   = array(
            '_key' => $this->edge3Name
        );
        $this->edge4Array   = array(
            '_key' => $this->edge4Name
        );
        $this->edge5Array   = array(
            '_key' => $this->edge5Name
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
        $vertex4 = Vertex::createFromArray($this->vertex4Array);
        $vertex5 = Vertex::createFromArray($this->vertex5Array);


        $edge1 = Edge::createFromArray($this->edge1Array);
        $edge2 = Edge::createFromArray($this->edge2Array);
        $edge3 = Edge::createFromArray($this->edge3Array);
        $edge4 = Edge::createFromArray($this->edge4Array);
        $edge5 = Edge::createFromArray($this->edge5Array);


        $this->graphHandler->saveVertex($this->graphName, $vertex1);
        $this->graphHandler->saveVertex($this->graphName, $vertex2);
        $this->graphHandler->saveVertex($this->graphName, $vertex3);
        $this->graphHandler->saveVertex($this->graphName, $vertex4);
        $this->graphHandler->saveVertex($this->graphName, $vertex5);
        $this->graphHandler->getVertex($this->graphName, $this->vertex1Name);
        $this->graphHandler->getVertex($this->graphName, $this->vertex2Name);
        $this->graphHandler->getVertex($this->graphName, $this->vertex3Name);
        $this->graphHandler->getVertex($this->graphName, $this->vertex4Name);
        $this->graphHandler->getVertex($this->graphName, $this->vertex5Name);
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
                           $this->vertex2Name,
                           $this->vertex4Name,
                           $this->edgeLabel3,
                           $edge3
        );
        $this->graphHandler->saveEdge(
                           $this->graphName,
                           $this->vertex5Name,
                           $this->vertex1Name,
                           $this->edgeLabel4,
                           $edge4
        );
        $this->graphHandler->saveEdge(
                           $this->graphName,
                           $this->vertex5Name,
                           $this->vertex2Name,
                           $this->edgeLabel5,
                           $edge5
        );
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Follow only outbound edges:
     */
    public function testTraversalUsingDirectionOutbound()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array('direction' => 'outbound');
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertCount(4, $result['result']['visited']['vertices']);
        $this->assertCount(4, $result['result']['visited']['paths']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Follow only inbound edges:
     */
    public function testTraversalUsingDirectionInbound()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array('direction' => 'inbound');
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertCount(2, $result['result']['visited']['vertices']);
        $this->assertCount(2, $result['result']['visited']['paths']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Follow any direction of edges:
     */
    public function testTraversalUsingDirectionAnyAndUniquenessWithVerticesNoneAndEdgesGlobal()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction'  => 'any',
            'uniqueness' => array('vertices' => 'none', 'edges' => 'global')
        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertCount(6, $result['result']['visited']['vertices']);
        $this->assertCount(6, $result['result']['visited']['paths']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Excluding Charlie and Bob:
     */
    public function testTraversalUsingDirectionOutboundAndFilter1()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction' => 'outbound',
            'filter'    => 'if (vertex.name === "Bob" || vertex.name === "Charlie") {return "exclude";}return;'
        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertCount(2, $result['result']['visited']['vertices']);
        $this->assertCount(2, $result['result']['visited']['paths']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Do not follow edges from Bob:
     */
    public function testTraversalUsingDirectionOutboundAndFilter2()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction' => 'outbound',
            'filter'    => 'if (vertex.name === "Bob") {return "prune";}return;'
        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertCount(2, $result['result']['visited']['vertices']);
        $this->assertCount(2, $result['result']['visited']['paths']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Visit only nodes in a depth of at least 2:
     */
    public function testTraversalUsingDirectionOutboundAndMinDepthIs2()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction' => 'outbound',
            'minDepth'  => 2
        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertCount(2, $result['result']['visited']['vertices']);
        $this->assertCount(2, $result['result']['visited']['paths']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Visit only nodes in a depth of at most 1:
     */
    public function testTraversalUsingDirectionOutboundAndMaxDepthIs1()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction' => 'outbound',
            'maxDepth'  => 1
        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertCount(2, $result['result']['visited']['vertices']);
        $this->assertCount(2, $result['result']['visited']['paths']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Count all visited nodes and return a list of nodes only:
     */
    public function testTraversalCountVisitedNodesAndReturnListOfNodesOnly()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction' => 'outbound',
            'init'      => 'result.visited = 0; result.myVertices = [ ];',
            'visitor'   => 'result.visited++; result.myVertices.push(vertex);'

        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertEquals(4, $result['result']['visited']);
        $this->assertCount(4, $result['result']['myVertices']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Expand only inbound edges of Alice and outbound edges of Eve:
     */
    public function testTraversalExpandOnlyInboundOfAliceAndOutboundOfEve()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'expander' => "var connections = [ ];if (vertex.name === \"Alice\") {config.edgeCollection.inEdges(vertex).forEach(function (e) {connections.push({ vertex: require(\"internal\").db._document(e._from), edge: e});});}if (vertex.name === \"Eve\") {config.edgeCollection.outEdges(vertex).forEach(function (e) {connections.push({vertex: require(\"internal\").db._document(e._to), edge: e});});}return connections;"

        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple. Only assert result counts.
        $this->assertCount(3, $result['result']['visited']['vertices']);
        $this->assertCount(3, $result['result']['visited']['paths']);
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Follow the depthfirst strategy:
     */
    public function testTraversalFollowDepthFirstStrategy()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction' => 'any',
            'strategy'  => 'depthFirst'

        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple.
        $this->assertCount(11, $result['result']['visited']['vertices']);
        $this->assertCount(11, $result['result']['visited']['paths']);
        $this->assertTrue(
             $result['result']['visited']['vertices'][0]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][0]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][1]['name'] == 'Eve',
             'name is: ' . $result['result']['visited']['vertices'][1]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][3]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][3]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][8]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][8]['name']
        );
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Using postorder ordering:
     */
    public function testTraversalUsingPostOrderOrdering()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction' => 'any',
            'order'     => 'postorder'

        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple.
        $this->assertCount(11, $result['result']['visited']['vertices']);
        $this->assertCount(11, $result['result']['visited']['paths']);
        $this->assertTrue(
             $result['result']['visited']['vertices'][0]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][0]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][1]['name'] == 'Charlie',
             'name is: ' . $result['result']['visited']['vertices'][1]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][5]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][5]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][10]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][10]['name']
        );
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Using backward item-ordering:
     */
    public function testTraversalUsingBackwardItemOrdering()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction' => 'any',
            'itemOrder' => 'backward'

        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple.
        $this->assertCount(11, $result['result']['visited']['vertices']);
        $this->assertCount(11, $result['result']['visited']['paths']);
        $this->assertTrue(
             $result['result']['visited']['vertices'][0]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][0]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][1]['name'] == 'Bob',
             'name is: ' . $result['result']['visited']['vertices'][1]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][5]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][5]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][10]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][10]['name']
        );
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * Edges should only be included once globally, but nodes are included every time they are visited:
     */
    public function testTraversalIncludeEdgesOnlyOnceGloballyButNodesEveryTimeVisited()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction'  => 'any',
            'uniqueness' => array('vertices' => 'none', 'edges' => 'global')

        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);

        $result = $traversal->getResult();

        // keeping test simple.
        $this->assertCount(6, $result['result']['visited']['vertices']);
        $this->assertCount(6, $result['result']['visited']['paths']);
        $this->assertTrue(
             $result['result']['visited']['vertices'][0]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][0]['name']
        );
        $this->assertTrue(
             $result['result']['visited']['vertices'][3]['name'] == 'Alice',
             'name is: ' . $result['result']['visited']['vertices'][3]['name']
        );
    }


    /**
     * Test for creation of a graph and a traversal
     * Modeled after: http://www.arangodb.org/manuals/1.4/HttpTraversals.html
     *
     * Test:
     * If the underlying graph is cyclic, maxIterations should be set:
     * The underlying graph has two vertices Alice and Bob. With the directed edges:
     * Alice knows Bob _ Bob knows Alice
     */
    public function testTraversalTooManyIterations()
    {
        $this->createGraph();

        $startVertex    = $this->vertexCollectionName . '/' . $this->vertex1Name;
        $edgeCollection = $this->edgeCollectionName;
        $options        = array(
            'direction'     => 'any',
            'uniqueness'    => array('vertices' => 'none', 'edges' => 'none'),
            'maxIterations' => 5

        );
        $traversal      = new Traversal($this->connection, $startVertex, $edgeCollection, $options);


        try {
            $traversal->getResult();
        } catch (Exception $e) {
            // don't bother us, if it's already deleted.
        }
        $details                = $e->getDetails();
        $expectedCutDownMessage = "too many iterations";
        $len                    = strlen($expectedCutDownMessage);
        $this->assertTrue(
             $e->getCode() == 500 && substr(
                 $details['errorMessage'],
                 0,
                 $len
             ) == $expectedCutDownMessage,
             'Did not return code 500 with first part of the message: "' . $expectedCutDownMessage . '", instead returned: ' . $e->getCode(
             ) . ' and "' . $details['errorMessage'] . '"'
        );
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
