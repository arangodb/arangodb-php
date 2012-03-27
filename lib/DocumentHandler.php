<?php

/**
 * AvocadoDB PHP client: document handler
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * A document handler that fetches documents from the server and
 * persists them on the server. It does so by issueing the 
 * appropriate HTTP requests to the server.
 *
 * @package AvocadoDbPhpClient
 */
class DocumentHandler {
  /**
   * Connection object
   * @param Connection
   */
  private $_connection;

  /**
   * URL base part for all document-related REST calls
   */
  const URL              = '/document';

  /**
   * documents array index
   */
  const ENTRY_DOCUMENTS  = 'documents';
  
  /**
   * Construct a new document handler
   *
   * @param Connection $connection - connection to be used
   * @return void
   */
  public function __construct(Connection $connection) {
    $this->_connection = $connection;
  }
  
  /**
   * Get a single document from a collection
   * This will throw if the document cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @param mixed $documentId - document identifier
   * @return Document - the document fetched from the server
   */
  public function get($collectionId, $documentId) {
    $url = UrlHelper::buildUrl(self::URL, $collectionId, $documentId);
    $response = $this->_connection->get($url);

    $data = $response->getJson();

    return Document::createFromArray($data);
  }
  
  /**
   * Get the list of all documents' ids from a collection
   * This will throw if the list cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @return array - ids of documents in the collection
   */
  public function getAllIds($collectionId) {
    $url = UrlHelper::appendParamsUrl(self::URL, array('collection' => $collectionId));
    $response = $this->_connection->get($url);
    
    $data = $response->getJson();
    if (!isset($data[self::ENTRY_DOCUMENTS])) {
      throw new ClientException('Got an invalid document list from the server');
    }

    $ids = array();
    foreach ($data[self::ENTRY_DOCUMENTS] as $location) {
      $ids[] = UrlHelper::getDocumentIdFromLocation($location);
    }

    return $ids;
  }
  
  /**
   * Add a document to a collection
   * This will add the document to the collection and return the document's id
   * This will throw if the document cannot be created
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param Document $document - the document to be added
   * @param bool $create - create the collection if it does not yet exist
   * @return mixed - id of document created
   */
  public function add($collectionId, Document $document, $create = NULL) {
    if ($create === NULL) {
      $create = $this->_connection->getOption(ConnectionOptions::OPTION_CREATE);
    }

    $data = $document->getAll();
    $params = array('collection' => $collectionId, 'createCollection' => $create ? "true" : "false");
    $url = UrlHelper::appendParamsUrl(self::URL, $params); 
    $response = $this->_connection->post($url, json_encode($data));

    $location = $response->getHeader('location');
    if (!$location) {
      throw new ClientException('Did not find location header in server response');
    }
    $id = UrlHelper::getDocumentIdFromLocation($location);
    $document->setId($id);

    return $id;
  }

  /**
   * Update an existing document in a collection
   * This will update the document on the server
   * This will throw if the document cannot be updated
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param Document $document - document to be updated
   * @return bool - always true, will throw if there is an error
   */
  public function update($collectionId, Document $document) {
    $documentId = $this->getDocumentId($document);
    
    $data = $document->getAll();
    $result = $this->_connection->put(UrlHelper::buildUrl(self::URL, $collectionId, $documentId), json_encode($data));

    return true;
  }

  /**
   * Delete a document from a collection
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $document - document id OR document to be updated
   * @return bool - always true, will throw if there is an error
   */
  public function delete($collectionId, $document) {
    $documentId = $this->getDocumentId($document);

    $result = $this->_connection->delete(UrlHelper::buildUrl(self::URL, $collectionId, $documentId));

    return true;
  }

  /**
   * Helper function to get a document id from a document or a document id value
   *
   * @throws ClientException
   * @param mixed $document - document id OR document to be updated
   * @return mixed - document id, will throw if there is an error
   */

  private function getDocumentId($document) {
    if ($document instanceof Document) {
      $documentId = $document->getId();
    }
    else {
      $documentId = $document;
    }

    if (!$documentId || !(is_string($documentId) || is_double($documentId) || is_int($documentId))) {
      throw new ClientException('Cannot alter a document without a document id');
    }

    return $documentId;
  }
    
}
