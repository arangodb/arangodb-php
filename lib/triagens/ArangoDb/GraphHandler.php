<?php

/**
 * ArangoDB PHP client: graph handler
 *
 * @package   ArangoDbPhpClient
 * @author    Jan Steemann
 * @author    Frank Mayer
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * A graph handler that manages graphs.
 * It does so by issuing the
 * appropriate HTTP requests to the server.
 *
 * @package ArangoDbPhpClient
 */
class GraphHandler extends
    DocumentHandler
{

    /**
     * documents array index
     */
    const ENTRY_GRAPH = 'graph';

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
     * Just throw an exception if add() is called on edges.
     *
     * @internal
     * @throws ClientException
     *
     * @param mixed    $graphName    - graph name as string or number
     * @param Document $document     - the document to be added
     * @param bool     $create       - create the collection if it does not yet exist
     */

    public function add($graphName, Document $document, $create = null)
    {
        throw new ClientException("Graphs don't have an add() method. Please use createGraph()");
    }

    /**
     * Just throw an exception if save() is called on edges.
     *
     * @internal
     * @throws ClientException
     *
     * @param mixed    $graphName    - graph name as string or number
     * @param Document $document     - the document to be added
     * @param bool     $create       - create the collection if it does not yet exist
     */
    public function save($graphName, Document $document, $create = null)
    {
        throw new ClientException("Graphs don't have a save() method. Please use createGraph()");
    }


    /**
     * save an edge to an edge-collection
     *
     * This will save the edge to the collection and return the edges-document's id
     *
     * This will throw if the document cannot be saved
     *
     * @throws Exception
     *
     * @param Graph - $graph - The name of the graph to create
     *
     * @return mixed - id of document created
     * @since 1.2
     */
    public function createGraph(Graph $graph)
    {
        $params   = array(
            self::OPTION_KEY      => $graph->getKey(),
            self::OPTION_VERTICES => $graph->getVerticesCollection(),
            self::OPTION_EDGES    => $graph->getEdgesCollection()
        );
        $url      = UrlHelper::appendParamsUrl(Urls::URL_GRAPH, $params);
        $response = $this->getConnection()->post($url, $this->getConnection()->json_encode_wrapper($params));
        $json     = $response->getJson();

        $graph->setInternalId($json['graph'][Graph::ENTRY_ID]);
        $graph->setRevision($json['graph'][Graph::ENTRY_REV]);

        return $graph->getAll();
    }


    /**
     * Drop a graph and remove all its vertices and edges
     *
     * @throws Exception
     *
     * @param string $graph - graph name as a string
     *
     * @return bool - always true, will throw if there is an error
     */
    public function deleteGraph($graph)
    {

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, $graph);
        $this->getConnection()->delete($url);

        return true;
    }


    /**
     * Get a graph's properties
     *
     * @throws Exception
     *
     * @param string $graph - graph name as a string
     *
     * @return bool - Returns an array of attributes. Will throw if there is an error
     */
    public function properties($graph)
    {

        $url         = UrlHelper::buildUrl(Urls::URL_GRAPH, $graph);
        $result      = $this->getConnection()->get($url);
        $resultArray = $result->getJson();

        return $resultArray['graph'];
    }


    /**
     * save a document to a collection
     *
     * This will add the document to the collection and return the document's id
     *
     * This will throw if the document cannot be saved
     *
     * @throws Exception
     *
     * @param mixed    $graphName - the name of the graph
     * @param Document $document  - the document to be added
     *
     * @return mixed - id of document created
     * @since 1.2
     */
    public function saveVertex($graphName, Document $document)
    {
        $data = $document->getAll();
        $url  = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_VERTEX);

        $response = $this->getConnection()->post($url, $this->getConnection()->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];
        $id        = $vertex['_id'];
        @list(, $documentId) = explode('/', $id, 2);

        $document->setInternalId($vertex[Document::ENTRY_ID]);
        $document->setRevision($vertex[Document::ENTRY_REV]);

        if ($documentId != $document->getId()) {
            throw new ClientException('Got an invalid response from the server');
        }

        return $document->getId();
    }


    /**
     * Get a single vertex from a graph
     *
     * This will throw if the vertex cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $graphName  - collection id as a string or number
     * @param mixed $vertexId   - vertex identifier
     * @param array $options    - optional, array of options
     * <p>Options are :
     * <li>'includeInternals' - true to include the internal attributes. Defaults to false</li>
     * <li>'ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     * </p>
     *
     * @return Document - the vertex document fetched from the server
     */
    public function getVertex($graphName, $vertexId, array $options = array())
    {
        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_VERTEX, $vertexId);
        $response = $this->getConnection()->get($url);

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];

        return Document::createFromArray($vertex, $options);
    }


    /**
     * Replace an existing vertex in a graph, identified graph name and vertex id
     *
     * This will update the vertex on the server
     *
     * This will throw if the vertex cannot be Replaced
     *
     * If policy is set to error (locally or globally through the connectionoptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the to-be-replaced vertex is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed    $graphName    - graph name as string or number
     * @param mixed    $vertexId     - vertex id as string or number
     * @param Document $document     - vertex document to be updated
     * @param mixed    $options      - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     * <p>Options are :
     * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li>'waitForSync' - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     *
     * @return bool - always true, will throw if there is an error
     *
     * @since 1.2
     */
    public function ReplaceVertex($graphName, $vertexId, Document $document, $options = array())
    {
        // This preserves compatibility for the old policy parameter.
        $params   = array();
        $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_REPLACE_POLICY);
        $params = $this->includeOptionsInParams($options, $params, array(
                                                                        'waitForSync' => $this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC)
                                                                   ));

        $revision = $document->getRevision();
        if (!is_null($revision)) {
            $params[ConnectionOptions::OPTION_REVISION] = $revision;
        }

        $data = $document->getAll();
        $url  = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_VERTEX, $vertexId);

        $response = $this->getConnection()->PUT($url, $this->getConnection()->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $vertex    = $jsonArray['vertex'];
        $id        = $vertex['_id'];
        @list(, $documentId) = explode('/', $id, 2);

        $document->setInternalId($vertex[Document::ENTRY_ID]);
        $document->setRevision($vertex[Document::ENTRY_REV]);

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
     * If policy is set to error (locally or globally through the connectionoptions)
     * and the passed vertex-document has a _rev value set, the database will check
     * that the revision of the to-be-replaced document is the same as the one given.
     *
     * @throws Exception
     *
     * @param string   $graphName   - graph name as string
     * @param mixed    $vertexId    - vertex id as string or number
     * @param Document $document    - patch vertex-document which contains the attributes and values to be updated
     * @param mixed    $options     - optional, array of options (see below)
     * <p>Options are :
     * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     * <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function updateVertex($graphName, $vertexId, Document $document, $options = array())
    {
        // This preserves compatibility for the old policy parameter.
        $params   = array();
        $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_UPDATE_POLICY);
        $params = $this->includeOptionsInParams($options, $params, array(
                                                                        'waitForSync' => $this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC),
                                                                        'keepNull'    => true,
                                                                   ));

        $revision = $document->getRevision();
        if (!is_null($revision)) {
            $params[ConnectionOptions::OPTION_REVISION] = $revision;
        }

        $url    = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_VERTEX, $vertexId);
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->patch($url, $this->getConnection()->json_encode_wrapper($document->getAll()));

        return true;
    }


    /**
     * Remove a vertex from a graph, identified by the graph name and vertex id
     *
     * @throws Exception
     *
     * @param mixed  $graphName  - graph name as string or number
     * @param mixed  $vertexId - vertex id as string or number
     * @param  mixed $revision - optional revision of the vertex to be deleted
     * @param mixed  $options  - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     * <p>Options are :
     * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function removeVertex($graphName, $vertexId, $revision = null, $options = array())
    {
        // This preserves compatibility for the old policy parameter.
        $params   = array();
        $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_DELETE_POLICY);
        $params = $this->includeOptionsInParams($options, $params, array(
                                                                        'waitForSync' => $this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC),
                                                                        'keepNull'    => true,
                                                                   ));

        if (!is_null($revision)) {
            $params[ConnectionOptions::OPTION_REVISION] = $revision;
        }

        $url    = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_VERTEX, $vertexId);
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->delete($url);

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
     * @param mixed    $graphName  - graph name as string or number
     * @param mixed    $from     - from vertex
     * @param mixed    $to       - to vertex
     * @param mixed    $label        - (optional) label for the edge
     * @param Edge $document - the edge-document to be added
     *
     * @return mixed - id of edge created
     * @since 1.2
     */
    public function saveEdge($graphName, $from, $to, $label = null, Edge $document)
    {
        if (!is_null($label)) {
            $document->set('$label', $label);
        }
        $document->setFrom($from);
        $document->setTo($to);
        $data                 = $document->getAll();
        $data[self::KEY_FROM] = $from;
        $data[self::KEY_TO]   = $to;

        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_EDGE);
        $response = $this->getConnection()->post($url, $this->getConnection()->json_encode_wrapper($data));

        //        $location = $response->getLocationHeader();
        //        if (!$location) {
        //            throw new ClientException('Did not find location header in server response');
        //        }


        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];
        $id        = $edge['_id'];
        @list(, $documentId) = explode('/', $id, 2);

        $document->setInternalId($edge[Document::ENTRY_ID]);
        $document->setRevision($edge[Document::ENTRY_REV]);

        if ($documentId != $document->getId()) {
            throw new ClientException('Got an invalid response from the server');
        }

        return $document->getId();
    }


    /**
     * Get a single edge from a graph
     *
     * This will throw if the edge cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $graphName  - collection id as a string or number
     * @param mixed $edgeId     - edge identifier
     * @param array $options    - optional, array of options
     * <p>Options are :
     * <li>'includeInternals' - true to include the internal attributes. Defaults to false</li>
     * <li>'ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     * </p>
     *
     * @return Document - the edge document fetched from the server
     */
    public function getEdge($graphName, $edgeId, array $options = array())
    {
        $url      = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_EDGE, $edgeId);
        $response = $this->getConnection()->get($url);

        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];

        return Edge::createFromArray($edge, $options);
    }


    /**
     * Replace an existing edge in a graph, identified graph name and edge id
     *
     * This will replace the edge on the server
     *
     * This will throw if the edge cannot be Replaced
     *
     * If policy is set to error (locally or globally through the connectionoptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the to-be-replaced edge is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed    $graphName     - graph name as string or number
     * @param mixed    $edgeId        - edge id as string or number
     * @param mixed    $label        - (optional) label for the edge
     * @param Edge $document      - edge document to be updated
     * @param mixed    $options      - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     * <p>Options are :
     * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li>'waitForSync' - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     *
     * @return bool - always true, will throw if there is an error
     *
     * @since 1.2
     */
    public function ReplaceEdge($graphName, $edgeId, $label, Edge $document, $options = array())
    {
        // This preserves compatibility for the old policy parameter.
        $params   = array();
        $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_REPLACE_POLICY);
        $params = $this->includeOptionsInParams($options, $params, array(
                                                                        'waitForSync' => $this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC)
                                                                   ));

        $revision = $document->getRevision();
        if (!is_null($revision)) {
            $params[ConnectionOptions::OPTION_REVISION] = $revision;
        }

        $data = $document->getAll();
        if (!is_null($label)) {
            $document->set('$label', $label);
        }
        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_EDGE, $edgeId);

        $response = $this->getConnection()->PUT($url, $this->getConnection()->json_encode_wrapper($data));

        $jsonArray = $response->getJson();
        $edge      = $jsonArray['edge'];
        $id        = $edge['_id'];
        @list(, $documentId) = explode('/', $id, 2);

        $document->setInternalId($edge[Document::ENTRY_ID]);
        $document->setRevision($edge[Document::ENTRY_REV]);

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
     * If policy is set to error (locally or globally through the connectionoptions)
     * and the passed edge-document has a _rev value set, the database will check
     * that the revision of the to-be-replaced document is the same as the one given.
     *
     * @throws Exception
     *
     * @param string   $graphName - graph name as string
     * @param mixed    $edgeId    - edge id as string or number
     * @param mixed    $label        - (optional) label for the edge
     * @param Edge $document      - patch edge-document which contains the attributes and values to be updated
     * @param mixed    $options   - optional, array of options (see below)
     * <p>Options are :
     * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     * <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function updateEdge($graphName, $edgeId, $label, Edge $document, $options = array())
    {
        // This preserves compatibility for the old policy parameter.
        $params   = array();
        $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_UPDATE_POLICY);
        $params = $this->includeOptionsInParams($options, $params, array(
                                                                        'waitForSync' => $this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC),
                                                                        'keepNull'    => true,
                                                                   ));        $policy   = null;

        $revision = $document->getRevision();
        if (!is_null($revision)) {
            $params[ConnectionOptions::OPTION_REVISION] = $revision;
        }

        if (!is_null($label)) {
            $document->set('$label', $label);
        }

        $url    = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_EDGE, $edgeId);
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->patch($url, $this->getConnection()->json_encode_wrapper($document->getAll()));

        return true;
    }


    /**
     * Remove a edge from a graph, identified by the graph name and edge id
     *
     * @throws Exception
     *
     * @param mixed  $graphName   - graph name as string or number
     * @param mixed  $edgeId    - edge id as string or number
     * @param  mixed $revision  - optional revision of the edge to be deleted
     * @param mixed  $options   - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     * <p>Options are :
     * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     * <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     * </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function removeEdge($graphName, $edgeId, $revision = null, $options = array())
    {
        // This preserves compatibility for the old policy parameter.
        $params   = array();
        $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_DELETE_POLICY);
        $params = $this->includeOptionsInParams($options, $params, array(
                                                                        'waitForSync' => $this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC),
                                                                        'keepNull'    => true,
                                                                   ));
        if (!is_null($revision)) {
            $params[ConnectionOptions::OPTION_REVISION] = $revision;
        }

        $url    = UrlHelper::buildUrl(Urls::URL_GRAPH, $graphName, Urls::URLPART_EDGE, $edgeId);
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->delete($url);

        return true;
    }
}