<?php

/**
 * ArangoDB PHP client: graph handler
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @author    Frank Mayer
 * @author    Florian Bartels
 * @copyright Copyright 2014, triagens GmbH, Cologne, Germany
 *
 * @since     1.2
 */

namespace triagens\ArangoDb;

/**
 * A handler that manages graphs.
 *
 * <br>
 *
 * @package triagens\ArangoDb
 * @since   1.2
 */
class GraphHandler extends
    Handler
{
    /**
     * documents array index
     */
    const ENTRY_GRAPH = 'graph';

    /**
     * conditional update of edges or vertices
     */
    const OPTION_REVISION = 'revision';

    /**
     * vertex parameter
     */
    const OPTION_VERTICES = 'vertices';

    /**
     * direction parameter
     */
    const OPTION_EDGES = 'edges';

    /**
     * direction parameter
     */
    const OPTION_KEY = '_key';
    
    /**
     * collection parameter
     */
    const OPTION_COLLECTION = 'collection';
    
    /**
     * collections parameter
     */
    const OPTION_COLLECTIONS = 'collections';

    /**
     * example parameter
     */
    const KEY_FROM = '_from';

    /**
     * example parameter
     */
    const KEY_TO = '_to';
    
    /**
     * name parameter
     */
    const OPTION_NAME = 'name';

    /**
     * edge defintion parameter
     */
    const OPTION_EDGE_DEFINITION = 'edgeDefinition';
    
    /**
     * edge defintions parameter
     */
    const OPTION_EDGE_DEFINITIONS = 'edgeDefinitions';
    
    /**
     * orphan collection parameter
     */
    const OPTION_ORPHAN_COLLECTIONS = 'orphanCollections';
    
    /**
     * drop collection 
     */
    const OPTION_DROP_COLLECTION = 'dropCollection';
    
    /**
     * batchsize
     */
    private $batchsize;
    
    /**
     * count
     */
    private $count;
    
    /**
     * limit
     */
    private $limit;

    
    /**
     * Sets the batchsize for any method creating a cursor.
     * Will be reseted after the cursor has been created.
     * 
     * @param int $batchsize 
     */
    public function setBatchsize($batchsize)
    {
    	$this->batchsize = $batchsize;
    }
    
    /**
     * Sets the count for any method creating a cursor.
     * Will be reseted after the cursor has been created.
     *
     * @param int $count
     */
    public function setCount($count)
    {
    	$this->count = $count;
    }
    
    /**
     * Sets the limit for any method creating a cursor.
     * Will be reseted after the cursor has been created.
     *
     * @param int $limit
     */
    public function setLimit($limit)
    {
    	$this->limit = $limit;
    }
    
    
    /**
     * Create a graph
     *
     * This will create a graph using the given graph object and return an array of the created graph object's attributes.<br><br>
     *
     * @throws Exception
     * 
     * @param Graph $graph  - The graph object which holds the information of the graph to be created
     *
     * @return array
     * @since   1.2
     */
    public function createGraph(Graph $graph)
    {	
    	$edgeDefintions = array();
    	foreach ($graph->getEdgeDefinitions() as $ed) {
    		$edgeDefintions[] = $ed->transformToArray();
    	}
    	
    	$params   = array(
            self::OPTION_NAME      => $graph->getKey(),
            self::OPTION_EDGE_DEFINITIONS => $edgeDefintions,
            self::OPTION_ORPHAN_COLLECTIONS    => $graph->getOrphanCollections()
        );
        $url      = Urls::URL_GRAPH;
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($params));
        $json     = $response->getJson();
		$graph->setInternalId($json['graph'][Graph::ENTRY_ID]);
		$graph->set(Graph::ENTRY_KEY, $json['graph'][self::OPTION_NAME ]);
        $graph->setRevision($json['graph'][Graph::ENTRY_REV]);
        

        return $graph->getAll();
    }


    /**
     * Get a graph
     *
     * This will get a graph.<br><br>
     *
     * @throws Exception
     *
     * @param String $graph   - The name of the graph
     * @param array  $options - Options to pass to the method
     *
     * @return Graph
     * @since   1.2
     */
    public function getGraph($graph, array $options = array())
    {
        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph));
        try {
        	$response = $this->getConnection()->get($url);
        } catch (Exception $e) {
        	return false;
        }
        $data     = $response->getJson();

        $options['_isNew'] = false;

        $result =  Graph::createFromArray($data['graph'], $options);
        $result->set(Graph::ENTRY_KEY, $data['graph'][self::OPTION_NAME]);
        return $result;
    }


    /**
     * Drop a graph and remove all its vertices and edges, also drops vertex and edge collections<br><br>
     *
     * @throws Exception
     *
     * @param mixed $graph          - graph name as a string or instance of Graph
     * @param bool $dropCollections - if set to false the graphs collections will not be droped.
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function dropGraph($graph, $dropCollections = true)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        
        
        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph));
        $url  = UrlHelper::appendParamsUrl($url, array("dropCollections" => $dropCollections));
        $this->getConnection()->delete($url);
        
        return true;
    }


    /**
     * Get a graph's properties<br><br>
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     *
     * @return bool - Returns an array of attributes. Will throw if there is an error
     * @since 1.2
     */
    public function properties($graph)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $url         = UrlHelper::buildUrl(Urls::URL_DOCUMENT . "/_graphs" , array($graph));
        
        $result      = $this->getConnection()->get($url);
        $resultArray = $result->getJson();

        return $resultArray;
    }
    
    /**
     * add an orphan collection to the graph.
     *
     * This will add a further orphan collection to the graph.<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param string $orphanCollection  - the orphan collection to be added as string.
     *
     * @return Graph
     * @since 2.2
     */
    public function addOrphanCollection($graph, $orphanCollection)
    {
    	if ($graph instanceof Graph) {
    		$graph = $graph->getKey();
    	}
    
    	$url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX));
    	$data = array(
    		self::OPTION_COLLECTION => $orphanCollection
    	);
    
    	try {
    		$response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));
    	} catch (Exception $e) {
    		throw new ClientException($e->getMessage());
    	}
    
    	$data     = $response->getJson();

        $options['_isNew'] = false;

        $result =  Graph::createFromArray($data['graph'], $options);
        $result->set(Graph::ENTRY_KEY, $data['graph'][self::OPTION_NAME]);
        return $result;
    }
    
    /**
     * deletes an orphan collection from the graph.
     *
     * This will delete an orphan collection from the graph.<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param string $orphanCollection  - the orphan collection to be removed as string.
     * @param boolean $dropCollection  - if set to true the collection is deleted, not just removed from the graph.
     *
     * @return Graph
     * @since 2.2
     */
    public function deleteOrphanCollection($graph, $orphanCollection, $dropCollection= false)
    {
    	if ($graph instanceof Graph) {
    		$graph = $graph->getKey();
    	}
    
    	$url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $orphanCollection));
    	$data = array(
    			self::OPTION_DROP_COLLECTION => $dropCollection
    	);
    	$url  = UrlHelper::appendParamsUrl($url, $data);
    
    	try {
    		$response = $this->getConnection()->delete($url);
    	} catch (Exception $e) {
    		throw new ClientException($e->getMessage());
    	}
    
    	$data     = $response->getJson();

        $options['_isNew'] = false;

        $result =  Graph::createFromArray($data['graph'], $options);
        $result->set(Graph::ENTRY_KEY, $data['graph'][self::OPTION_NAME]);
        return $result;
    }
    /**
     * gets all vertex collection from the graph.
     *
     * This will get all vertex collection (orphans and used in edge definitions) from the graph.<br><br>
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     *
     * @return array
     * @since 2.2
     */
    public function getVertexCollections($graph)
    {
    	if ($graph instanceof Graph) {
    		$graph = $graph->getKey();
    	}
    
    	$url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX));
    	
    	try {
    		$response = $this->getConnection()->get($url);
    	} catch (Exception $e) {
    		throw new ClientException($e->getMessage());
    	}
    	
    	$data     = $response->getJson();
    	sort($data[self::OPTION_COLLECTIONS]);
    	return $data[self::OPTION_COLLECTIONS];
    }
    
    /**
     * adds an edge definition to the graph.
     *
     * This will add a further edge definition to the graph.<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param EdgeDefinition $edgeDefinition  - the new edge definition.
     *
     * @return Graph
     * @since 2.2
     */
    public function addEdgeDefinition($graph, $edgeDefinition)
    {
    	if ($graph instanceof Graph) {
    		$graph = $graph->getKey();
    	}
    
    	$url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE));
    	$data = $edgeDefinition->transformToArray();
    	
    	try {
    		$response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));
    		
    	} catch (Exception $e) {
    		throw new ClientException($e->getMessage());
    	}
    
    	$data     = $response->getJson();
    
    	$options['_isNew'] = false;
    
    	$result =  Graph::createFromArray($data['graph'], $options);
    	$result->set(Graph::ENTRY_KEY, $data['graph'][self::OPTION_NAME]);
    	return $result;
    }
    
    /**
     * deletes an edge definition from the graph.
     *
     * This will delete an edge definition from the graph.<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param string $edgeDefinition  - the name of the edge definitions relation.
     * @param boolean $dropCollection  - if set to true the edge definitions collections are deleted.
     *
     * @return Graph
     * @since 2.2
     */
    public function deleteEdgeDefinition($graph, $edgeDefinition, $dropCollection= false)
    {
    	if ($graph instanceof Graph) {
    		$graph = $graph->getKey();
    	}
    
    	$url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $edgeDefinition));
    	$data = array(
    			self::OPTION_DROP_COLLECTION => $dropCollection
    	);
    	$url  = UrlHelper::appendParamsUrl($url, $data);
    	try {
    		$response = $this->getConnection()->delete($url);
    	} catch (Exception $e) {
    		throw new ClientException($e->getMessage());
    	}
    
    	$data     = $response->getJson();
    
    	$options['_isNew'] = false;
    
    	$result =  Graph::createFromArray($data['graph'], $options);
    	$result->set(Graph::ENTRY_KEY, $data['graph'][self::OPTION_NAME]);
    	return $result;
    }
    /**
     * gets all edge collections from the graph.
     *
     * This will get all edge collections from the graph.<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     *
     * @return array()
     * @since 2.2
     */
    public function getEdgeCollections($graph)
    {
    	if ($graph instanceof Graph) {
    		$graph = $graph->getKey();
    	}
    
    	$url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE));
    	 
    	try {
    		$response = $this->getConnection()->get($url);
    	} catch (Exception $e) {
    		throw new ClientException($e->getMessage());
    	}
    	$data     = $response->getJson();
    	sort($data[self::OPTION_COLLECTIONS]);
    	return $data[self::OPTION_COLLECTIONS];
    }
    
    
    /**
     * replaces an edge definition of the graph.
     *
     * This will replace an edge definition in the graph.<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param EdgeDefinition $edgeDefinition  - the edge definition.
     *
     * @return Graph
     * @since 2.2
     */
    public function replaceEdgeDefinition($graph, $edgeDefinition)
    {
    	if ($graph instanceof Graph) {
    		$graph = $graph->getKey();
    	}
    
    	$url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $edgeDefinition->getRelation()));
    	$data = $edgeDefinition->transformToArray();
    	 
    	try {
    		$response = $this->getConnection()->put($url, $this->json_encode_wrapper($data));
      	} catch (Exception $e) {
      		throw new ClientException($e->getMessage());
    	}
    
    	$data     = $response->getJson();
    
    	$options['_isNew'] = false;
    
    	$result = Graph::createFromArray($data['graph'], $options);
    	$result->set(Graph::ENTRY_KEY, $data['graph'][self::OPTION_NAME]);
    	return $result;
    }
    
    /**
     * save a vertex to a graph
     *
     * This will add the vertex-document to the graph and return the vertex id
     *
     * This will throw if the vertex cannot be saved<br><br>
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param mixed $document  - the vertex to be added, can be passed as a vertex object or an array
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide 
     * the collection to store the vertex.
     *
     * @return string - id of vertex created
     * @since 1.2
     */
    public function saveVertex($graph, $document ,$collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        if (is_array($document)) {
            $document = Vertex::createFromArray($document);
        }
        if (count($this->getVertexCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getVertexCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getVertexCollections($graph);
        	$collection = $collection[0];
        }
        
        $data = $document->getAll();
        $url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $collection));

        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];
        $id        = $vertex['_id'];
        
        $document->setInternalId($vertex[Vertex::ENTRY_ID]);
        $document->setRevision($vertex[Vertex::ENTRY_REV]);

        $document->setIsNew(false);

        return $document->getInternalId();
    }


    /**
     * Get a single vertex from a graph
     *
     * This will throw if the vertex cannot be fetched from the server<br><br>
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param mixed $vertexId - the vertex identifier
     * @param array $options  optional, an array of options:
     * <p>
	 * <li><b>_includeInternals</b> - true to include the internal attributes. Defaults to false</li>
     * <li><b>includeInternals</b> - Deprecated, please use '_includeInternals'.</li>
     * <li><b>_ignoreHiddenAttributes</b> - true to show hidden attributes. Defaults to false</li>
     * <li><b>ignoreHiddenAttributes</b> - Deprecated, please use '_ignoreHiddenAttributes'.</li>
     * </p>
     * @param string $collection - if one uses a graph with more than one vertex collection one must provide the collection
     *  to load the vertex.                        
     *
     * @return Document
     * @since 1.2
     */
    public function getVertex($graph, $vertexId, array $options = array(), $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        $parts = explode( "/" , $vertexId);
        if (count($parts) === 2) {
        	$vertexId = $parts[1];
        	$collection = $parts[0]; 
        }
        if (count($this->getVertexCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getVertexCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getVertexCollections($graph);
        	$collection = $collection[0];
        }
        
        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $collection, $vertexId));
        $response = $this->getConnection()->get($url);

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];

        $options['_isNew'] = false;

        return Vertex::createFromArray($vertex, $options);
    }


    /**
     * Check if a vertex exists
     *
     * This will call self::getVertex() internally and checks if there
     * was an exception thrown which represents an 404 request.
     *
     * @throws Exception When any other error than a 404 occurs
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param mixed $vertexId  - the vertex identifier
     * @return boolean
     */
    public function hasVertex($graph, $vertexId)
    {
        try {
            // will throw ServerException if entry could not be retrieved
            $result = $this->getVertex($graph, $vertexId);
            return true;
        } catch (ServerException $e) {
            // we are expecting a 404 to return boolean false
            if ($e->getCode() === 404) {
                return false;
            }

            // just rethrow
            throw $e;
        }

        return false;
    }


    /**
     * Replace an existing vertex in a graph, identified graph name and vertex id
     *
     * This will update the vertex on the server
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the to-be-replaced vertex is the same as the one given.<br><br>
     *
     * @throws Exception
     *
     * @param mixed    $graph     - graph name as a string or instance of Graph
     * @param mixed    $vertexId  - the vertex id as string or number
     * @param Document $document  - the vertex-document to be updated
     * @param mixed    $options   optional, an array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method):
     * <p>
     * <li><b>revision</b> - revision for conditional updates ('some-revision-id' [use the passed in revision id], false or true [use document's revision])</li>
     * <li><b>policy</b> - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li><b>waitForSync</b> - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide the collection                            
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function replaceVertex($graph, $vertexId, Document $document, $options = array(), $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
		
        $parts = explode( "/" , $vertexId);
        if (count($parts) === 2) {
        	$vertexId = $parts[1];
        	$collection = $parts[0];
        }
        if (count($this->getVertexCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getVertexCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getVertexCollections($graph);
        	$collection = $collection[0];
        }
        
        $options = array_merge(array(self::OPTION_REVISION => false), $options);
        
        // This preserves compatibility for the old policy parameter.
        $params = array();
        $params = $this->validateAndIncludeOldSingleParameterInParams(
                       $options,
                       $params,
                       ConnectionOptions::OPTION_REPLACE_POLICY
        );
        $params = $this->includeOptionsInParams(
                       $options,
                       $params,
                       array(
                            'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC)
                       )
        );

        //Include the revision for conditional updates if required
        if ($options[self::OPTION_REVISION] === true) {

            $revision = $document->getRevision();

            if (!is_null($revision)) {
                $params[ConnectionOptions::OPTION_REVISION] = $revision;
            }
        } elseif ($options[self::OPTION_REVISION]) {
            $params[ConnectionOptions::OPTION_REVISION] = $options[self::OPTION_REVISION];
        }

        $data = $document->getAll();
        $url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $collection, $vertexId));
        $url  = UrlHelper::appendParamsUrl($url, $params);

        $response = $this->getConnection()->PUT($url, $this->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];
        
        $document->setInternalId($vertex[Vertex::ENTRY_ID]);
        $document->setRevision($vertex[Vertex::ENTRY_REV]);


        return true;
    }


    /**
     * Update an existing vertex in a graph, identified by graph name and vertex id
     *
     * This will update the vertex on the server
     *
     * This will throw if the vertex cannot be updated
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed vertex-document has a _rev value set, the database will check
     * that the revision of the to-be-replaced document is the same as the one given.<br><br>
     *
     * @throws Exception
     *
     * @param mixed    $graph     - graph name as a string or instance of Graph
     * @param mixed    $vertexId  - the vertex id as string or number
     * @param Document $document  - the patch vertex-document which contains the attributes and values to be updated
     * @param mixed    $options    optional, an array of options (see below)
     * <p>
     * <li><b>policy</b> - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li><b>keepNull</b> - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     * <li><b>waitForSync</b> - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide the collection
     * 
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function updateVertex($graph, $vertexId, Document $document, $options = array(), $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        $parts = explode( "/" , $vertexId);
        if (count($parts) === 2) {
        	$vertexId = $parts[1];
        	$collection = $parts[0];
        }
        if (count($this->getVertexCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getVertexCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getVertexCollections($graph);
        	$collection = $collection[0];
        }
        
        $options = array_merge(array(self::OPTION_REVISION => false), $options);
        // This preserves compatibility for the old policy parameter.
        $params = array();
        $params = $this->validateAndIncludeOldSingleParameterInParams(
                       $options,
                       $params,
                       ConnectionOptions::OPTION_UPDATE_POLICY
        );
        $params = $this->includeOptionsInParams(
                       $options,
                       $params,
                       array(
                            'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC),
                            'keepNull'    => true,
                       )
        );

        //Include the revision for conditional updates if required
        if ($options[self::OPTION_REVISION] === true) {

            $revision = $document->getRevision();

            if (!is_null($revision)) {
                $params[ConnectionOptions::OPTION_REVISION] = $revision;
            }
        } elseif ($options[self::OPTION_REVISION]) {
            $params[ConnectionOptions::OPTION_REVISION] = $options[self::OPTION_REVISION];
        }

        $url    = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $collection, $vertexId));
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->patch($url, $this->json_encode_wrapper($document->getAll()));
        $json   = $result->getJson();
        $vertex = $json['vertex'];
        $document->setRevision($vertex[Vertex::ENTRY_REV]);

        return true;
    }


    /**
     * Remove a vertex from a graph, identified by the graph name and vertex id<br><br>
     *
     * @throws Exception
     *
     * @param mixed  $graph     - graph name as a string or instance of Graph
     * @param mixed  $vertexId  - the vertex id as string or number
     * @param mixed  $revision  - optional, the revision of the vertex to be deleted
     * @param mixed  $options    optional, an array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     * <p>
     * <li><b>policy</b> - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li><b>waitForSync</b> - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide the collection
     * 
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function removeVertex($graph, $vertexId, $revision = null, $options = array(), $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        $parts = explode( "/" , $vertexId);
        if (count($parts) === 2) {
        	$vertexId = $parts[1];
        	$collection = $parts[0];
        }
        if (count($this->getVertexCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getVertexCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getVertexCollections($graph);
        	$collection = $collection[0];
        }
        
        // This preserves compatibility for the old policy parameter.
        $params = array();
        $params = $this->validateAndIncludeOldSingleParameterInParams(
                       $options,
                       $params,
                       ConnectionOptions::OPTION_DELETE_POLICY
        );
        $params = $this->includeOptionsInParams(
                       $options,
                       $params,
                       array(
                            'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC),
                            'keepNull'    => true,
                       )
        );

        if (!is_null($revision)) {
            $params[ConnectionOptions::OPTION_REVISION] = $revision;
        }

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $collection, $vertexId));
        $url = UrlHelper::appendParamsUrl($url, $params);
        $this->getConnection()->delete($url);

        return true;
    }


    /**
     * save an edge to a graph
     *
     * This will save the edge to the graph and return the edges-document's id
     *
     * This will throw if the edge cannot be saved<br><br>
     *
     * @throws Exception
     *
     * @param mixed $graph     - graph name as a string or instance of Graph
     * @param mixed $from      - the 'from' vertex
     * @param mixed $to        - the 'to' vertex
     * @param mixed $label     - (optional) a label for the edge
     * @param mixed $document  - the edge-document to be added, can be passed as an edge object or an array
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide the collection
     *
     * @return mixed - id of edge created
     * @since 1.2
     */
    public function saveEdge($graph, $from, $to, $label = null, $document, $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
		if (count($this->getEdgeCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getEdgeCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getEdgeCollections($graph);
        	$collection = $collection[0];
        }
        
        
        if (is_array($document)) {
            $document = Edge::createFromArray($document);
        }
        if (!is_null($label)) {
            $document->set('$label', $label);
        }
        $document->setFrom($from);
        $document->setTo($to);
        $data                 = $document->getAll();
        $data[self::KEY_FROM] = $from;
        $data[self::KEY_TO]   = $to;
        
        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $collection));
        
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));
        
        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];

        $document->setInternalId($edge[Edge::ENTRY_ID]);
        $document->setRevision($edge[Edge::ENTRY_REV]);

        $document->setIsNew(false);

        return $document->getInternalId();
    }


    /**
     * Get a single edge from a graph
     *
     * This will throw if the edge cannot be fetched from the server<br><br>
     *
     * @throws Exception
     *
     * @param mixed $graph     - graph name as a string or instance of Graph
     * @param mixed $edgeId    - edge identifier
     * @param array $options   optional, array of options
     * <p>
     * <li><b>_includeInternals</b> - true to include the internal attributes. Defaults to false</li>
     * <li><b>includeInternals</b> - Deprecated, please use '_includeInternals'.</li>
     * <li><b>_ignoreHiddenAttributes</b> - true to show hidden attributes. Defaults to false</li>
     * <li><b>ignoreHiddenAttributes</b> - Deprecated, please use '_ignoreHiddenAttributes'.</li>
     * </p>
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide the collection                         
     *
     * @return Document - the edge document fetched from the server
     * @since 1.2
     */
    public function getEdge($graph, $edgeId, array $options = array(), $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        $parts = explode( "/" , $edgeId);
        if (count($parts) === 2) {
        	$edgeId = $parts[1];
        	$collection = $parts[0];
        }
        if (count($this->getEdgeCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getEdgeCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getEdgeCollections($graph);
        	$collection = $collection[0];
        }

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $collection, $edgeId));
        $response = $this->getConnection()->get($url);

        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];

        $options['_isNew'] = false;

        return Edge::createFromArray($edge, $options);
    }


    /**
     * Check if an edge exists
     *
     * This will call self::getEdge() internally and checks if there
     * was an exception thrown which represents an 404 request.
     *
     * @throws Exception When any other error than a 404 occurs
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param mixed $edgeId  - the vertex identifier
     * @return boolean
     */
    public function hasEdge($graph, $edgeId)
    {
        try {
            // will throw ServerException if entry could not be retrieved
            $result = $this->getEdge($graph, $edgeId);
            return true;
        } catch (ServerException $e) {
            // we are expecting a 404 to return boolean false
            if ($e->getCode() === 404) {
                return false;
            }

            // just rethrow
            throw $e;
        }

        return false;
    }


    /**
     * Replace an existing edge in a graph, identified graph name and edge id
     *
     * This will replace the edge on the server
     *
     * This will throw if the edge cannot be Replaced
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the to-be-replaced edge is the same as the one given.<br><br>
     *
     * @throws Exception
     *
     * @param mixed $graph     - graph name as a string or instance of Graph
     * @param mixed $edgeId    - edge id as string or number
     * @param mixed $label     - label for the edge or ''
     * @param Edge  $document  - edge document to be updated
     * @param mixed $options   optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     * <p>
     * <li><b>policy</b> - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li><b>waitForSync</b> - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide the collection
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function replaceEdge($graph, $edgeId, $label, Edge $document, $options = array(), $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        $parts = explode( "/" , $edgeId);
        if (count($parts) === 2) {
        	$edgeId = $parts[1];
        	$collection = $parts[0];
        }
        if (count($this->getEdgeCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getEdgeCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getEdgeCollections($graph);
        	$collection = $collection[0];
        }

        $options = array_merge(array(self::OPTION_REVISION => false), $options);

        // This preserves compatibility for the old policy parameter.
        $params = array();
        $params = $this->validateAndIncludeOldSingleParameterInParams(
                       $options,
                       $params,
                       ConnectionOptions::OPTION_REPLACE_POLICY
        );
        $params = $this->includeOptionsInParams(
                       $options,
                       $params,
                       array(
                            'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC)
                       )
        );

        //Include the revision for conditional updates if required
        if ($options[self::OPTION_REVISION] === true) {

            $revision = $document->getRevision();

            if (!is_null($revision)) {
                $params[ConnectionOptions::OPTION_REVISION] = $revision;
            }
        } elseif ($options[self::OPTION_REVISION]) {
            $params[ConnectionOptions::OPTION_REVISION] = $options[self::OPTION_REVISION];
        }

        $data = $document->getAll();
        if (!is_null($label)) {
            $document->set('$label', $label);
        }

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $collection,  $edgeId));
        $url = UrlHelper::appendParamsUrl($url, $params);

        $response = $this->getConnection()->PUT($url, $this->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];
        
        $document->setInternalId($edge[Edge::ENTRY_ID]);
        $document->setRevision($edge[Edge::ENTRY_REV]);

        return true;
    }


    /**
     * Update an existing edge in a graph, identified by graph name and edge id
     *
     * This will update the edge on the server
     *
     * This will throw if the edge cannot be updated
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed edge-document has a _rev value set, the database will check
     * that the revision of the to-be-replaced document is the same as the one given.<br><br>
     *
     * @throws Exception
     *
     * @param mixed  $graph     - graph name as a string or instance of Graph
     * @param mixed  $edgeId    - edge id as string or number
     * @param mixed  $label     - label for the edge or ''
     * @param Edge   $document  - patch edge-document which contains the attributes and values to be updated
     * @param mixed  $options   optional, array of options (see below)
     * <p>
     * <li><b>policy</b> - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li><b>keepNull</b> - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     * <li><b>waitForSync</b> - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide the collection                          
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function updateEdge($graph, $edgeId, $label, Edge $document, $options = array(), $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        $parts = explode( "/" , $edgeId);
        if (count($parts) === 2) {
        	$edgeId = $parts[1];
        	$collection = $parts[0];
        }
        if (count($this->getEdgeCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getEdgeCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getEdgeCollections($graph);
        	$collection = $collection[0];
        }

        $options = array_merge(array(self::OPTION_REVISION => false), $options);

        // This preserves compatibility for the old policy parameter.
        $params = array();
        $params = $this->validateAndIncludeOldSingleParameterInParams(
                       $options,
                       $params,
                       ConnectionOptions::OPTION_UPDATE_POLICY
        );
        $params = $this->includeOptionsInParams(
                       $options,
                       $params,
                       array(
                            'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC),
                            'keepNull'    => true,
                       )
        );
        $policy = null;

        //Include the revision for conditional updates if required
        if ($options[self::OPTION_REVISION] === true) {

            $revision = $document->getRevision();

            if (!is_null($revision)) {
                $params[ConnectionOptions::OPTION_REVISION] = $revision;
            }
        } elseif ($options[self::OPTION_REVISION]) {
            $params[ConnectionOptions::OPTION_REVISION] = $options[self::OPTION_REVISION];
        }

        if (!is_null($label)) {
            $document->set('$label', $label);
        }

        $url    = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $collection, $edgeId));
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->patch($url, $this->json_encode_wrapper($document->getAll()));
        $json   = $result->getJson();
        $edge   = $json['edge'];
        $document->setRevision($edge[Edge::ENTRY_REV]);

        return true;
    }


    /**
     * Remove a edge from a graph, identified by the graph name and edge id<br><br>
     *
     * @throws Exception
     *
     * @param mixed  $graph     - graph name as a string or instance of Graph
     * @param mixed  $edgeId    - edge id as string or number
     * @param mixed  $revision  - optional revision of the edge to be deleted
     * @param mixed  $options  optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     * <p>
     * <li><b>policy</b> - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li><b>waitForSync</b> - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     * @param string $collection  - if one uses a graph with more than one vertex collection one must provide the collection                          
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function removeEdge($graph, $edgeId, $revision = null, $options = array(), $collection = null)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        $parts = explode( "/" , $edgeId);
        if (count($parts) === 2) {
        	$edgeId = $parts[1];
        	$collection = $parts[0];
        }
        if (count($this->getEdgeCollections($graph)) !== 1 && $collection === null) {
        	throw new ClientException('A collection must be provided.');
        } else if (count($this->getEdgeCollections($graph)) === 1 && $collection === null) {
        	$collection = $this->getEdgeCollections($graph);
        	$collection = $collection[0];
        }

        // This preserves compatibility for the old policy parameter.
        $params = array();
        $params = $this->validateAndIncludeOldSingleParameterInParams(
                       $options,
                       $params,
                       ConnectionOptions::OPTION_DELETE_POLICY
        );
        $params = $this->includeOptionsInParams(
                       $options,
                       $params,
                       array(
                            'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC),
                            'keepNull'    => true,
                       )
        );
        if (!is_null($revision)) {
            $params[ConnectionOptions::OPTION_REVISION] = $revision;
        }

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $collection, $edgeId));
        $url = UrlHelper::appendParamsUrl($url, $params);
        $this->getConnection()->delete($url);

        return true;
    }


    /**
     * Get neighboring vertices of a given vertex
     *
     * This method accepts multiple argument types for the <b>vertex examples</b>, these can be:
     * <li>a vertex id </li>
     * <li>'null' to select every vertex</li>
     * <li>an array containing key value pairs that must match a vertex</li>
     * <li>an array of arrays containing key value pairs that must match a vertex. 
     * This example means that you can define filters and combine them with "or".</li><br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed      $graph        - graph name as a string or instance of Graph
     * @param mixed      $vertexExample     - see functions introduction
     * @param bool|array $options      an array of optional parameters:
     * <ul>
     * <li><b>batchSize</b> - the batch size of the returned cursor (deprecated, use 'setBatchsize' instead)</li>
     * <li><b>limit</b> - limit the result size by a give number  (deprecated, use 'setLimit' instead)</li>
     * <li><b>count</b> - return the total number of results  Defaults to false  (deprecated, use 'setCount' instead)</li>
     * <li><b>filter</b> - a optional filter</li>
     * <ul>Filter options are :<br>
     * <li><b>direction</b> - Filter for inbound (value "in") or outbound (value "out") neighbors. Default value is "any".</li>
     * <li><b>labels</b> - filter by an array of edge labels (empty array means no restriction).</li>
     * <li><b>properties</b> - filter neighbors by an array of edge properties</li>
     * <ul>Properties options are :<br>
     * <li><b>key</b> - Filter the result vertices by a key value pair.</li>
     * <li><b>value</b> -  The value of the key.</li>
     * <li><b>compare</b> - A comparison operator. (==, >, <, >=, <= )</li>
     * </ul>
     * </ul>
     * <li><b>minDepth</b> -  Defines the minimal length of a path from an edge to a vertex (default is 1, which means only the edges directly 
     *  connected to a vertex would be returned).
	 * <li><b>maxDepth</b> - Defines the maximal length of a path from an edge to a vertex (default is 1, which means only the edges directly
	 *  connected to a vertex would be returned).
     * <li><b>_sanitize</b> - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li><b>sanitize</b> - Deprecated, please use '_sanitize'.</li>
     * <li><b>_hiddenAttributes</b> - Set an array of hidden attributes for created documents.
     * <li><b>hiddenAttributes</b> - Deprecated, please use '_hiddenAttributes'.</li>
     * <ul>
     * This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     * The difference is, that if you're returning a resultset of documents, the getAll() is already called <br>
     * and the hidden attributes would not be applied to the attributes.<br>
     * </ul>
     * </li>
     * </ul>
     *
     * @return Cursor
     */
    public function getNeighborVertices($graph, $vertexExample, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $options['objectType'] = 'vertex';
        $data                  = array_merge($options, $this->getCursorOptions($options));
        $filterString = 'FILTER ';
        $statement = new Statement($this->getConnection(), array(
        		"query"     => ''
        ));
        $statement->setResultType($options['objectType']);
        $aql = "FOR a IN GRAPH_NEIGHBORS(@graphName, @vertex, @options) ";
        if (isset($options['filter']) && isset($options['filter']['direction'])) {
        	if ($options['filter']['direction'] === "in") {
        		$options['filter']['direction'] = "inbound";
        	}
        	if ($options['filter']['direction'] === "out") {
        		$options['filter']['direction'] = "outbound";
        	}
        	$options['direction'] = $options['filter']['direction'];
        }
        if (isset($options['filter']) && isset($options['filter']['properties'])) {
        	if (!isset($options['filter']['properties'][0])) {
        		$tmp = $options['filter']['properties'];
        		$options['filter']['properties'] = array();
        		$options['filter']['properties'][0] = $tmp;
        	}
        	$i = 0;
        	foreach ($options['filter']['properties'] as $filter) {
        		$i++;
        		switch ($filter['compare']) {
        			case "HAS":
        				$aql .= $filterString . "HAS(a.path.edges[0], @key" . $i . ") ";
        				$statement->bind('key' . $i, $filter['key']);
        				break;
        			case "HAS_NOT":
        				$aql .= $filterString . "!HAS(a.path.edges[0], @key" . $i . ") ";
        				$statement->bind('key' . $i, $filter['key']);
        				break;
        			default:
        				$aql .= $filterString . "a.path.edges[0][@key" . $i . "] " .  $filter['compare'] . " @value" . $i;
        				$statement->bind('key' . $i, $filter['key']);
        				$statement->bind('value' . $i, $filter['value']);
        				break;
        		} 
        		$filterString = ' && ';
        	}
        }
        
        if (isset($options['filter']) && isset($options['filter']['labels'])) {
        	$aql .= $filterString . 'a.path.edges[0]["$label"] IN @labels ';
        	$statement->bind('labels', $options['filter']['labels']);
        }
        unset($options['filter']);  
        $statement->bind('graphName', $graph);
        $statement->bind('vertex', $vertexExample);
        $statement->bind('options', $options);
        if (isset($options["batchSize"])) {
        	$this->setBatchsize($options["batchSize"]);
        	$statement->setBatchSize($options["batchSize"]);
        }
        if (isset($options["count"])) {
        	$this->setCount($options["count"]);
        	$statement->setCount($options["count"]);
        }
        if (isset($options["limit"])) {
        	$this->setLimit($options["limit"]);
        	$aql .= " LIMIT " . $options["limit"];
        }
        if (isset($this->batchsize)) {
        	$statement->setBatchSize($this->batchsize);
        	unset($this->batchsize);
        }
        if (isset($this->count)) {
        	$statement->setCount($this->count);
        	unset($this->count);
        }
        if (isset($this->limit)) {
        	$aql .= " LIMIT " . $this->limit;
        	unset($this->limit);
        }
        
        $aql .= " RETURN a.vertex";
        $statement->setQuery($aql);
        return $statement->execute();
        
    }


    /**
     * Get connected edges of a given vertex
     *
     * This will throw if the list cannot be fetched from the server<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed      $graph        - graph name as a string or instance of Graph
     * @param mixed      $vertexId     - the vertex id
     * @param bool|array $options      - an array of optional parameters:
     * <ul><br>
     * <li><b>batchSize</b> - the batch size of the returned cursor (deprecated, use 'setBatchsize' instead)</li>
     * <li><b>limit</b> - limit the result size by a give number  (deprecated, use 'setLimit' instead)</li>
     * <li><b>count</b> - return the total number of results  Defaults to false  (deprecated, use 'setCount' instead)</li>
     * <li><b>filter</b> - a optional filter</li>
     * <ul>Filter options are :<br>
     * <li><b>direction</b> - Filter for inbound (value "in") or outbound (value "out") neighbors. Default value is "any".</li>
     * <li><b>labels</b> - filter by an array of edge labels (empty array means no restriction).</li>
     * <li><b>properties</b> - filter neighbors by an array of edge properties</li>
     * <ul>Properties options are :<br>
     * <li><b>key</b> - Filter the result vertices by a key value pair.</li>
     * <li><b>value</b> -  The value of the key.</li>
     * <li><b>compare</b> - A comparison operator. (==, >, <, >=, <= )</li>
     * </ul>
     * </ul>
     * <li><b>minDepth</b> -  Defines the minimal length of a path from an edge to a vertex 
	 * (default is 1, which means only the edges directly connected to a vertex would be returned).
	 * <li><b>maxDepth</b> - Defines the maximal length of a path from an edge to a vertex 
	 * (default is 1, which means only the edges directly connected to a vertex would be returned).
     * <li><b>_sanitize</b> - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li><b>sanitize</b> - Deprecated, please use '_sanitize'.</li>
     * <li><b>_hiddenAttributes</b> - Set an array of hidden attributes for created documents.
     * <li><b>hiddenAttributes</b> - Deprecated, please use '_hiddenAttributes'.</li>
     * <ul>
     * This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     * The difference is, that if you're returning a resultset of documents, the getAll() is already called <br>
     * and the hidden attributes would not be applied to the attributes.<br>
     * </ul>
     * </li>
     * </ul>
     *
     * @return Cursor
     */
    public function getConnectedEdges($graph, $vertexId, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        if (isset($options["filter"]) && isset($options["filter"]["direction"])) {
        	$options["direction"] = $options["filter"]["direction"];
        }
        $options['vertexExample'] = $vertexId;
        
        return $this->getEdges($graph, $options);
    }

    /**
     * Get all vertices of a graph
     *
     * This will throw if the list cannot be fetched from the server<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed      $graph        - graph name as a string or instance of Graph
     * @param bool|array $options      - an array of optional parameters:
     * <ul><br>
     * <li><b>batchSize</b> - the batch size of the returned cursor (deprecated, use 'setBatchsize' instead)</li>
     * <li><b>limit</b> - limit the result size by a give number  (deprecated, use 'setLimit' instead)</li>
     * <li><b>count</b> - return the total number of results  Defaults to false  (deprecated, use 'setCount' instead)</li>
     * <li><b>filter</b> - a optional filter</li>
     * <ul>Filter options are :<br>
     * <li><b>properties</b> - filter neighbors by an array of edge properties</li>
     * <ul>Properties options are :<br>
     * <li><b>key</b> - Filter the result vertices by a key value pair.</li>
     * <li><b>value</b> -  The value of the key.</li>
     * <li><b>compare</b> - A comparison operator. (==, >, <, >=, <= )</li>
     * </ul>
     * </ul>
     * <li><b>_sanitize</b> - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li><b>sanitize</b> - Deprecated, please use '_sanitize'.</li>
     * <li><b>_hiddenAttributes</b> - Set an array of hidden attributes for created documents.
     * <li><b>hiddenAttributes</b> - Deprecated, please use '_hiddenAttributes'.</li>
     * <ul>
     * This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     * The difference is, that if you're returning a resultset of documents, the getAll() is already called <br>
     * and the hidden attributes would not be applied to the attributes.<br>
     * </ul>
     * </li>
     * </ul>
     *
     * @return Cursor
     */
    public function getVertices($graph, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        
        $options['objectType'] = 'vertex';
        $data                  = array_merge($options, $this->getCursorOptions($options));
        $filterString = 'FILTER ';
        $statement = new Statement($this->getConnection(), array(
        		"query"     => ''
        ));
        $aql = "FOR a IN GRAPH_VERTICES(@graphName, {}, @options) ";
        
        if (isset($options['filter']) && isset($options['filter']['properties'])) {
        	if (!isset($options['filter']['properties'][0])) {
        		$tmp = $options['filter']['properties'];
        		$options['filter']['properties'] = array();
        		$options['filter']['properties'][0] = $tmp;
        	}
        	$i = 0;
        	foreach ($options['filter']['properties'] as $filter) {
        		$i++;
        		switch ($filter['compare']) {
        			case "HAS":
        				$aql .= $filterString . "HAS(a, @key" . $i . ") ";
        				$statement->bind('key' . $i, $filter['key']);
        				break;
        			case "HAS_NOT":
        				$aql .= $filterString . "!HAS(a, @key" . $i . ") ";
        				$statement->bind('key' . $i, $filter['key']);
        				break;
        			default:
        				$aql .= $filterString . "a[@key" . $i . "] " .  $filter['compare'] . " @value" . $i;
        				$statement->bind('key' . $i, $filter['key']);
        				$statement->bind('value' . $i, $filter['value']);
        				break;
        		} 
        		$filterString = ' && ';
        	}
        }
        
        $statement->bind('graphName', $graph);
        $statement->bind('options', $options);
    	if (isset($options["batchSize"])) {
        	$this->setBatchsize($options["batchSize"]);
        	$statement->setBatchSize($options["batchSize"]);
        }
        if (isset($options["count"])) {
        	$this->setCount($options["count"]);
        	$statement->setCount($options["count"]);
        }
        if (isset($options["limit"])) {
        	$this->setLimit($options["limit"]);
        	$aql .= " LIMIT " . $options["limit"];
        }
        if (isset($this->batchsize)) {
        	$statement->setBatchSize($this->batchsize);
        	unset($this->batchsize);
        }
        if (isset($this->count)) {
        	$statement->setCount($this->count);
        	unset($this->count);
        }
        if (isset($this->limit)) {
        	$aql .= " LIMIT " . $this->limit;
        	unset($this->limit);
        }
        
        $aql .= " RETURN a";
        $statement->setQuery($aql);
        return $statement->execute();
    }


    /**
     * Get edges of a graph
     *
     * This will throw if the list cannot be fetched from the server<br><br>
     *
     *
     * @throws Exception
     *
     * @param mixed      $graph        - graph name as a string or instance of Graph
     * @param bool|array $options      - an array of optional parameters:
     * <ul><br>
     * <li><b>batchSize</b> - the batch size of the returned cursor (deprecated, use 'setBatchsize' instead)</li>
     * <li><b>limit</b> - limit the result size by a give number  (deprecated, use 'setLimit' instead)</li>
     * <li><b>count</b> - return the total number of results  Defaults to false  (deprecated, use 'setCount' instead)</li>
     * <li><b>filter</b> - a optional filter</li>
     * <ul>Filter options are :<br>
     * <li><b>properties</b> - filter neighbors by an array of edge properties</li>
     * <li><b>labels</b> - filter by an array of edge labels (empty array means no restriction).</li>
     * <ul>Properties options are :<br>
     * <li><b>key</b> - Filter the result edges by a key value pair.</li>
     * <li><b>value</b> -  The value of the key.</li>
     * <li><b>compare</b> - A comparison operator. (==, >, <, >=, <= )</li>
     * </ul>
     * </ul>                                
     * <li><b>vertexExample</b> - An example for the desired vertices.
     * <li><b>direction</b> - The direction of the edges as a string. 
     * Possible values are *outbound*, *inbound* and *any* (default).
     * <li>edgeCollectionRestriction*  - One or multiple edge collection names. 
     * Only edges from these collections will be considered for the path.
     * <li>vertexCollectionRestriction* - One or multiple vertex collection names. 
     * Only vertices from these collections will be considered as start vertex of a path.
	 * <li><b>minDepth</b> -  Defines the minimal length of a path from an edge to a vertex 
	 * (default is 1, which means only the edges directly connected to a vertex would be returned).
	 * <li><b>maxDepth</b> - Defines the maximal length of a path from an edge to a vertex 
	 * (default is 1, which means only the edges directly connected to a vertex would be returned).
     * <li><b>_sanitize</b> - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li><b>sanitize</b> - Deprecated, please use '_sanitize'.</li>
     * <li><b>_hiddenAttributes</b> - Set an array of hidden attributes for created documents.
     * <li><b>hiddenAttributes</b> - Deprecated, please use '_hiddenAttributes'.</li>
     * <ul>
     * This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     * The difference is, that if you're returning a resultset of documents, the getAll() is already called <br>
     * and the hidden attributes would not be applied to the attributes.<br>
     * </ul>
     * </li>
     * </ul>
     *
     * @return Cursor
     */
    public function getEdges($graph, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }
        
        $options['objectType'] = 'edge';
        $data                  = array_merge($options, $this->getCursorOptions($options));
        $filterString = 'FILTER ';
        $statement = new Statement($this->getConnection(), array(
        		"query"     => ''
        ));
        $statement->setResultType($options['objectType']);
        $p = "{}";
        if (isset($options["vertexExample"])) {
        	$p = "@ve";
        	$statement->bind('ve', $options["vertexExample"]);
        }
        $aql = "FOR a IN GRAPH_EDGES(@graphName, " . $p . ", @options) ";
        
        if (isset($options['direction'])) {
        	if ($options['direction'] === "in") {
        		$options['direction'] = "inbound";
        	}
        	if ($options['direction'] === "out") {
        		$options['direction'] = "outbound";
        	}
        }
        
        if (isset($options['filter']) && isset($options['filter']['properties'])) {
        	if (!isset($options['filter']['properties'][0])) {
        		$tmp = $options['filter']['properties'];
        		$options['filter']['properties'] = array();
        		$options['filter']['properties'][0] = $tmp;
        	}
        	$i = 0;
        	foreach ($options['filter']['properties'] as $filter) {
        		$i++;
        		switch ($filter['compare']) {
        			case "HAS":
        				$aql .= $filterString . "HAS(a, @key" . $i . ") ";
        				$statement->bind('key' . $i, $filter['key']);
        				break;
        			case "HAS_NOT":
        				$aql .= $filterString . "!HAS(a, @key" . $i . ") ";
        				$statement->bind('key' . $i, $filter['key']);
        				break;
        			default:
        				$aql .= $filterString . "a[@key" . $i . "] " .  $filter['compare'] . " @value" . $i;
        				$statement->bind('key' . $i, $filter['key']);
        				$statement->bind('value' . $i, $filter['value']);
        				break;
        		} 
        		$filterString = ' && ';
        	}
        }
        
        if (isset($options['filter']) && isset($options['filter']['labels'])) {
        	$aql .= $filterString . 'a["$label"] IN @labels ';
        	$statement->bind('labels', $options['filter']['labels']);
        }
        
        $statement->bind('graphName', $graph);
        $statement->bind('options', $options);
    	if (isset($options["batchSize"])) {
        	$this->setBatchsize($options["batchSize"]);
        	$statement->setBatchSize($options["batchSize"]);
        }
        if (isset($options["count"])) {
        	$this->setCount($options["count"]);
        	$statement->setCount($options["count"]);
        }
        if (isset($options["limit"])) {
        	$this->setLimit($options["limit"]);
        	$aql .= " LIMIT " . $options["limit"];
        }
        if (isset($this->batchsize)) {
        	$statement->setBatchSize($this->batchsize);
        	unset($this->batchsize);
        }
        if (isset($this->count)) {
        	$statement->setCount($this->count);
        	unset($this->count);
        }
        if (isset($this->limit)) {
        	$aql .= " LIMIT " . $this->limit;
        	unset($this->limit);
        }
        
        $aql .= " RETURN a";
        $statement->setQuery($aql);
        return $statement->execute();
     }
     
     
     
     /**
      * Get all pathes of a graph
      *
      * This will throw if the list cannot be fetched from the server<br><br>
      *
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>batchSize</b> - the batch size of the returned cursor (deprecated, use 'setBatchsize' instead)</li>
      * <li><b>limit</b> - limit the result size by a give number  (deprecated, use 'setLimit' instead)</li>
      * <li><b>count</b> - return the total number of results  Defaults to false  (deprecated, use 'setCount' instead)</li>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).
      * <li>followCycles*  - If set to *true* the query follows cycles in the graph,
  	  *	default is false.
      * <li><b>minDepth</b> -  Defines the minimal length of a path from an edge to a vertex
      * (default is 1, which means only the edges directly connected to a vertex would be returned).
      * <li><b>maxDepth</b> - Defines the maximal length of a path from an edge to a vertex
      * (default is 1, which means only the edges directly connected to a vertex would be returned).
      * </li>
      * </ul>
      *
      * @return Cursor
      */
     public function getPaths($graph, $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     
     	$options['objectType'] = 'path';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "FOR a IN GRAPH_PATHS(@graphName,  @options) ";
     
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     
     	$statement->bind('graphName', $graph);
     	$statement->bind('options', $options);
     	if (isset($options["batchSize"])) {
        	$this->setBatchsize($options["batchSize"]);
        	$statement->setBatchSize($options["batchSize"]);
        }
        if (isset($options["count"])) {
        	$this->setCount($options["count"]);
        	$statement->setCount($options["count"]);
        }
        if (isset($options["limit"])) {
        	$this->setLimit($options["limit"]);
        	$aql .= " LIMIT " . $options["limit"];
        }
        if (isset($this->batchsize)) {
        	$statement->setBatchSize($this->batchsize);
        	unset($this->batchsize);
        }
        if (isset($this->count)) {
        	$statement->setCount($this->count);
        	unset($this->count);
        }
        if (isset($this->limit)) {
        	$aql .= " LIMIT " . $this->limit;
        	unset($this->limit);
        }
        
     	$aql .= " RETURN a";
     	$statement->setQuery($aql);
     	return $statement->execute();
     }
     
     /**
      * Get the shortest pathes of a graph
      *
      * This will throw if the list cannot be fetched from the server
      * This method accepts multiple argument types for the <b>vertex examples</b>, these can be:
      * <li>a vertex id </li>
      * <li><b>null</b> to select every vertex</li>
      * <li>an array containing key value pairs that must match a vertex</li>
      * <li>an array of arrays containing key value pairs that must match a vertex. 
      * This example means that you can define filters and combine them with "or".</li><br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param mixed      $startVertexExample  - see functions introduction
      * @param mixed      $endVertexExample    - see functions introduction
      * 
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>batchSize</b> - the batch size of the returned cursor (deprecated, use 'setBatchsize' instead)</li>
      * <li><b>limit</b> - limit the result size by a give number  (deprecated, use 'setLimit' instead)</li>
      * <li><b>count</b> - return the total number of results  Defaults to false  (deprecated, use 'setCount' instead)</li>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      * <li><b>edgeCollectionRestriction</b> - One or multiple edge
	  *	collection names. Only edges from these collections will be considered for the path.</li>
	  * <li><b>startVertexCollectionRestriction</b> - One or multiple vertex
      *	collection names. Only vertices from these collections will be considered as start vertex of a path.</li>
      * <li><b>endVertexCollectionRestriction</b> - One or multiple vertex
      *	collection names. Only vertices from these collections will be considered as end vertex of a path.</li>
      * <li><b>edgeExamples'  - A filter example for the edges in the shortest paths, see introduction.
	  *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the 
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.
	  * <li><b>weight'  - The name of the attribute of the edges containing the length as a string.
	  * <li><b>defaultWeight'  - Only used with the option *weight*. If an edge does not have the attribute named as 
	  * defined in option *weight* this default.
      * </li>
      * </ul>
      *
      * @return Cursor
      */
     public function getShortestPaths($graph, 
     								  $startVertexExample = null,
                                      $endVertexExample = null,
                                      $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'shortestPath';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "FOR a IN GRAPH_SHORTEST_PATH(@graphName, @start, @end , @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('start', $startVertexExample);
     	$statement->bind('end', $endVertexExample);
     	$statement->bind('options', $options);
     	if (isset($options["batchSize"])) {
        	$this->setBatchsize($options["batchSize"]);
        	$statement->setBatchSize($options["batchSize"]);
        }
        if (isset($options["count"])) {
        	$this->setCount($options["count"]);
        	$statement->setCount($options["count"]);
        }
        if (isset($options["limit"])) {
        	$this->setLimit($options["limit"]);
        	$aql .= " LIMIT " . $options["limit"];
        }
        if (isset($this->batchsize)) {
        	$statement->setBatchSize($this->batchsize);
        	unset($this->batchsize);
        }
        if (isset($this->count)) {
        	$statement->setCount($this->count);
        	unset($this->count);
        }
        if (isset($this->limit)) {
        	$aql .= " LIMIT " . $this->limit;
        	unset($this->limit);
        }
        
     	$aql .= " RETURN a";
     	$statement->setQuery($aql);
     	return $statement->execute();
     }
     
     /**
      * Gets the distance of vertex pairs of a graph
      *
      * This will throw if the list cannot be fetched from the server<br><br>
      *  
      * @see GraphHandler::getShortestPaths()  For parameter description see 'getShortestPaths'. 
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param mixed      $startVertexExample  - see functions introduction
      * @param mixed      $endVertexExample    - see functions introduction
      *
      * @param bool|array $options      - an array of optional parameters:
      *
      * @return Cursor
      */
     public function getDistanceTo($graph,
     	$startVertexExample = null,
     	$endVertexExample = null,
     	$options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'distanceTo';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "FOR a IN GRAPH_DISTANCE_TO(@graphName, @start, @end , @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('start', $startVertexExample);
     	$statement->bind('end', $endVertexExample);
     	$statement->bind('options', $options);
     	if (isset($options["batchSize"])) {
        	$this->setBatchsize($options["batchSize"]);
        	$statement->setBatchSize($options["batchSize"]);
        }
        if (isset($options["count"])) {
        	$this->setCount($options["count"]);
        	$statement->setCount($options["count"]);
        }
        if (isset($options["limit"])) {
        	$this->setLimit($options["limit"]);
        	$aql .= " LIMIT " . $options["limit"];
        }
        if (isset($this->batchsize)) {
        	$statement->setBatchSize($this->batchsize);
        	unset($this->batchsize);
        }
        if (isset($this->count)) {
        	$statement->setCount($this->count);
        	unset($this->count);
        }
        if (isset($this->limit)) {
        	$aql .= " LIMIT " . $this->limit;
        	unset($this->limit);
        }
        
     	$aql .= " RETURN a";
     	$statement->setQuery($aql);
     	return $statement->execute();
     }
     
     /**
      * Get common neighboring vertices of 2 gicen vertices.
      *
      * This will throw if the list cannot be fetched from the server
      * 
      * This method returns the intersection of the result of 'getNeighborVertices.'
      * This method accepts a different set of options than 'getNeighborVertices.'<br><br>
      *
      * @see GraphHandler::getNeighborVertices()  This method returns the intersection of the result of 'getNeighborVertices.' 
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param mixed      $vertex1Example     - see 'getNeighborVertices'
      * @param mixed      $vertex2Example     - see 'getNeighborVertices'
      * @param array      $options1      - see '$options2'. 
      * @param array      $options2      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - "any" or "inbound" (or "in") or "outbound" (or "out") . Default value is "any".</li>
      * <li><b>edgeExamples</b> - A filter example for the edges, see $vertex1Example</li>
      * <li><b>neighborExamples</b> - A filter example for the neighbors, see $vertex1Example</li>
      *	<li><b>edgeCollectionRestriction</b>  - One or multiple edge collection names. 
      * Only edges from these collections will be considered for the path.
      * <li><b>vertexCollectionRestriction</b> - One or multiple vertex collection names. 
      * Only vertices from these collections will be considered as start vertex of a path.
	  * <li><b>minDepth</b> -  Defines the minimal length of a path from an edge to a vertex 
	  * (default is 1, which means only the edges directly connected to a vertex would be returned).
	  * <li><b>maxDepth</b> - Defines the maximal length of a path from an edge to a vertex 
	  * (default is 1, which means only the edges directly connected to a vertex would be returned).
      * </ul>
      *
      * @return Cursor
      */
     public function getCommonNeighborVertices($graph, $vertex1Example = null, $vertex2Example = null, $options1 = array(),$options2 = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType('commonNeighbors');
     	$aql = "FOR a IN GRAPH_COMMON_NEIGHBORS(@graphName, @vertex1, @vertex2, @options1, @options2) ";
     	if (isset($options1['direction'])) {
     		if ($options1['direction'] === "in") {
     			$options1['direction'] = "inbound";
     		}
     		if ($options1['direction'] === "out") {
     			$options1['direction'] = "outbound";
     		}
     	}

     	if (isset($options2['direction'])) {
     		if ($options2['direction'] === "in") {
     			$options2['direction'] = "inbound";
     		}
     		if ($options2['direction'] === "out") {
     			$options2['direction'] = "outbound";
     		}
     	}
     	
     	$statement->bind('graphName', $graph);
     	$statement->bind('vertex1', $vertex1Example);
     	$statement->bind('vertex2', $vertex2Example);
     	$statement->bind('options1', $options1);
     	$statement->bind('options2', $options2);
        if (isset($this->batchsize)) {
        	$statement->setBatchSize($this->batchsize);
        	unset($this->batchsize);
        }
        if (isset($this->count)) {
        	$statement->setCount($this->count);
        	unset($this->count);
        }
        if (isset($this->limit)) {
        	$aql .= " LIMIT " . $this->limit;
        	unset($this->limit);
        }
        
     	$aql .= " RETURN a";
     	$statement->setQuery($aql);
     	return $statement->execute();
     
     }
     
     
     /**
      * Get vertices with common properties.
      *
      * This will throw if the list cannot be fetched from the server
      *
      * This method accepts multiple argument types for the <b>vertex examples</b>, these can be:
      * <li>a vertex id </li>
      * <li><b>null</b> select every vertex</li>
      * <li>an array containing key value pairs that must match a vertex</li>
      * <li>an array of arrays containing key value pairs that must match a vertex. 
      * This example means that you can define filters and combine them with "or".</li><br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param mixed      $vertex1Example     - see 'getNeighborVertices'
      * @param mixed      $vertex2Example     - see 'getNeighborVertices'
      * @param array      $options      - see 'options' description.
      * <ul><br>
      * <li><b>vertex1CollectionRestriction</b> - One or multiple vertex collection names.
      * Only vertices from these collections will be considered.
      * <li><b>vertex2CollectionRestriction</b> - One or multiple vertex collection names.
      * Only vertices from these collections will be considered.
      * <li><b>ignoreProperties</b> - One or multiple attributes of a document that should be ignored, either a string or an array.
      * </li>
      * </ul>
      *
      * @return Cursor
      */
     public function getCommonProperties($graph, $vertex1Example= null, $vertex2Example = null, $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType('commonProperties');
     	$aql = "FOR a IN GRAPH_COMMON_PROPERTIES(@graphName, @vertex1, @vertex2, @options) ";
     	
     	$statement->bind('graphName', $graph);
     	$statement->bind('vertex1', $vertex1Example);
     	$statement->bind('vertex2', $vertex2Example);
     	$statement->bind('options', $options);
     	if (isset($this->batchsize)) {
     		$statement->setBatchSize($this->batchsize);
     		unset($this->batchsize);
     	}
     	if (isset($this->count)) {
     		$statement->setCount($this->count);
     		unset($this->count);
     	}
     	if (isset($this->limit)) {
     		$aql .= " LIMIT " . $this->limit;
     		unset($this->limit);
     	}
     
     	$aql .= " RETURN a";
     	$statement->setQuery($aql);
     	return $statement->execute();
     	 
     }
     
     
     
     /**
      * Get the absolute <a href="http://en.wikipedia.org/wiki/Distance_%28graph_theory%29">eccentricity</a> of a graph.
      *
      * This will throw if the list cannot be fetched from the server
      * This does not support 'batchsize', 'limit' and 'count'.
      * This method accepts multiple argument types for the <b>vertex examples</b>, these can be:
      * <li>a vertex id </li>
      * <li><b>null</b> select every vertex</li>
      * <li>an array containing key value pairs that must match a vertex</li>
      * <li>an array of arrays containing key value pairs that must match a vertex.
      * This example means that you can define filters and combine them with "or".</li><br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param mixed      $vertexExample  - see functions introduction
      *
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      * <li><b>edgeCollectionRestriction</b>        - One or multiple edge
      *	collection names. Only edges from these collections will be considered for the path.<li>
      * <li><b>startVertexCollectionRestriction</b> - One or multiple vertex
      *	collection names. Only vertices from these collections will be considered as start vertex of a path.</li>
      * <li><b>endVertexCollectionRestriction</b> - One or multiple vertex
      *	collection names. Only vertices from these collections will be considered as end vertex of a path.</li>
      * <li><b>edgeExamples</b>  - A filter example for the edges in the shortest paths, see introduction.
      *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.
      * <li><b>weight</b>  - The name of the attribute of the edges containing the length as a string.
      * <li><b>defaultWeight</b>  - Only used with the option *weight*. If an edge does not have the attribute named as
      * defined in option *weight* this default.
      * </ul>
      *
      *
      * @return array
      */
     public function getAbsoluteEccentricity($graph,
     										$vertexExample = null,
     										$options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'figure';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "RETURN GRAPH_ABSOLUTE_ECCENTRICITY(@graphName, @vertex , @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('vertex', $vertexExample);
     	$statement->bind('options', $options);
     	
     	$statement->setQuery($aql);
     	return $statement->execute()->getAll();
     }
     
     
     /**
      * Get the <a href="http://en.wikipedia.org/wiki/Distance_%28graph_theory%29">eccentricity</a> of a graph.
      *
      * This will throw if the list cannot be fetched from the server
      * This does not support 'batchsize', 'limit' and 'count'.<br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - The direction of the edges as a string.</li>
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.</li>
      * <li><b>weight</b>  - The name of the attribute of the edges containing the length as a string.</li>
      * <li><b>defaultWeight</b>  - Only used with the option *weight*. If an edge does not have the attribute named as
      * defined in option *weight* this default.</li>
      * </ul>
      *
      *
      * @return array 
      */
     public function getEccentricity($graph,
     	$options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'figure';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "RETURN GRAPH_ECCENTRICITY(@graphName, @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('options', $options);
     
     	$statement->setQuery($aql);
     	return $statement->execute()->getAll();
     }
     
     /**
      * Get the absolute <a href="http://en.wikipedia.org/wiki/Centrality#Closeness_centrality">closeness</a> of a graph.
      *
      * This will throw if the list cannot be fetched from the server
      * This does not support 'batchsize', 'limit' and 'count'.
      * This method accepts multiple argument types for the <b>vertex examples</b>, these can be:
      * <li>a vertex id </li>
      * <li><b>null</b> select every vertex</li>
      * <li>an array containing key value pairs that must match a vertex</li>
      * <li>an array of arrays containing key value pairs that must match a vertex.
      * This example means that you can define filters and combine them with "or".</li><br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param mixed      $vertexExample  - see functions introduction
      *
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      * <li><b>edgeCollectionRestriction</b>  - One or multiple edge
      *	collection names. Only edges from these collections will be considered for the path.<li>
      * <li><b>startVertexCollectionRestriction</b> - One or multiple vertex
      *	collection names. Only vertices from these collections will be considered as start vertex of a path.</li>
      * <li><b>endVertexCollectionRestriction</b> - One or multiple vertex
      *	collection names. Only vertices from these collections will be considered as end vertex of a path.</li>
      * <li><b>edgeExamples</b>  - A filter example for the edges in the shortest paths, see introduction.
      *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.
      * <li><b>weight</b> - The name of the attribute of the edges containing the length as a string.
      * <li><b>defaultWeight</b>  - Only used with the option *weight*. If an edge does not have the attribute named as
      * defined in option *weight* this default.
      * </ul>
      *
      *
      * @return array
      */
     public function getAbsoluteCloseness($graph,
     $vertexExample = null,
     $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'figure';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "RETURN GRAPH_ABSOLUTE_CLOSENESS(@graphName, @vertex , @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('vertex', $vertexExample);
     	$statement->bind('options', $options);
     
     	$statement->setQuery($aql);
     	return $statement->execute()->getAll();
     }
      
      
     /**
      * Get the <a href="http://en.wikipedia.org/wiki/Centrality#Closeness_centrality">closeness</a> of a graph.
      *
      * This will throw if the list cannot be fetched from the server
      * This does not support 'batchsize', 'limit' and 'count'.<br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.
      * <li><b>weight</b>  - The name of the attribute of the edges containing the length as a string.
      * <li><b>defaultWeight</b>  - Only used with the option *weight*. If an edge does not have the attribute named as
      * defined in option *weight* this default.
      * </ul>
      *
      *
      * @return array
      */
     public function getCloseness($graph,
     $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'figure';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "RETURN GRAPH_CLOSENESS(@graphName, @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('options', $options);
     	 
     	$statement->setQuery($aql);
     	return $statement->execute()->getAll();
     }
     
     
     /**
      * Get the absolute <a href="http://en.wikipedia.org/wiki/Betweenness_centrality">betweenness</a> of a graph.
      *
      * This will throw if the list cannot be fetched from the server
      * This does not support 'batchsize', 'limit' and 'count'.<br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      *
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.
      * <li><b>weight</b> - The name of the attribute of the edges containing the length as a string.
      * <li><b>defaultWeight</b>  - Only used with the option *weight*. If an edge does not have the attribute named as
      * defined in option *weight* this default.
      * </ul>
      *
      *
      * @return array
      */
     public function getAbsoluteBetweenness($graph,
     $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'figure';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "RETURN GRAPH_ABSOLUTE_BETWEENNESS(@graphName, @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('options', $options);
     	 
     	$statement->setQuery($aql);
     	return $statement->execute()->getAll();
     }
     
     
     /**
      * Get the <a href="http://en.wikipedia.org/wiki/Betweenness_centrality">betweenness</a> of a graph.
      *
      * This will throw if the list cannot be fetched from the server
      * This does not support 'batchsize', 'limit' and 'count'.<br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.
      * <li><b>weight</b> - The name of the attribute of the edges containing the length as a string.
      * <li><b>defaultWeight</b> - Only used with the option *weight*. If an edge does not have the attribute named as
      * defined in option *weight* this default.
      * </ul>
      *
      *
      * @return array
      */
     public function getBetweenness($graph,
     $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'figure';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "RETURN GRAPH_BETWEENNESS(@graphName, @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('options', $options);
     	 
     	$statement->setQuery($aql);
     	return $statement->execute()->getAll();
     }
     
     /**
      * Get the <a href="http://en.wikipedia.org/wiki/Eccentricity_%28graph_theory%29">radius</a> of a graph.
      *
      * This will throw if the list cannot be fetched from the server
      * This does not support 'batchsize', 'limit' and 'count'.<br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.
      * <li><b>weight</b> - The name of the attribute of the edges containing the length as a string.
      * <li><b>defaultWeight</b>  - Only used with the option *weight*. If an edge does not have the attribute named as
      * defined in option *weight* this default.
      * </ul>
      *
      *
      * @return double
      */
     public function getRadius($graph,
     $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'figure';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "RETURN GRAPH_RADIUS(@graphName, @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('options', $options);
     	 
     	$statement->setQuery($aql);
     	$a = $statement->execute()->getAll();
     	return $a[0];
     }
     
     /**
      * Get the <a href="http://en.wikipedia.org/wiki/Eccentricity_%28graph_theory%29">diameter</a> of a graph.
      *
      * This will throw if the list cannot be fetched from the server
      * This does not support 'batchsize', 'limit' and 'count'.<br><br>
      *
      * @throws Exception
      *
      * @param mixed      $graph        - graph name as a string or instance of Graph
      *
      * @param bool|array $options      - an array of optional parameters:
      * <ul><br>
      * <li><b>direction</b> - The direction of the edges as a string.
      * Possible values are *outbound*, *inbound* and *any* (default).</li>
      *	<li><b>algorithm</b> - The algorithm to calculate the shortest paths. If both start and end vertex examples are empty
      * <a href="http://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm">Floyd-Warshall</a> is used, otherwise the
      * default is <a "href=http://en.wikipedia.org/wiki/Dijkstra's_algorithm">Dijkstra</a>.
      * <li><b>weight</b> - The name of the attribute of the edges containing the length as a string.
      * <li><b>defaultWeight</b> - Only used with the option *weight*. If an edge does not have the attribute named as
      * defined in option *weight* this default.
      * </ul>
      *
      *
      * @return double
      */
     public function getDiameter($graph,
     $options = array())
     {
     	if ($graph instanceof Graph) {
     		$graph = $graph->getKey();
     	}
     	 
     	$options['objectType'] = 'figure';
     	$data                  = array_merge($options, $this->getCursorOptions($options));
     	$statement = new Statement($this->getConnection(), array(
     			"query"     => ''
     	));
     	$statement->setResultType($options['objectType']);
     	$aql = "RETURN GRAPH_DIAMETER(@graphName, @options) ";
     	 
     	if (isset($options['direction'])) {
     		if ($options['direction'] === "in") {
     			$options['direction'] = "inbound";
     		}
     		if ($options['direction'] === "out") {
     			$options['direction'] = "outbound";
     		}
     	}
     	 
     	$statement->bind('graphName', $graph);
     	$statement->bind('options', $options);
     	 
     	$statement->setQuery($aql);
     	$a = $statement->execute()->getAll();
     	return $a[0];
     }
      
}
