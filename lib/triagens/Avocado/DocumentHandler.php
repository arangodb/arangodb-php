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
class DocumentHandler extends Handler {
  /**
   * documents array index
   */
  const ENTRY_DOCUMENTS   = 'documents';
  
  /**
   * collection parameter
   */
  const OPTION_COLLECTION = 'collection';
  
  /**
   * example parameter
   */
  const OPTION_EXAMPLE    = 'example';
  
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
    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $response = $this->getConnection()->get($url);

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
    $url = UrlHelper::appendParamsUrl(Urls::URL_DOCUMENT, array(self::OPTION_COLLECTION => $collectionId));
    $response = $this->getConnection()->get($url);
    
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
   * Get document(s) by specifying an example
   * This will throw if the list cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $document - the example document as a Document object or an array
   * @return array - documents matching the example [0...n]
   */
  public function getByExample($collectionId, $document) {
    if (is_array($document)) {
      $document = Document::createFromArray($document);
    }

    if (!($document instanceof Document)) {
      throw new ClientException('Invalid example document specification');
    }
    
    $data = array(self::OPTION_COLLECTION => $collectionId, self::OPTION_EXAMPLE => $document->getAll());

    $response = $this->getConnection()->put(Urls::URL_EXAMPLE, json_encode($data));
    
    $data = $response->getJson();
    if (!is_array($data)) {
      throw new ClientException('Got an invalid document list from the server');
    }

    $documents = array();
    foreach ($data as $document) {
      $documents[] = Document::createFromArray($document);
    }

    return $documents;
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
      $create = $this->getConnection()->getOption(ConnectionOptions::OPTION_CREATE);
    }

    $data = $document->getAll();
    $params = array(self::OPTION_COLLECTION => $collectionId, ConnectionOptions::OPTION_CREATE => UrlHelper::getBoolString($create));
    $url = UrlHelper::appendParamsUrl(Urls::URL_DOCUMENT, $params); 
    $response = $this->getConnection()->post($url, json_encode($data));

    $location = $response->getLocationHeader();
    if (!$location) {
      throw new ClientException('Did not find location header in server response');
    }

    $json = $response->getJson();
    $id = UrlHelper::getDocumentIdFromLocation($location);

    $document->setInternalId($json[Document::ENTRY_ID]);
    $document->setRevision($json[Document::ENTRY_REV]);
    
    if ($id != $document->getId()) {
      throw new ClientException('Got an invalid response from the server');
    }

    return $document->getId();
  }

  /**
   * Update an existing document in a collection
   * This will update the document on the server
   * This will throw if the document cannot be updated
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param Document $document - document to be updated
   * @param bool $policy - update policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   */
  public function update($collectionId, Document $document, $policy = NULL) {
    $collectionId = $this->getCollectionId($document);
    $documentId   = $this->getDocumentId($document);

    if ($policy === NULL) {
      $policy = $this->getConnection()->getOption(ConnectionOptions::OPTION_UPDATE_POLICY);
    }

    UpdatePolicy::validate($policy);
    
    $data = $document->getAll();
    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $url = UrlHelper::appendParamsUrl($url, array(ConnectionOptions::OPTION_UPDATE_POLICY => $policy));
    $result = $this->getConnection()->put($url, json_encode($data));

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

    $result = $this->getConnection()->delete(UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId));

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
  
  /**
   * Helper function to get a collection id from a document
   *
   * @throws ClientException
   * @param Document $document - document id 
   * @return mixed - collection id, will throw if there is an error
   */
  private function getCollectionId(Document $document) {
    $collectionId = $document->getCollectionId();

    if (!$collectionId || !(is_string($collectionId) || is_double($collectionId) || is_int($collectionId))) {
      throw new ClientException('Cannot alter a document without a document id');
    }

    return $collectionId;
  }
    
}
