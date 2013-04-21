<?php
namespace triagens\ArangoDb;

// get connection options from a helper file
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';


try {
    // Setup connection, graph and graph handler
    $connection   = new Connection($connectionOptions);
    $graphHandler = new GraphHandler($connection);
    $graph        = new Graph();
    $graph->set('_key', 'Graph');
    $graph->setVerticesCollection('VertexCollection');
    $graph->setEdgesCollection('EdgeCollection');
    $graphHandler->createGraph($graph);

    // Define some arrays to build the content of the vertices and edges
    $vertex1Array = array(
        '_key'     => 'vertex1',
        'someKey1' => 'someValue1'
    );
    $vertex2Array = array(
        '_key'     => 'vertex2',
        'someKey2' => 'someValue2'
    );
    $edge1Array   = array(
        '_key'         => 'edge1',
        'someEdgeKey1' => 'someEdgeValue1'
    );

    // Create documents for 2 vertices and a connecting edge
    $vertex1 = Vertex::createFromArray($vertex1Array);
    $vertex2 = Vertex::createFromArray($vertex2Array);
    $edge1   = Edge::createFromArray($edge1Array);

    // Save the vertices
    $saveResult1 = $graphHandler->saveVertex('Graph', $vertex1);
    $saveResult2 = $graphHandler->saveVertex('Graph', $vertex2);

    // Get the vertices
    $getResult1 = $graphHandler->getVertex('Graph', 'vertex1');
    $getResult2 = $graphHandler->getVertex('Graph', 'vertex2');

    // Save the connecting edge
    $saveEdgeResult1 = $graphHandler->saveEdge('Graph', 'vertex1', 'vertex2', 'somelabelValue', $edge1);

    // Get the connecting edge
    $getEdgeResult1 = $graphHandler->getEdge('Graph', 'edge1');

    // Remove vertices and edges
    $result1 = $graphHandler->removeVertex('Graph', 'vertex1');
    $result1 = $graphHandler->removeVertex('Graph', 'vertex2');
    $result1 = $graphHandler->removeEdge('Graph', 'edge1');
} catch (ConnectException $e) {
    print $e . PHP_EOL;
} catch (ServerException $e) {
    print $e . PHP_EOL;
} catch (ClientException $e) {
    print $e . PHP_EOL;
}
