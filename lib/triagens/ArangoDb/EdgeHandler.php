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
class EdgeHandler extends DocumentHandler {
  /**
   * documents array index
   */
  const ENTRY_DOCUMENTS   = 'edge';
  
  /**
   * collection parameter
   */
  const OPTION_COLLECTION = 'collection';
  
  /**
   * example parameter
   */
  const OPTION_EXAMPLE    = 'example';
  
  /**
   * example parameter
   */
  const OPTION_FROM    = 'from';
  
  /**
   * example parameter
   */
  const OPTION_TO    = 'to';
  
  /**
   * vertex parameter
   */
  const OPTION_VERTEX    = 'vertex';
  
  /**
   * direction parameter
   */
  const OPTION_DIRECTION    = 'direction';
  

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
            throw new ClientException("Edges don't have an add() method. Please use saveEdge()");
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
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $from - from vertex
   * @param mixed $to - to vertex
   * @param Document $document - the document to be added
   * @param bool $create - create the collection if it does not yet exist
   * @return mixed - id of document created
   * @since 1.0
   */
  public function saveEdge($collectionId, $from, $to, Document $document, $create = NULL) {
    if ($create === NULL) {
      $create = $this->getConnection()->getOption(ConnectionOptions::OPTION_CREATE);
    }
    $document->setFrom($from);
    $document->setTo($to);
    $data = $document->getAll();
    $params = array(self::OPTION_COLLECTION => $collectionId, self::OPTION_FROM => $document->getFrom(), self::OPTION_TO => $document->getTo());
    $url = UrlHelper::appendParamsUrl(Urls::URL_EDGE, $params); 
    $response = $this->getConnection()->post($url, json_encode($data));

    $location = $response->getLocationHeader();
    if (!$location) {
      throw new ClientException('Did not find location header in server response');
    }

    $json = $response->getJson();
    $id = UrlHelper::getDocumentIdFromLocation($location);

    $document->setInternalId($json[Edge::ENTRY_ID]);
    $document->setRevision($json[Edge::ENTRY_REV]);
   
    if ($id != $document->getId()) {
      throw new ClientException('Got an invalid response from the server');
    }

    return $document->getId();
  }
  
  
  /**
   * Get edges for a given vertex
   *
   * @throws Exception
   * @param mixed $collectionId - edge-collection id as string or number
   * @param mixed $vertexHandle - the vertex involved
   * @param string $direction - optional defaults to 'any'. Other possible Values 'in' & 'out'
   * @return array - array of cursors
   * @since 1.0
   */
    public function edges($collectionId, $vertexHandle, $direction='any') {

        $params = array(self::OPTION_COLLECTION => $collectionId, self::OPTION_VERTEX => $vertexHandle, self::OPTION_DIRECTION => $direction);
        $url = UrlHelper::appendParamsUrl(Urls::URL_EDGE, $params); 
        $response = $this->getConnection()->get($url);
        $json = $response->getJson();
        return $json;                        
    }

  /**
   * Get inbound edges for a given vertex
   *
   * @throws Exception
   * @param mixed $collectionId - edge-collection id as string or number
   * @param mixed $vertexHandle - the vertex involved
   * @return array - array of cursors
   */
    public function inEdges($collectionId, $vertexHandle) {
       return $this->edges($collectionId,$vertexHandle,'in'); 
    }

  /**
   * Get outbound edges for a given vertex
   *
   * @throws Exception
   * @param mixed $collectionId - edge-collection id as string or number
   * @param mixed $vertexHandle - the vertex involved
   * @return array - array of cursors
   */
    public function outEdges($collectionId, $vertexHandle) {
       return $this->edges($collectionId,$vertexHandle,'out');
    }
    
    
    
    
    
  
}