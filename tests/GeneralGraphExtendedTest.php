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
 * @package triagens\ArangoDb
 */
class GeneralGraphExtendedTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {	
    	$this->connection = getConnection();
    	$v = time();
    	$this->graphName = "graph" . $v;
    	$this->v1 = "v1" . $v;
    	$this->v2 = "v2" . $v;
    	$this->v3 = "v3" . $v;
    	$this->v4 = "v4" . $v;
    	$this->v5 = "v5" . $v;
    	$this->e1 = "e1" . $v;
    	$this->e2 = "e2" . $v;
    	$param1 = array();
    	$param1[] = $this->v1;
    	$param1[] = $this->v2;
    	$param2 = array();
    	$param2[] = $this->v3;
    	$param2[] = $this->v4;
      $ed1 = EdgeDefinition::createDirectedRelation(
        		$this->e1, 
        		$param1, 
        		$param2
		  );
    	$ed2 = EdgeDefinition::createUndirectedRelation(
    			$this->e2, $this->v5
		  );
    	$this->graph = new Graph($this->graphName);
    	$this->graph->addEdgeDefinition($ed1);
    	$this->graph->addEdgeDefinition($ed2);
    	$this->graph->addOrphanCollection("orphan");
    	$this->graphHandler = new GraphHandler($this->connection);
    	$this->graphHandler->createGraph($this->graph);
    	$this->graph = $this->graphHandler->getGraph($this->graphName);
    	$this->vertex1Array  = array(
    			'_key'     => "vertex1",
    			'someKey1' => 'someValue1',
    			'sharedKey1' => 1
    	);
    	$this->vertex2Array  = array(
    			'_key'     => "vertex2",
    			'someKey2' => 'someValue2',
    			'sharedKey1' => 2
    	);
    	$this->vertex3Array  = array(
    			'_key'     => "vertex3",
    			'someKey3' => 'someValue3',
    			'sharedKey1' => 1
    	);
    	$this->vertex4Array  = array(
    			'_key'     => "vertex4",
    			'someKey4' => 'someValue4',
    			'sharedKey1' => 2
    	);
    	$this->vertex5Array = array(
    			'_key'	   => "vertex5",
    			'someKey5' => 'someValue5',
    			'a' => 3,
    			'sharedKey1' => 1
    	);
    	$this->vertex6Array = array(
    			'_key'	   => "vertex6",
    			'someKey6' => 'someValue6',
    			'sharedKey1' => 1
    	);
    	$this->vertex7Array = array(
    			'_key'	   => "vertex7",
    			'someKey7' => 'someValue7',
    			'a' => 3,
    			'sharedKey1' => 1
    	);
    	$this->edge1Array    = array(
    			'_key'         => "edge1",
    			'someEdgeKey1' => 'someEdgeValue1',
    			'sharedKey1' => 1,
    			'weight' => 10
    	);
    	$this->edge2Array    = array(
    			'_key'         => "edge2",
    			'someEdgeKey2' => 'someEdgeValue2',
    			'sharedKey2' => 2,
    			'weight' => 15
    	);
    	$this->edge3Array    = array(
    			'_key'         => "edge3",
    			'sharedKey3' => 2,
    			'weight' => 12
    	);
    	$this->edge4Array   = array(
    			'_key'         => "edge4",
    			'sharedKey4' => 1,
    			'weight' => 7
    	);
    	$this->edge5Array   = array(
    			'_key'         => "edge5",
    			'sharedKey5' => 1,
    			'weight' => 5
    	);
    	$this->edge6Array   = array(
    			'_key'         => "edge6",
    			'sharedKey6' => 1,
    			'weight' => 2
    	);
    }


    // Helper method to setup a graph
    public function createGraph()
    {
    	$vertex1 = Vertex::createFromArray($this->vertex1Array);
    	$vertex2 = Vertex::createFromArray($this->vertex2Array);
    	$vertex3 = Vertex::createFromArray($this->vertex3Array);
    	$vertex4 = Vertex::createFromArray($this->vertex4Array);
    	$vertex5 = Vertex::createFromArray($this->vertex5Array);
    	$vertex6 = Vertex::createFromArray($this->vertex6Array);
    	$vertex7 = Vertex::createFromArray($this->vertex7Array);
    	$edge1   = Edge::createFromArray($this->edge1Array);
    	$edge2   = Edge::createFromArray($this->edge2Array);
    	$edge3   = Edge::createFromArray($this->edge3Array);
    	$edge4   = Edge::createFromArray($this->edge4Array);
    	$edge5   = Edge::createFromArray($this->edge5Array);
    	$this->graphHandler->saveVertex($this->graphName, $vertex1, $this->v1);
    	$this->graphHandler->saveVertex($this->graphName, $vertex2, $this->v2);
    	$this->graphHandler->saveVertex($this->graphName, $vertex3, $this->v3);
    	$this->graphHandler->saveVertex($this->graphName, $vertex4, $this->v4);
    	$this->graphHandler->saveVertex($this->graphName, $vertex5, $this->v5);
    	$this->graphHandler->saveVertex($this->graphName, $vertex6, $this->v5);
    	$this->graphHandler->saveVertex($this->graphName, $vertex7, $this->v1);
    	$this->graphHandler->saveEdge(
    			$this->graphName,
    			$this->v1 . "/" . $this->vertex1Array["_key"],
    			$this->v3  . "/" . $this->vertex3Array["_key"],
    			"edgeLabel1",
    			$edge1,
    			$this->e1
    	);
    	$this->graphHandler->saveEdge(
    			$this->graphName,
    			$this->v1  . "/" . $this->vertex7Array["_key"],
    			$this->v3  . "/" . $this->vertex3Array["_key"],
    			"edgeLabel2",
    			$edge2,
    			$this->e1
    	);
    	$this->graphHandler->saveEdge(
    			$this->graphName,
    			$this->v1  . "/" . $this->vertex7Array["_key"],
    			$this->v4  . "/" . $this->vertex4Array["_key"],
    			"edgeLabel3",
    			$edge3,
    			$this->e1
    	);
    	$this->graphHandler->saveEdge(
    			$this->graphName,
    			$this->v2  . "/" . $this->vertex2Array["_key"],
    			$this->v4  . "/" . $this->vertex4Array["_key"],
    			"edgeLabel4",
    			$edge4,
    			$this->e1
    	);
    	$this->graphHandler->saveEdge(
    			$this->graphName,
    			$this->v5  . "/" . $this->vertex5Array["_key"],
    			$this->v5  . "/" . $this->vertex6Array["_key"],
    			"edgeLabel5",
    			$edge5,
    			$this->e2
    	);
    }


    /**
     */
    public function testsaveGetUpdateReplaceRemoveVertex()
    {
    	$vertex1 = Vertex::createFromArray($this->vertex1Array);
    	$ex = null;
    	try {
    		$this->graphHandler->saveVertex($this->graphName, $vertex1 );
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	$this->createGraph();
    	
    	$ex = null;
    	try {
    		$this->graphHandler->getVertex($this->graphName, $this->vertex1Array["_key"] );
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	
    	$v1 = $this->graphHandler->getVertex($this->graphName, $this->vertex1Array["_key"] ,array(),  $this->v1);
    	$v2 = $this->graphHandler->getVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,array());
    	
    	$this->assertTrue($v1->getInternalKey() == $v2->getInternalKey());
    	
    	$v1 = $this->graphHandler->getVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,array());
    	$v = Vertex::createFromArray($this->vertex7Array);
    	$v->setRevision($v1->getRevision());
    	$ex = null;
    	try {
    		$this->graphHandler->replaceVertex($this->graphName, $this->vertex1Array["_key"] ,$v, array());
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	 
    	$v1 = $this->graphHandler->getVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,array());
    	$v = Vertex::createFromArray($this->vertex7Array);
    	$v->setRevision($v1->getRevision());
    	$this->assertTrue(
    		$this->graphHandler->replaceVertex($this->graphName, $this->vertex1Array["_key"] ,$v, array('revision' =>$v1->getRevision()),  $this->v1)
    	);
    	$v1 = $this->graphHandler->getVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,array());
    	$v = Vertex::createFromArray($this->vertex7Array);
    	$v->setRevision($v1->getRevision());
    	$this->assertTrue(
    		$this->graphHandler->replaceVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,$v, array('revision' =>true))
    	);
    	$ex = null;
    	try {
    		$this->graphHandler->updateVertex($this->graphName, $this->vertex1Array["_key"] ,$v, array());
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	$v1 = $this->graphHandler->getVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,array());
    	$v = Vertex::createFromArray($this->vertex7Array);
    	$v->setRevision($v1->getRevision());
    	$this->assertTrue(
    			$this->graphHandler->updateVertex($this->graphName, $this->vertex1Array["_key"] ,$v, array('revision' =>true),  $this->v1)
    	);
    	$v1 = $this->graphHandler->getVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,array());
    	$v = Vertex::createFromArray($this->vertex7Array);
    	$v->setRevision($v1->getRevision());
    	$this->assertTrue(
    			$this->graphHandler->updateVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,$v, array('revision' => $v1->getRevision()))
    	);
    	//removeVertex($graph, $vertexId, $revision = null, $options = array(), $collection = null)
    	$ex = null;
    	try {
    		$this->graphHandler->removeVertex($this->graphName, $this->vertex1Array["_key"], null ,array());
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	$v1 = $this->graphHandler->getVertex($this->graphName, $this->v1 . "/" . $this->vertex1Array["_key"] ,array());
    	$this->assertTrue($this->graphHandler->removeVertex($this->graphName,  $this->v1 . "/" . $this->vertex1Array["_key"], $v1->getRevision() ,array()));
    	 
    	
    }
    
    /**
     */
    public function testsaveGetUpdateReplaceRemoveEdge()
    {
    	$edge1   = Edge::createFromArray($this->edge1Array);
    	$ex = null;
    	try {
    		$this->graphHandler->saveEdge(
    				$this->graphName,  
    				$this->v1 . "/" . $this->vertex1Array["_key"], 
    				$this->v1 . "/" . $this->vertex1Array["_key"],
    				null,
    				$edge1 );
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	$this->createGraph();
    	$this->graphHandler->saveEdge(
    			$this->graphName,
    			$this->v1 . "/" . $this->vertex1Array["_key"],
    			$this->v4 . "/" . $this->vertex4Array["_key"],
    			null,
    			array(), 
    			$this->e1);
    	 
    	$ex = null;
    	try {
    		$this->graphHandler->getEdge($this->graphName, $this->edge1Array["_key"] );
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	 
    	$v1 = $this->graphHandler->getEdge($this->graphName, $this->edge1Array["_key"] ,array(),  $this->e1);
    	$v2 = $this->graphHandler->getEdge($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"] ,array());
    	 
    	$this->assertTrue($v1->getInternalKey() == $v2->getInternalKey());
    	 
    	$v1 = $this->graphHandler->getEdge($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"] ,array());
    	$v = Edge::createFromArray($this->edge1Array);
    	$v->setRevision($v1->getRevision());
    	$ex = null;
    	try {
    		$this->graphHandler->replaceEdge($this->graphName, $this->edge1Array["_key"] ,null,$v, array());
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	
    	$v1 = $this->graphHandler->getEdge($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"] ,array());
    	$v = Edge::createFromArray($this->edge1Array);
    	$v->setRevision($v1->getRevision());
    	$this->assertTrue(
    			$this->graphHandler->replaceEdge($this->graphName, $this->edge1Array["_key"],null ,$v, array('revision' =>$v1->getRevision()),  $this->e1)
    	);
    	$v1 = $this->graphHandler->getEdge($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"] ,array());
    	$v = Edge::createFromArray($this->edge1Array);
    	$v->setRevision($v1->getRevision());
    	$this->assertTrue(
    			$this->graphHandler->replaceEdge($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"],null ,$v, array('revision' =>true))
    	);
    	$ex = null;
    	try {
    		$this->graphHandler->updateEdge($this->graphName, $this->edge1Array["_key"],null ,$v, array());
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	$v1 = $this->graphHandler->getEdge($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"] ,array());
    	$v = Edge::createFromArray($this->edge1Array);
    	$v->setRevision($v1->getRevision());
    	$this->assertTrue(
    			$this->graphHandler->updateEdge($this->graphName, $this->edge1Array["_key"],null ,$v, array('revision' =>true),  $this->e1)
    	);
    	$v1 = $this->graphHandler->getEdge($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"] ,array());
    	$v = Edge::createFromArray($this->edge1Array);
    	$v->setRevision($v1->getRevision());
    	$this->assertTrue(
    			$this->graphHandler->updateEdge($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"] ,null,$v, array('revision' => $v1->getRevision()))
    	);
    	//removeVertex($graph, $vertexId, $revision = null, $options = array(), $collection = null)
    	$ex = null;
    	try {
    		$this->graphHandler->removeEdge($this->graphName, $this->edge1Array["_key"], null ,array());
    	} catch (Exception $e) {
    		$ex = $e->getMessage();
    	}
    	$this->assertTrue($ex == 'A collection must be provided.');
    	$v1 = $this->graphHandler->getVertex($this->graphName, $this->e1 . "/" . $this->edge1Array["_key"] ,array());
    	$this->assertTrue($this->graphHandler->removeEdge($this->graphName,  $this->e1 . "/" . $this->edge1Array["_key"], $v1->getRevision() ,array()));
    	
    	
    }

    /**
     */
     public function testGetNeighborVertices()
    {
    	$this->createGraph();
    	$options = array(
    		
    		"filter" => array(
    			"direction" => "in",
    			"properties" => array(
     				array(
     					"compare" => "HAS",
     					"key" => "sharedKey2"
     				),	
    				array(
    					"compare" => "HAS_NOT",
    					"key" => "someEdgeKey1"
    				)	
    			)	
    		)		
    	);
    	$e = $this->graphHandler->getNeighborVertices($this->graphName, $this->v3 . "/" . $this->vertex3Array["_key"], $options);
    	
    	
    	$options = array(
    	
    			"filter" => array(
    					"direction" => "any"
    			),
    			"maxDepth" => 3,
    	);
    	$e = $this->graphHandler->getNeighborVertices($this->graphName, $this->v3 . "/" . $this->vertex3Array["_key"], $options);
    	$this->assertTrue(count($e->getAll()) === 4);
    	
    } 	
    
    public function testGetEdges() 
    {
    	$this->createGraph();
    	$cursor = $this->graphHandler->getEdges($this->graphName);
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getMetadata();
    	$this->assertTrue(count($m["result"]) == 5);
    	$this->assertTrue($m["hasMore"] == false);
    	
    	$params = array(
    		"edgeCollectionRestriction" => $this->e1,
    		"vertexCollectionRestriction" => $this->v3,
    		"maxDepth" => "2",
    	);
    	
    	$cursor = $this->graphHandler->getEdges($this->graphName, $params);
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getMetadata();
    	$this->assertTrue(count($m["result"]) == 2);
    	$this->assertTrue($m["hasMore"] == false);
    	
    	$params = array(
    			"edgeCollectionRestriction" => $this->e1,
    			"vertexCollectionRestriction" => array($this->v1, $this->v3),
    			"maxDepth" => "2",
    			"direction" => "in",
    			"filter" => array(
    					"properties" => array(
    							array(
    									"compare" => "HAS",
    									"key" => "sharedKey2"
    							),
    							array(
    									"compare" => "HAS_NOT",
    									"key" => "someEdgeKey1"
    							)
    					)
    			)

    	);
    	 
    	$cursor = $this->graphHandler->getEdges($this->graphName, $params);
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getMetadata();
    	$this->assertTrue(count($m["result"]) == 1);
    	$this->assertTrue($m["hasMore"] == false);
    }
    
    public function testConnectedEdges()
    {
    	$this->createGraph();
    	$params = array(
    			"maxDepth" => "2",
    			"direction" => "any"
    	);
    	 
    	$cursor = $this->graphHandler->getConnectedEdges($this->graphName, $this->v1 . "/" . $this->vertex7Array["_key"], $params);
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getMetadata();
    	$this->assertTrue(count($m["result"]) == 4);
    	$this->assertTrue($m["hasMore"] == false);
    	
    	
    }
    
    
    
    public function testgetVertices()
    {
    	$this->createGraph();
    	$cursor = $this->graphHandler->getVertices($this->graphName);
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getMetadata();
    	$this->assertTrue(count($m["result"]) == 7);
    	$this->assertTrue($m["hasMore"] == false);
    	
    	$params = array(
    			"filter" => array(
    					"properties" => array(
    							array(
    									"compare" => "HAS",
    									"key" => "sharedKey1"
    							),
    							array(
    									"compare" => "HAS_NOT",
    									"key" => "someKey1"
    							)
    					)
    			)
    	);
    	 
    	$cursor = $this->graphHandler->getVertices($this->graphName, $params);
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getMetadata();
    	$this->assertTrue(count($m["result"]) == 6);
    	$this->assertTrue($m["hasMore"] == false);
    	 
    	 
    }
    
    public function testGetPaths()
    {
    	$this->createGraph();
    	$cursor = $this->graphHandler->getPaths($this->graphName, array(
    		"direction" => "out"
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 10);
    	
    	$cursor = $this->graphHandler->getPaths($this->graph, array(
    		"direction" => "any",
    		"maxDepth" => 20
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 29);
    	
    	$cursor = $this->graphHandler->getPaths($this->graph, array(
    			"direction" => "in",
    			"maxDepth" => 20,
    			"batchSize" => 2,
    			"limit" => 1,
    			"count" => true
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 1);
    	
    
    }
    
    
    public function testGetShortestPaths()
    {
    	$this->createGraph();
    	$cursor = $this->graphHandler->getShortestPaths($this->graphName, null, null, array(
    			"direction" => "out"
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 5);
    	
    	$cursor = $this->graphHandler->getShortestPaths($this->graph, null, null, array(
    			"direction" => "in",
    			"batchSize" => 2,
    			"limit" => 1,
    			"count" => true
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 1);
    	
    	$cursor = $this->graphHandler->getShortestPaths($this->graphName, null, null, array(
    			"direction" => "any",
    			"edgeCollectionRestriction" => $this->e1,
    			"startVertexCollectionRestriction" => $this->v1,
    			"endVertexCollectionRestriction" => $this->v3,
    			"edgeExamples" => array(
    				"someEdgeKey2" => "someEdgeValue2"
    			)
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 1);
    	
    	$cursor = $this->graphHandler->getShortestPaths(
    			$this->graphName, 
    			array(
    				'a' => 3,
    			), 
    			array(
    				"sharedKey1" => 1
    			),
    			array(
    				"direction" => "out"
    			));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 2);
    	 
    }
    
    public function testGetDistanceToPaths()
    {
    	$this->createGraph();
    	$cursor = $this->graphHandler->getDistanceTo($this->graphName, null, null, array(
    			"direction" => "out"
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 5);
    	 
    	$cursor = $this->graphHandler->getDistanceTo($this->graph, null, null, array(
    			"direction" => "in",
    			"batchSize" => 2,
    			"limit" => 1,
    			"count" => true
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 1);
    	 
    	$cursor = $this->graphHandler->getDistanceTo($this->graphName, null, null, array(
    			"direction" => "any",
    			"edgeCollectionRestriction" => $this->e1,
    			"startVertexCollectionRestriction" => $this->v1,
    			"endVertexCollectionRestriction" => $this->v3,
    			"edgeExamples" => array(
    					"someEdgeKey2" => "someEdgeValue2"
    			)
    	));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 1);
    	 
    	$cursor = $this->graphHandler->getDistanceTo(
    			$this->graphName,
    			array(
    					'a' => 3,
    			),
    			null,
    			array(
    					"direction" => "out"
    			));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	$this->assertTrue(count($m) === 3);
    	
    	$cursor = $this->graphHandler->getDistanceTo(
    			$this->graphName,
    			array(
    					'a' => 3,
    			),
    			null,
    			array(
    					"direction" => "out",
    					"weight" => "weight"
    			));
    	$this->assertInstanceOf('triagens\ArangoDb\Cursor', $cursor);
    	$m = $cursor->getAll();
    	 
    	$this->assertTrue(count($m) === 3);
    }
    
    public function testGetCommonNeighborVertices()
    {
    	$this->createGraph();
    	$options = array(
    
    			"direction" => "any",
    			"maxDepth" => 2
    			
    	);
    	$e = $this->graphHandler->getCommonNeighborVertices($this->graphName, null, null, $options, $options);

    	$m = $e->getAll();
    	$this->assertTrue(count($m) === 5);
    	$this->assertTrue(count($m[$this->v1 . "/" . $this->vertex1Array["_key"]]) === 4);
    	$this->assertTrue(count($m[$this->v1 . "/" . $this->vertex7Array["_key"]]) === 4);
    	$this->assertTrue(count($m[$this->v2 . "/" . $this->vertex2Array["_key"]]) === 4);
    	$this->assertTrue(count($m[$this->v3 . "/" . $this->vertex3Array["_key"]]) === 4);
    	$this->assertTrue(count($m[$this->v4 . "/" . $this->vertex4Array["_key"]]) === 4);
    	
    	$options1 = array(
    		"direction" => "out",
    		"maxDepth" => 2
    	);
    	$options2 = array(
    		"direction" => "in"
    	);
    	$e = $this->graphHandler->getCommonNeighborVertices($this->graph, array(), array(), $options1, $options2);
    	$m = $e->getAll();
    	$this->assertTrue(count($m) === 0);
    	
    	$options1 = array(
    			"direction" => "in"
    	);
    	$options2 = array(
    			"direction" => "out"
    	);
    	$e = $this->graphHandler->getCommonNeighborVertices($this->graph, array(), array(), $options1, $options2);
    	$m = $e->getAll();
    	$this->assertTrue(count($m) === 0);
    	
    	$this->graphHandler->setBatchsize(1);
    	$this->graphHandler->setCount(true);
    	$this->graphHandler->setLimit(2);
    	$e = $this->graphHandler->getCommonNeighborVertices(
    			$this->graphName, 
    			array(
    					array("_id" => $this->v1 . "/" . $this->vertex1Array["_key"]),
    					array("_id" => $this->v2 . "/" . $this->vertex2Array["_key"])
    			), 
    			array("_id" => $this->v1 . "/" . $this->vertex7Array["_key"])
    	);
    	$meta = $e->getMetadata();
    	$this->assertTrue($meta["hasMore"]);
    	$this->assertTrue($meta["count"] === 2);
    	
    	 
    }
	
    public function testGetCommonProperties()
    {
    	$this->createGraph();
    	$e = $this->graphHandler->getCommonProperties($this->graphName);
    	$m = $e->getAll();
    	$this->assertTrue(count($m) === 7);
    	
    	$e = $this->graphHandler->getCommonProperties($this->graphName, null, null, array(
    			"vertex1CollectionRestriction" => array($this->v1, $this->v2),
    			"vertex2CollectionRestriction" => array($this->v3, $this->v4)
    	));
    	$m = $e->getAll();
    	$this->assertTrue(count($m) === 3);
    	
    	$e = $this->graphHandler->getCommonProperties($this->graph, null, null, array(
    			"vertex1CollectionRestriction" => array($this->v1, $this->v2),
    			"vertex2CollectionRestriction" => array($this->v3, $this->v4),
    			"ignoreProperties" => array("sharedKey1")
    	));
    	$m = $e->getAll();
    	$this->assertTrue(count($m) === 0);
    	
    	$this->graphHandler->setBatchsize(1);
    	$this->graphHandler->setCount(true);
    	$this->graphHandler->setLimit(2);
    	$e = $this->graphHandler->getCommonProperties($this->graphName);
    	$meta = $e->getMetadata();
    	$this->assertTrue($meta["hasMore"]);
    	$this->assertTrue($meta["count"] === 2);
    	
    }
    
    public function testGetAbsEccentricity()
    {
    	$this->createGraph();
    	$e = $this->graphHandler->getAbsoluteEccentricity($this->graph);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    	
    	$e = $this->graphHandler->getAbsoluteEccentricity($this->graph, 
    		array(
    				array("_id" => $this->v1 . "/" . $this->vertex1Array["_key"]),
    				array("_id" => $this->v2 . "/" . $this->vertex2Array["_key"])
    			), 
    		array(
    				"direction" => "out",
    				"weight" => "weight"
    		)
    	);
    	$m = $e;
    	
    	$this->assertTrue($m === array(
			  $this->v1 . "/" . $this->vertex1Array["_key"] => 10, 
  			  $this->v2 . "/" . $this->vertex2Array["_key"] => 7
    		)
    	);
    	
    	$e = $this->graphHandler->getAbsoluteEccentricity($this->graph,
    			array(
    					array("_id" => $this->v1 . "/" . $this->vertex1Array["_key"]),
    					array("_id" => $this->v2 . "/" . $this->vertex2Array["_key"])
    			),
    			array(
    					"direction" => "in",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    	 
    	$this->assertTrue(count($m) === 0);
    	
    	 
    }
    
    public function testGetEccentricity()
    {
    	$this->createGraph();
    	$e = $this->graphHandler->getEccentricity($this->graph);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    	 
    	$e = $this->graphHandler->getEccentricity($this->graph,
    			array(
    					"direction" => "out",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    	 
    	$this->assertTrue(count($m) === 5);
    	 
    	$e = $this->graphHandler->getEccentricity($this->graph,
    			array(
    					"direction" => "in",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    	$this->assertTrue(count($m) === 4);
    	 
    
    }
    
    
    public function testGetAbsCloseness()
    {
    	$this->createGraph();
    	$e = $this->graphHandler->getAbsoluteCloseness($this->graph);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    	 
    	$e = $this->graphHandler->getAbsoluteCloseness($this->graph,
    			array(
    					array("_id" => $this->v1 . "/" . $this->vertex1Array["_key"]),
    					array("_id" => $this->v2 . "/" . $this->vertex2Array["_key"])
    			),
    			array(
    					"direction" => "out",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    	 
    	$this->assertTrue($m === array(
    			$this->v1 . "/" . $this->vertex1Array["_key"] => 10,
    			$this->v2 . "/" . $this->vertex2Array["_key"] => 7
    	)
    	);
    	 
    	$e = $this->graphHandler->getAbsoluteCloseness($this->graph,
    			array(
    					array("_id" => $this->v1 . "/" . $this->vertex1Array["_key"]),
    					array("_id" => $this->v2 . "/" . $this->vertex2Array["_key"])
    			),
    			array(
    					"direction" => "in",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    
    	$this->assertTrue(count($m) === 0);
    	 
    
    }
    
    public function testGetCloseness()
    {
    	$this->createGraph();
    	$e = $this->graphHandler->getCloseness($this->graph);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    
    	$e = $this->graphHandler->getCloseness($this->graph,
    			array(
    					"direction" => "out",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    
    	$this->assertTrue(count($m) === 5);
    
    	$e = $this->graphHandler->getCloseness($this->graph,
    			array(
    					"direction" => "in",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    	$this->assertTrue(count($m) === 4);
    
    
    }
    
    
	public function testGtAbsoluteBetweenness()
    {
    	$this->createGraph();
    	$e = $this->graphHandler->getAbsoluteBetweenness($this->graph);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    
    	$e = $this->graphHandler->getAbsoluteBetweenness($this->graph,
    			array(
    					"direction" => "out",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    
    	$e = $this->graphHandler->getAbsoluteBetweenness($this->graph,
    			array(
    					"direction" => "in",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    
    
    }
    
    public function testGetBetweenness()
    {
    	$this->createGraph();
    	$e = $this->graphHandler->getBetweenness($this->graph);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    
    	$e = $this->graphHandler->getBetweenness($this->graph,
    			array(
    					"direction" => "out",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    
    	$this->assertTrue(count($m) === 7);
    
    	$e = $this->graphHandler->getBetweenness($this->graph,
    			array(
    					"direction" => "in",
    					"weight" => "weight"
    			)
    	);
    	$m = $e;
    	$this->assertTrue(count($m) === 7);
    
    
    }
    
    public function testGetDiameterAndRadius()
    {
    	$this->createGraph();
    	$e = $this->graphHandler->getRadius($this->graph);
    	$this->assertTrue($e === 1);
    	$e = $this->graphHandler->getDiameter($this->graph);
    	$this->assertTrue($e === 4);
    	
    	$e = $this->graphHandler->getRadius($this->graph, array("direction" => "out"));
    	$this->assertTrue($e === 1);
    	$e = $this->graphHandler->getDiameter($this->graph, array("direction" => "out"));
    	$this->assertTrue($e === 1);
    	
    	$e = $this->graphHandler->getRadius($this->graph, array("direction" => "in"));
    	$this->assertTrue($e === 1);
    	$e = $this->graphHandler->getDiameter($this->graph, array("direction" => "in"));
    	$this->assertTrue($e === 1);
    	
    	
    	
    	$e = $this->graphHandler->getRadius($this->graph, array("weight" => "weight"));
    	$this->assertTrue($e === 5);
    	$e = $this->graphHandler->getDiameter($this->graph, array("weight" => "weight"));
    	$this->assertTrue($e === 44);
    	
    	
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
