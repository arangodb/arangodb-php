<?php

/**
 * ArangoDB PHP client: document handler
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @author    Frank Mayer
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * A handler that manages edges
 *
 * An edge-document handler that fetches edges from the server and
 * persists them on the server. It does so by issuing the
 * appropriate HTTP requests to the server.<br>
 *
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     1.0
 */
class EdgeHandler extends
    DocumentHandler
{
    /**
     * documents array index
     */
    const ENTRY_DOCUMENTS = 'edge';
    
    /**
     * edges array index
     */
    const ENTRY_EDGES = 'edges';

    /**
     * collection parameter
     */
    const OPTION_COLLECTION = 'collection';

    /**
     * example parameter
     */
    const OPTION_EXAMPLE = 'example';

    /**
     * example parameter
     */
    const OPTION_FROM = 'from';

    /**
     * example parameter
     */
    const OPTION_TO = 'to';

    /**
     * vertex parameter
     */
    const OPTION_VERTEX = 'vertex';

    /**
     * direction parameter
     */
    const OPTION_DIRECTION = 'direction';

    /**
     * Intermediate function to call the createFromArray function from the right context
     *
     * @param $data
     * @param $options
     *
     * @return Edge
     */
    public function createFromArrayWithContext($data, $options)
    {
        return Edge::createFromArray($data, $options);
    }


    /**
     * Just throw an exception if add() is called on edges.
     *
     * @internal
     * @throws Exception
     *
     * @param mixed    $collection   - collection id as string or number
     * @param Edge     $document     - the document to be added
     * @param bool     $create       - create the collection if it does not yet exist
     *
     * @return mixed|void
     */
    public function add($collection, Document $document, $create = null)
    {
        throw new ClientException("Edges don't have an add() method. Please use saveEdge()");
    }


    /**
     * Just throw an exception if save() is called on edges.
     *
     * @internal
     * @throws Exception
     *
     * @param mixed    $collection   - collection id as string or number
     * @param Edge     $document     - the document to be added
     * @param bool     $create       - create the collection if it does not yet exist
     *
     * @return mixed|void
     */
    public function save($collection, $document, $create = null)
    {
        throw new ClientException("Edges don't have a save() method. Please use saveEdge()");
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
     * @param mixed      $collection   - collection id as string or number
     * @param mixed      $from         - from vertex
     * @param mixed      $to           - to vertex
     * @param mixed      $document     - the edge-document to be added, can be passed as an object or an array
     * @param bool|array $options      - optional, prior to v1.2.0 this was a boolean value for create. Since v1.0.0 it's an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>'create' - create the collection if it does not yet exist.</li>
     *                                 <li>'waitForSync' -  if set to true, then all removal operations will instantly be synchronised to disk.<br>
     *                                 If this is not specified, then the collection's default sync behavior will be applied.</li>
     *                                 </p>
     *
     * @return mixed - id of document created
     * @since 1.0
     */
    public function saveEdge($collection, $from, $to, $document, $options = array())
    {
        $collection = $this->makeCollection($collection);
        
        if (is_array($document)) {
            $document = Edge::createFromArray($document);
        }
        $document->setFrom($from);
        $document->setTo($to);
        
        $params = $this->validateAndIncludeOldSingleParameterInParams(
                       $options,
                       array(self::OPTION_COLLECTION => $collection),
                       ConnectionOptions::OPTION_CREATE
        );

        $params = $this->includeOptionsInParams(
                       $params,
                       array(),
                       array(
                            'collection'  => $collection,
                            'waitForSync' => ConnectionOptions::OPTION_WAIT_SYNC,
                            'silent'      => false
                       )
        );
        
        $this->createCollectionIfOptions($collection, $params);

        $data = $document->getAllForInsertUpdate();
        
        $url      = UrlHelper::appendParamsUrl(Urls::URL_EDGE, $params);
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($data));

        $location = $response->getLocationHeader();
        if (!$location) {
            throw new ClientException('Did not find location header in server response');
        }

        $json = $response->getJson();
        $id   = UrlHelper::getDocumentIdFromLocation($location);

        $document->setInternalId($json[Edge::ENTRY_ID]);
        $document->setRevision($json[Edge::ENTRY_REV]);

        if ($id != $document->getId()) {
            throw new ClientException('Got an invalid response from the server');
        }

        $document->setIsNew(false);

        return $document->getId();
    }


    /**
     * Get connected edges for a given vertex
     *
     * @throws Exception
     *
     * @param mixed  $collection   - edge-collection id as string or number
     * @param mixed  $vertexHandle - the vertex involved
     * @param string $direction    - optional defaults to 'any'. Other possible Values 'in' & 'out'
     * @param array $options       - optional, array of options
     *                               <p>Options are :
     *                               <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                               <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                               </p>
     *
     * @return array - array of connected edges
     * @since 1.0
     */
    public function edges($collection, $vertexHandle, $direction = 'any', array $options = array())
    {
        $collection = $this->makeCollection($collection);

        $params   = array(
            self::OPTION_VERTEX     => $vertexHandle,
            self::OPTION_DIRECTION  => $direction
        );
        $url      = UrlHelper::appendParamsUrl(Urls::URL_EDGES . '/' . urlencode($collection), $params);
        $response = $this->getConnection()->get($url);
        $json     = $response->getJson();
       
        $edges = array();
        foreach ($json[self::ENTRY_EDGES] as $data) {
           $edges[] = $this->createFromArrayWithContext($data, $options);
        }

        return $edges;
    }


    /**
     * Get connected inbound edges for a given vertex
     *
     * @throws Exception
     *
     * @param mixed $collection   - edge-collection id as string or number
     * @param mixed $vertexHandle - the vertex involved
     *
     * @return array - array of connected edges
     */
    public function inEdges($collection, $vertexHandle)
    {
        return $this->edges($collection, $vertexHandle, 'in');
    }

    /**
     * Get connected outbound edges for a given vertex
     *
     * @throws Exception
     *
     * @param mixed $collection   - edge-collection id as string or number
     * @param mixed $vertexHandle - the vertex involved
     *
     * @return array - array of connected edges
     */
    public function outEdges($collection, $vertexHandle)
    {
        return $this->edges($collection, $vertexHandle, 'out');
    }

}
