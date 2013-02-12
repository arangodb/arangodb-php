<?php

/**
 * ArangoDB PHP client: document handler
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @author Frank Mayer
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * An edge-document handler that fetches edges from the server and
 * persists them on the server. It does so by issuing the 
 * appropriate HTTP requests to the server.
 *
 * @package ArangoDbPhpClient
 */
class GraphHandler extends DocumentHandler {

  /**
   * documents array index
   */
  const ENTRY_GRAPH   = 'graph';

  /**
   * vertex parameter
   */
  const OPTION_VERTICES    = 'vertices';

   /**
   * direction parameter
   */
  const OPTION_EDGES    = 'edges';

   /**
   * direction parameter
   */
  const OPTION_KEY    = '_key';

    /**
   * Just throw an exception if add() is called on edges.
   * 
   * @internal
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param Document $document - the document to be added
   * @param bool $create - create the collection if it does not yet exist
   */
  
  public function add($collectionId, Document $document, $create = NULL){
            throw new ClientException("Graphs don't have an add() method. Please use createGraph()");
      }
  /**
   * Just throw an exception if save() is called on edges.
   * 
   * @internal
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param Document $document - the document to be added
   * @param bool $create - create the collection if it does not yet exist
   */
  public function save($collectionId, Document $document, $create = NULL){
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
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $from - from vertex
   * @param mixed $to - to vertex
   * @param Document $document - the document to be added
   * @param bool $create - create the collection if it does not yet exist
   * @return mixed - id of document created
   * @since 1.0
   */
  public function createGraph(Graph $graph) {
    $params = array(self::OPTION_KEY => $graph->getKey(),
                    self::OPTION_VERTICES => $graph->getVerticesCollection(),
                    self::OPTION_EDGES => $graph->getEdgesCollection());
    $url = UrlHelper::appendParamsUrl(Urls::URL_GRAPH, $params);
    $response = $this->getConnection()->post($url, $this->getConnection()->json_encode_wrapper($params));
    $json = $response->getJson();

    $graph->setInternalId($json['graph'][Graph::ENTRY_ID]);
    $graph->setRevision($json['graph'][Graph::ENTRY_REV]);
   
    return $graph->getAll();
  }


    /**
     * Drop a graph and remove all its vertices and edges
     *
     * @throws Exception
     * @param string $graph - graph name as a string
     * @return bool - always true, will throw if there is an error
     */
    public function deleteGraph($graph) {

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, $graph);
        $result = $this->getConnection()->delete($url);
        $resultArray = $result->getJson();
        return true;
    }

   /**
     * Drop a graph and remove all its vertices and edges
     *
     * @throws Exception
     * @param string $graph - graph name as a string
     * @return bool - always true, will throw if there is an error
     */
    public function properties($graph) {

        $url = UrlHelper::buildUrl(Urls::URL_GRAPH, $graph);
        $result = $this->getConnection()->get($url);
        $resultArray = $result->getJson();
        return $resultArray['graph'];
    }




}