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
     * @param mixed    $collectionId - collection id as string or number
     * @param Document $document     - the document to be added
     * @param bool     $create       - create the collection if it does not yet exist
     *
     * @return mixed|void
     */
    public function add($collectionId, Document $document, $create = null)
    {
        throw new ClientException("Edges don't have an add() method. Please use saveEdge()");
    }


    /**
     * Just throw an exception if save() is called on edges.
     *
     * @internal
     * @throws Exception
     *
     * @param mixed    $collectionId - collection id as string or number
     * @param Document $document     - the document to be added
     * @param bool     $create       - create the collection if it does not yet exist
     *
     * @return mixed|void
     */
    public function save($collectionId, $document, $create = null)
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
     * @param mixed      $collectionId - collection id as string or number
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
    public function saveEdge($collectionId, $from, $to, $document, $options = array())
    {
        if (is_array($document)) {
            $document = Edge::createFromArray($document);
        }
        $document->setFrom($from);
        $document->setTo($to);
        $params = array(
            self::OPTION_COLLECTION => $collectionId,
            self::OPTION_FROM       => $document->getFrom(),
            self::OPTION_TO         => $document->getTo()
        );
        $params = $this->validateAndIncludeOldSingleParameterInParams(
                       $options,
                       $params,
                       ConnectionOptions::OPTION_CREATE
        );

        $params = $this->includeOptionsInParams(
                       $options,
                       $params,
                       array(
                            ConnectionOptions::OPTION_WAIT_SYNC => $this->getConnectionOption(
                                                                        ConnectionOptions::OPTION_WAIT_SYNC
                                ),
                       )
        );

        $data = $document->getAll();

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
     * Get edges for a given vertex
     *
     * @throws Exception
     *
     * @param mixed  $collectionId - edge-collection id as string or number
     * @param mixed  $vertexHandle - the vertex involved
     * @param string $direction    - optional defaults to 'any'. Other possible Values 'in' & 'out'
     *
     * @return array - array of cursors
     * @since 1.0
     */
    public function edges($collectionId, $vertexHandle, $direction = 'any')
    {

        $params   = array(
            self::OPTION_COLLECTION => $collectionId,
            self::OPTION_VERTEX     => $vertexHandle,
            self::OPTION_DIRECTION  => $direction
        );
        $url      = UrlHelper::appendParamsUrl(Urls::URL_EDGE, $params);
        $response = $this->getConnection()->get($url);
        $json     = $response->getJson();

        return $json;
    }


    /**
     * Get inbound edges for a given vertex
     *
     * @throws Exception
     *
     * @param mixed $collectionId - edge-collection id as string or number
     * @param mixed $vertexHandle - the vertex involved
     *
     * @return array - array of cursors
     */
    public function inEdges($collectionId, $vertexHandle)
    {
        return $this->edges($collectionId, $vertexHandle, 'in');
    }

    /**
     * Get outbound edges for a given vertex
     *
     * @throws Exception
     *
     * @param mixed $collectionId - edge-collection id as string or number
     * @param mixed $vertexHandle - the vertex involved
     *
     * @return array - array of cursors
     */
    public function outEdges($collectionId, $vertexHandle)
    {
        return $this->edges($collectionId, $vertexHandle, 'out');
    }

    /**
     * Get a single edge from a collection
     *
     * This will throw if the edge cannot be fetched from the server.
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     * @param mixed $edgeId   - document identifier
     * @param array $options      - optional, array of options
     *                            <p>Options are :
     *                            <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                            <li>'includeInternals' - Deprecated, please use '_includeInternals'.</li>
     *                            <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                            <li>'ignoreHiddenAttributes' - Deprecated, please use '_ignoreHiddenAttributes'.</li>
     *                            <li>'ifMatch' - boolean if given revision should match or not</li>
     *                            <li>'revision' - The document is returned if it matches/not matches revision.</li>
     *                            </p>
     *
     * @return edge - the edge fetched from the server
     */
    public function getById($collectionId, $edgeId, array $options = array())
    {
        $data = $this->getDocument(Urls::URL_EDGE, $collectionId, $edgeId, $options);
        $options['_isNew'] = false;

        return $this->createFromArrayWithContext($data, $options);
    }


    /**
     * Gets information about a single edge from a collection
     *
     * This will throw if the edge cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     * @param mixed $edgeId   - document identifier
     * @param ifMatch' -  boolean if given revision should match or not</li>
     * @param revision' - The edge is returned if it matches/not matches revision.</li>
     *
     * @return array - an array containing the complete header including the key httpCode.
     */
    public function  getHead($collectionId, $edgeId, $revision = null, $ifMatch = null)
    {
        return $this->head(Urls::URL_EDGE, $collectionId, $edgeId, $revision, $ifMatch);
    }

    /**
     * Remove an edge from a collection, identified by the collection id and edge id
     *
     * @throws |Exception
     *
     * @param mixed  $collectionId - collection id as string or number
     * @param mixed  $edgeId   - document id as string or number
     * @param  mixed $revision     - optional revision of the document to be deleted
     * @param mixed  $options      - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                             <p>Options are :
     *                             <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                             <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                             </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function removeById($collectionId, $edgeId, $revision = null, $options = array())
    {
        return $this->erase(Urls::URL_EDGE, $collectionId, $edgeId, $revision, $options);
    }

    /**
     * Replace an existing edge in a collection, identified by collection id and edge id
     *
     * This will update the edge on the server
     *
     * This will throw if the edge cannot be Replaced
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed edge has a _rev value set, the database will check
     * that the revision of the to-be-replaced edge is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed    $collectionId - collection id as string or number
     * @param mixed    $edgeId   - edge id as string or number
     * @param Document $edge     - edge to be updated
     * @param mixed    $options  - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                               <p>Options are :
     *                               <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                               <li>'waitForSync' - can be used to force synchronisation of the edge replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                               </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function replaceById($collectionId, $edgeId, Document $edge, $options = array())
    {
        return $this->put(Urls::URL_EDGE, $collectionId, $edgeId, $edge, $options);
    }


    /**
     * Update an existing edge in a collection, identified by collection id and edge id
     * Attention - The behavior of this method has changed since version 1.1
     *
     * This will update the edge on the server
     *
     * This will throw if the edge cannot be updated
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed edge has a _rev value set, the database will check
     * that the revision of the edge to-be-updated is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed    $collectionId - collection id as string or number
     * @param mixed    $edgeId   - edge id as string or number
     * @param Document $edge     - patch edge which contains the attributes and values to be updated
     * @param mixed    $options      - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                               <p>Options are :
     *                               <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                               <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     *                               <li>'waitForSync' - can be used to force synchronisation of the edge update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                               </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function updateById($collectionId, $edgeId, Document $edge, $options = array())
    {
        return $this->patch(Urls::URL_EDGE, $collectionId, $edgeId, $edge, $options);
    }

}
