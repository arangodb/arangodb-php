<?php

/**
 * ArangoDB PHP client: graph handler
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @author    Frank Mayer
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
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
     * example parameter
     */
    const KEY_FROM = '_from';

    /**
     * example parameter
     */
    const KEY_TO = '_to';


    /**
     * Create a graph
     *
     * This will create a graph using the given graph object and return an array of the created graph object's attributes.
     *
     * This will throw if the graph cannot be created
     *
     * @throws Exception
     *
     * @param Graph - $graph - The graph object which holds the information of the graph to be created
     *
     * @return array - an array of the created graph object's attributes.
     * @since   1.2
     */
    public function createGraph(Graph $graph)
    {
        $params   = array(
            self::OPTION_KEY      => $graph->getKey(),
            self::OPTION_VERTICES => $graph->getVerticesCollection(),
            self::OPTION_EDGES    => $graph->getEdgesCollection()
        );
        $url      = UrlHelper::appendParamsUrl(Urls::URL_GRAPH, $params);
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($params));
        $json     = $response->getJson();

        $graph->setInternalId($json['graph'][Graph::ENTRY_ID]);
        $graph->setRevision($json['graph'][Graph::ENTRY_REV]);

        return $graph->getAll();
    }


    /**
     * Get a graph
     *
     * This will get a graph.
     *
     * This will throw if the graph cannot be retrieved.
     *
     * @throws Exception
     *
     * @param String $graph   - The name of the graph
     * @param array  $options - Options to pass to the method
     *
     * @return Graph - A graph object representing the graph
     * @since   1.2
     */
    public function getGraph($graph, array $options = array())
    {
        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph));
        $response = $this->getConnection()->get($url);
        $data     = $response->getJson();

        if ($data['error']) {
            return false;
        }

        $options['_isNew'] = false;

        return Graph::createFromArray($data['graph'], $options);
    }


    /**
     * Drop a graph and remove all its vertices and edges, also drops vertex and edge collections
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function dropGraph($graph)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph));
        $this->getConnection()->delete($url);

        return true;
    }


    /**
     * Get a graph's properties
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

        $url         = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph));
        $result      = $this->getConnection()->get($url);
        $resultArray = $result->getJson();

        return $resultArray['graph'];
    }


    /**
     * save a vertex to a graph
     *
     * This will add the vertex-document to the graph and return the vertex id
     *
     * This will throw if the vertex cannot be saved
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param mixed $document  - the vertex to be added, can be passed as a vertex object or an array
     *
     * @return mixed - id of vertex created
     * @since 1.2
     */
    public function saveVertex($graph, $document)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        if (is_array($document)) {
            $document = Vertex::createFromArray($document);
        }
        $data = $document->getAll();
        $url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX));

        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];
        $id        = $vertex['_id'];
        @list(, $documentId) = explode('/', $id, 2);

        $document->setInternalId($vertex[Vertex::ENTRY_ID]);
        $document->setRevision($vertex[Vertex::ENTRY_REV]);

        if ($documentId != $document->getId()) {
            throw new ClientException('Got an invalid response from the server');
        }

        $document->setIsNew(false);

        return $document->getId();
    }


    /**
     * Get a single vertex from a graph
     *
     * This will throw if the vertex cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $graph - graph name as a string or instance of Graph
     * @param mixed $vertexId  - the vertex identifier
     * @param array $options   - optional, an array of options
     *                          <p>Options are :
     *                          <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                          <li>'includeInternals' - Deprecated, please use '_includeInternals'.</li>
     *                          <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                          <li>'ignoreHiddenAttributes' - Deprecated, please use '_ignoreHiddenAttributes'.</li>
     *                          </p>
     *
     * @return Document - the vertex document fetched from the server
     * @since 1.2
     */
    public function getVertex($graph, $vertexId, array $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $vertexId));
        $response = $this->getConnection()->get($url);

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];

        $options['_isNew'] = false;

        return Vertex::createFromArray($vertex, $options);
    }


    /**
     * Replace an existing vertex in a graph, identified graph name and vertex id
     *
     * This will update the vertex on the server
     *
     * This will throw if the vertex cannot be Replaced
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the to-be-replaced vertex is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed    $graph     - graph name as a string or instance of Graph
     * @param mixed    $vertexId  - the vertex id as string or number
     * @param Document $document  - the vertex-document to be updated
     * @param mixed    $options   - optional, an array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                            <p>Options are :]
     *                            <li>'revision' - revision for conditional updates ('some-revision-id' [use the passed in revision id], false or true [use document's revision])</li>
     *                            <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                            <li>'waitForSync' - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                            </p>
     *
     * @return bool - always true, will throw if there is an error
     *
     * @since 1.2
     */
    public function replaceVertex($graph, $vertexId, Document $document, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
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
        $url  = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $vertexId));
        $url  = UrlHelper::appendParamsUrl($url, $params);

        $response = $this->getConnection()->PUT($url, $this->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];
        $id        = $vertex['_id'];
        @list(, $documentId) = explode('/', $id, 2);

        $document->setInternalId($vertex[Vertex::ENTRY_ID]);
        $document->setRevision($vertex[Vertex::ENTRY_REV]);

        if ($documentId != $document->getId()) {
            throw new ClientException('Got an invalid response from the server');
        }

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
     * that the revision of the to-be-replaced document is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed    $graph     - graph name as a string or instance of Graph
     * @param mixed    $vertexId  - the vertex id as string or number
     * @param Document $document  - the patch vertex-document which contains the attributes and values to be updated
     * @param mixed    $options   - optional, an array of options (see below)
     *                            <p>Options are :
     *                            <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                            <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     *                            <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                            </p>
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function updateVertex($graph, $vertexId, Document $document, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
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

        $url    = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $vertexId));
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->patch($url, $this->json_encode_wrapper($document->getAll()));
        $json   = $result->getJson();
        $vertex = $json['vertex'];
        $document->setRevision($vertex[Vertex::ENTRY_REV]);

        return true;
    }


    /**
     * Remove a vertex from a graph, identified by the graph name and vertex id
     *
     * @throws Exception
     *
     * @param mixed  $graph     - graph name as a string or instance of Graph
     * @param mixed  $vertexId  - the vertex id as string or number
     * @param mixed  $revision  - optional, the revision of the vertex to be deleted
     * @param mixed  $options   - optional, an array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                          <p>Options are :
     *                          <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                          <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                          </p>
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function removeVertex($graph, $vertexId, $revision = null, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
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

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTEX, $vertexId));
        $url = UrlHelper::appendParamsUrl($url, $params);
        $this->getConnection()->delete($url);

        return true;
    }


    /**
     * save an edge to a graph
     *
     * This will save the edge to the graph and return the edges-document's id
     *
     * This will throw if the edge cannot be saved
     *
     * @throws Exception
     *
     * @param mixed $graph     - graph name as a string or instance of Graph
     * @param mixed $from      - the 'from' vertex
     * @param mixed $to        - the 'to' vertex
     * @param mixed $label     - (optional) a label for the edge
     * @param mixed $document  - the edge-document to be added, can be passed as an edge object or an array
     *
     * @return mixed - id of edge created
     * @since 1.2
     */
    public function saveEdge($graph, $from, $to, $label = null, $document)
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
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

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE));
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];
        $id        = $edge['_id'];
        @list(, $documentId) = explode('/', $id, 2);

        $document->setInternalId($edge[Edge::ENTRY_ID]);
        $document->setRevision($edge[Edge::ENTRY_REV]);

        if ($documentId != $document->getId()) {
            throw new ClientException('Got an invalid response from the server');
        }

        $document->setIsNew(false);

        return $document->getId();
    }


    /**
     * Get a single edge from a graph
     *
     * This will throw if the edge cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $graph     - graph name as a string or instance of Graph
     * @param mixed $edgeId    - edge identifier
     * @param array $options   - optional, array of options
     *                         <p>Options are :
     *                         <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                         <li>'includeInternals' - Deprecated, please use '_includeInternals'.</li>
     *                         <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                         <li>'ignoreHiddenAttributes' - Deprecated, please use '_ignoreHiddenAttributes'.</li>
     *                         </p>
     *
     * @return Document - the edge document fetched from the server
     * @since 1.2
     */
    public function getEdge($graph, $edgeId, array $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $edgeId));
        $response = $this->getConnection()->get($url);

        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];

        $options['_isNew'] = false;

        return Edge::createFromArray($edge, $options);
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
     * that the revision of the to-be-replaced edge is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed $graph     - graph name as a string or instance of Graph
     * @param mixed $edgeId    - edge id as string or number
     * @param mixed $label     - label for the edge or ''
     * @param Edge  $document  - edge document to be updated
     * @param mixed $options   - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                         <p>Options are :
     *                         <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                         <li>'waitForSync' - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                         </p>
     *
     * @return bool - always true, will throw if there is an error
     *
     * @since 1.2
     */
    public function replaceEdge($graph, $edgeId, $label, Edge $document, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
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

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $edgeId));
        $url = UrlHelper::appendParamsUrl($url, $params);

        $response = $this->getConnection()->PUT($url, $this->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];
        $id        = $edge['_id'];
        @list(, $documentId) = explode('/', $id, 2);

        $document->setInternalId($edge[Edge::ENTRY_ID]);
        $document->setRevision($edge[Edge::ENTRY_REV]);

        if ($documentId != $document->getId()) {
            throw new ClientException('Got an invalid response from the server');
        }

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
     * that the revision of the to-be-replaced document is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed  $graph     - graph name as a string or instance of Graph
     * @param mixed  $edgeId    - edge id as string or number
     * @param mixed  $label     - label for the edge or ''
     * @param Edge   $document  - patch edge-document which contains the attributes and values to be updated
     * @param mixed  $options   - optional, array of options (see below)
     *                          <p>Options are :
     *                          <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                          <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     *                          <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                          </p>
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function updateEdge($graph, $edgeId, $label, Edge $document, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
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

        $url    = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $edgeId));
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->patch($url, $this->json_encode_wrapper($document->getAll()));
        $json   = $result->getJson();
        $edge   = $json['edge'];
        $document->setRevision($edge[Edge::ENTRY_REV]);

        return true;
    }


    /**
     * Remove a edge from a graph, identified by the graph name and edge id
     *
     * @throws Exception
     *
     * @param mixed  $graph     - graph name as a string or instance of Graph
     * @param mixed  $edgeId    - edge id as string or number
     * @param mixed  $revision  - optional revision of the edge to be deleted
     * @param mixed  $options   - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                          <p>Options are :
     *                          <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                          <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                          </p>
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function removeEdge($graph, $edgeId, $revision = null, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
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

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGE, $edgeId));
        $url = UrlHelper::appendParamsUrl($url, $params);
        $this->getConnection()->delete($url);

        return true;
    }


    /**
     * Get neighboring vertices of a given vertex
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed      $graph        - graph name as a string or instance of Graph
     * @param mixed      $vertexId     - the vertex id
     * @param bool|array $options      - optional, prior to v1.0.0 this was a boolean value for sanitize, since v1.0.0 it's an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>'batchSize' - the batch size of the returned cursor</li>
     *                                 <li>'limit' - limit the result size by a give number</li>
     *                                 <li>'count' - return the total number of results  Defaults to false.</li>
     *                                 <li>'filter' - a optional filter</li>
     *                                 <p>Filter options are :<br>
     *                                 <li>'direction' - Filter for inbound (value "in") or outbound (value "out") neighbors. Default value is "any".</li>
     *                                 <li>'labels' - filter by an array of edge labels (empty array means no restriction).</li>
     *                                 <li>'properties' - filter neighbors by an array of edge properties</li>
     *                                 <p>Properties options are :<br>
     *                                 <li>'key' - Filter the result vertices by a key value pair.</li>
     *                                 <li>'value' -  The value of the key.</li>
     *                                 <li>'compare' - A comparison operator. (==, >, <, >=, <= )</li>
     *                                 </p>
     *                                 </p>
     *                                 <li>'_sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                 <li>'sanitize' - Deprecated, please use '_sanitize'.</li>
     *                                 <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                 <li>'hiddenAttributes' - Deprecated, please use '_hiddenAttributes'.</li>
     *                                 <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a resultset of documents, the getAll() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     *                                 </p>
     *                                 </li>
     *                                 </p>
     *
     * @return cursor - Returns a cursor containing the result
     */
    public function getNeighborVertices($graph, $vertexId, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $options['objectType'] = 'vertex';
        $data                  = array_merge($options, $this->getCursorOptions($options));

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTICES, $vertexId));
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Get connected edges of a given vertex
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed      $graph        - graph name as a string or instance of Graph
     * @param mixed      $vertexId     - the vertex id
     * @param bool|array $options      - optional, prior to v1.0.0 this was a boolean value for sanitize, since v1.0.0 it's an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>'batchSize' - the batch size of the returned cursor</li>
     *                                 <li>'limit' - limit the result size by a give number</li>
     *                                 <li>'count' - return the total number of results  Defaults to false.</li>
     *                                 <li>'filter' - a optional filter</li>
     *                                 <p>Filter options are :<br>
     *                                 <li>'direction' - Filter for inbound (value "in") or outbound (value "out") neighbors. Default value is "any".</li>
     *                                 <li>'labels' - filter by an array of edge labels (empty array means no restriction).</li>
     *                                 <li>'properties' - filter neighbors by an array of edge properties</li>
     *                                 <p>Properties options are :<br>
     *                                 <li>'key' - Filter the result vertices by a key value pair.</li>
     *                                 <li>'value' -  The value of the key.</li>
     *                                 <li>'compare' - A comparison operator. (==, >, <, >=, <= )</li>
     *                                 </p>
     *                                 </p>
     *                                 <li>'_sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                 <li>'sanitize' - Deprecated, please use '_sanitize'.</li>
     *                                 <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                 <li>'hiddenAttributes' - Deprecated, please use '_hiddenAttributes'.</li>
     *                                 <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a resultset of documents, the getAll() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     *                                 </p>
     *                                 </li>
     *                                 </p>
     *
     * @return cursor - Returns a cursor containing the result
     */
    public function getConnectedEdges($graph, $vertexId, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $options['objectType'] = 'edge';
        $data                  = array_merge($options, $this->getCursorOptions($options));

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGES, $vertexId));
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }

    /**
     * Get all vertices of a graph
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed      $graph        - graph name as a string or instance of Graph
     * @param bool|array $options      - optional, prior to v1.0.0 this was a boolean value for sanitize, since v1.0.0 it's an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>'batchSize' - the batch size of the returned cursor</li>
     *                                 <li>'limit' - limit the result size by a give number</li>
     *                                 <li>'count' - return the total number of results  Defaults to false.</li>
     *                                 <li>'filter' - a optional filter</li>
     *                                 <p>Filter options are :<br>
     *                                 <li>'properties' - filter neighbors by an array of edge properties</li>
     *                                 <p>Properties options are :<br>
     *                                 <li>'key' - Filter the result vertices by a key value pair.</li>
     *                                 <li>'value' -  The value of the key.</li>
     *                                 <li>'compare' - A comparison operator. (==, >, <, >=, <= )</li>
     *                                 </p>
     *                                 </p>
     *                                 <li>'_sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                 <li>'sanitize' - Deprecated, please use '_sanitize'.</li>
     *                                 <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                 <li>'hiddenAttributes' - Deprecated, please use '_hiddenAttributes'.</li>
     *                                 <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a resultset of documents, the getAll() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     *                                 </p>
     *                                 </li>
     *                                 </p>
     *
     * @return cursor - Returns a cursor containing the result
     */
    public function getVertices($graph, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $options['objectType'] = 'vertex';
        $data                  = array_merge($options, $this->getCursorOptions($options));

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_VERTICES));
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Get edges of a graph
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed      $graph        - graph name as a string or instance of Graph
     * @param bool|array $options      - optional, prior to v1.0.0 this was a boolean value for sanitize, since v1.0.0 it's an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>'batchSize' - the batch size of the returned cursor</li>
     *                                 <li>'limit' - limit the result size by a give number</li>
     *                                 <li>'count' - return the total number of results  Defaults to false.</li>
     *                                 <li>'filter' - a optional filter</li>
     *                                 <p>Filter options are :<br>
     *                                 <li>'properties' - filter neighbors by an array of edge properties</li>
     *                                 <li>'labels' - filter by an array of edge labels (empty array means no restriction).</li>
     *                                 <p>Properties options are :<br>
     *                                 <li>'key' - Filter the result vertices by a key value pair.</li>
     *                                 <li>'value' -  The value of the key.</li>
     *                                 <li>'compare' - A comparison operator. (==, >, <, >=, <= )</li>
     *                                 </p>
     *                                 </p>
     *                                 <li>'_sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                 <li>'sanitize' - Deprecated, please use '_sanitize'.</li>
     *                                 <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                 <li>'hiddenAttributes' - Deprecated, please use '_hiddenAttributes'.</li>
     *                                 <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a resultset of documents, the getAll() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     *                                 </p>
     *                                 </li>
     *                                 </p>
     *
     * @return cursor - Returns a cursor containing the result
     */
    public function getEdges($graph, $options = array())
    {
        if ($graph instanceof Graph) {
            $graph = $graph->getKey();
        }

        $options['objectType'] = 'edge';
        $data                  = array_merge($options, $this->getCursorOptions($options));

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, array($graph, Urls::URLPART_EDGES));
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }
}
