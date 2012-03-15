<?php

/**
 * AvocadoDB PHP client: document handler
 * 
 * @modulegroup AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens;

/**
 * AvocadoDocumentHandler
 * 
 * A document handler that fetches documents from the server and
 * persists them on the server. It does so by issueing the 
 * appropriate HTTP requests to the server.
 */

class AvocadoDocumentHandler {
  private $_connection;

  const URL              = '/collection';
  const ENTRY_DOCUMENTS  = 'documents';
  
  /**
   * Construct a new document handler
   *
   * @return void
   */
  public function __construct(AvocadoConnection $connection) {
    $this->_connection = $connection;
  }
  
  /**
   * Get a single document from a collection
   *
   * @throws AvocadoException
   * @return AvocadoDocument
   */
  public function get($collectionId, $documentId) {
    $url = AvocadoUrlHelper::buildUrl(self::URL, $collectionId, $documentId);
    $response = $this->_connection->get($url);

    $data = $response->getJson();

    return AvocadoDocument::createFromArray($data);
  }
  
  /**
   * Get the list of all documents' ids from a collection
   *
   * @throws AvocadoException
   * @return array
   */
  public function getAllIds($collectionId) {
    $url = AvocadoUrlHelper::buildUrl(self::URL, $collectionId);
    $response = $this->_connection->get($url);
    
    $data = $response->getJson();
    if (!isset($data[self::ENTRY_DOCUMENTS])) {
      throw new AvocadoClientException('Got an invalid document list from the server');
    }

    $ids = array();
    foreach ($data[self::ENTRY_DOCUMENTS] as $location) {
      $ids[] = AvocadoUrlHelper::getDocumentIdFromLocation($location);
    }

    return $ids;
  }
  
  /**
   * Add a document to a collection
   * This will add the document to the collection and return the document's id
   *
   * @throws AvocadoException
   * @return string 
   */
  public function add($collectionId, AvocadoDocument $document) {
    $data = $document->getAll();
    $response = $this->_connection->post(AvocadoUrlHelper::buildUrl(self::URL, $collectionId), json_encode($data));

    $location = $response->getHeader('location');
    if (!$location) {
      throw new AvocadoClientException('Did not find location header in server response');
    }
    $id = AvocadoUrlHelper::getDocumentIdFromLocation($location);
    $document->setId($id);

    return $id;
  }

  /**
   * Update an existing document in a collection
   * This will update the document on the server
   *
   * @throws AvocadoException
   * @return bool 
   */
  public function update($collectionId, $documentId, AvocadoDocument $document) {
    $data = $document->getAll();
    $result = $this->_connection->put(AvocadoUrlHelper::buildUrl(self::URL, $collectionId, $documentId), json_encode($data));

    return true;
  }

  /**
   * Delete a document from a collection
   *
   * @throws AvocadoException
   * @return bool 
   */
  public function delete($collectionId, $documentId) {
    $result = $this->_connection->delete(AvocadoUrlHelper::buildUrl(self::URL, $collectionId, $documentId));

    return true;
  }
    
}
