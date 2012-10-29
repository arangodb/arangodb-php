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
 * A document handler that fetches documents from the server and
 * persists them on the server. It does so by issueing the 
 * appropriate HTTP requests to the server.
 *
 * @package ArangoDbPhpClient
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
   * 
   * Alias method for getById()
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @param mixed $documentId - document identifier
   * @return Document - the document fetched from the server
   */
  public function get($collectionId, $documentId) {
    return $this->getById($collectionId, $documentId);
  }


  /**
   * Get a single document from a collection
   * 
   * This will throw if the document cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @param mixed $documentId - document identifier
   * @return Document - the document fetched from the server
   */
  public function getById($collectionId, $documentId) {
    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $response = $this->getConnection()->get($url);

    $data = $response->getJson();

    return Document::createFromArray($data);
  }


  /**
   * Get the list of all documents' ids from a collection
   * 
   * This will throw if the list cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @return array - ids of documents in the collection
   * 
   * @deprecated to be deprecated in version 2.0 - This function is being replaced by  CollectionHandler::getAllIds()
   * 
   */
  public function getAllIds($collectionId) {
    $collectionHandler=new CollectionHandler($this->getConnection());
  return $collectionHandler->getAllIds($collectionId);
  }


  /**
   * Get document(s) by specifying an example
   * 
   * This will throw if the list cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $document - the example document as a Document object or an array
   * @param bool $sanitize - remove _id and _rev attributes from result documents
   * @return array - documents matching the example [0...n]
   * 
   * @deprecated to be deprecated in version 2.0 - This function is being replaced by CollectionHandler::byExample() 
   */
  public function getByExample($collectionId, $document, $sanitize = false) {
    $collectionHandler=new CollectionHandler($this->getConnection());
  return $collectionHandler->byExample($collectionId, $document, $sanitize);
  }


  /**
   * Add a document to a collection
   * 
   * This will add the document to the collection and return the document's id
   * 
   * This will throw if the document cannot be created
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param Document $document - the document to be added
   * @param bool $create - create the collection if it does not yet exist
   * @return mixed - id of document created \
   * 
   * @deprecated to be deprecated in version 2.0 - This function is being replaced by save()
   *
   */
   
  public function add($collectionId, Document $document, $create = NULL) {
    return $this->save($collectionId, $document, $create);
  }


  /**
   * save a document to a collection
   * 
   * This will add the document to the collection and return the document's id
   * 
   * This will throw if the document cannot be saved
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param Document $document - the document to be added
   * @param bool $create - create the collection if it does not yet exist
   * @return mixed - id of document created
   * @since 1.0
   */
  public function save($collectionId, Document $document, $create = NULL) {
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
   * Update an existing document in a collection, identified by the document itself
   * 
   * This will update the document on the server
   * 
   * This will throw if the document cannot be updated
   * 
   * If policy is set to error (locally or globally through the connectionoptions)
   * and the passed document has a _rev value set, the database will check 
   * that the revision of the to-be-replaced document is the same as the one given.
   *
   * @throws Exception
   * @param Document $document - document to be updated
   * @param mixed $policy - update policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   * 
   * @deprecated Attention!! To be deprecated in version 1.1 - This function is being replaced by replace()
   */
  public function update(Document $document, $policy = NULL) {
    return $this->replace( $document, $policy);
  }


  /**
   * Replace an existing document in a collection, identified by the document itself
   * 
   * This will update the document on the server
   * 
   * This will throw if the document cannot be updated
   * 
   * If policy is set to error (locally or globally through the connectionoptions)
   * and the passed document has a _rev value set, the database will check 
   * that the revision of the to-be-replaced document is the same as the one given.
   *
   * @throws Exception
   * @param Document $document - document to be updated
   * @param mixed $policy - update policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   */
  public function replace(Document $document, $policy = NULL) {
    $collectionId = $this->getCollectionId($document);
    $documentId   = $this->getDocumentId($document);

    return $this->updateById($collectionId, $documentId, $document, $policy);
  }


  /**
   * Update an existing document in a collection, identified by collection id and document id
   * 
   * This will update the document on the server
   * 
   * This will throw if the document cannot be updated
   * 
   * If policy is set to error (locally or globally through the connectionoptions)
   * and the passed document has a _rev value set, the database will check 
   * that the revision of the to-be-replaced document is the same as the one given.
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $documentId - document id as string or number
   * @param Document $document - document to be updated
   * @param mixed $policy - update policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   * 
   * @deprecated Attention!! To be deprecated in version 1.1 - This function is being replaced by replaceById()
   */
  public function updateById($collectionId, $documentId, Document $document, $policy = NULL) {
    $this->replaceById($collectionId, $documentId, $document, $policy);
    return true;
  }
  

  /**
   * Replace an existing document in a collection, identified by collection id and document id
   * 
   * This will update the document on the server
   * 
   * This will throw if the document cannot be Replaced
   * 
   * If policy is set to error (locally or globally through the connectionoptions)
   * and the passed document has a _rev value set, the database will check 
   * that the revision of the to-be-replaced document is the same as the one given.
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $documentId - document id as string or number
   * @param Document $document - document to be updated
   * @param mixed $policy - update policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   */
  public function replaceById($collectionId, $documentId, Document $document, $policy = NULL) {
    $revision = $document->getRevision();
   if (!is_null($revision)) {
       $params[ConnectionOptions::OPTION_REVISION]=$revision;
    } 

    if ($policy === NULL) {
      $policy = $this->getConnection()->getOption(ConnectionOptions::OPTION_UPDATE_POLICY);
    }
    $params[ConnectionOptions::OPTION_UPDATE_POLICY]=$policy;

    UpdatePolicy::validate($policy);

    $data = $document->getAll();
    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $url = UrlHelper::appendParamsUrl($url, $params);
    $result = $this->getConnection()->put($url, json_encode($data));

    return true;
  }


  /**
   * Delete a document from a collection, identified by the document itself
   *
   * @throws Exception
   * @param Document $document - document to be updated
   * @param mixed $policy - policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   * 
   * @deprecated To be deprecated in version 2.0 - This function is being replaced by remove()
   * 
   */
  public function delete(Document $document, $policy = NULL) {
    return $this->remove($document, $policy);
  }                                


  /**
   * Remove a document from a collection, identified by the document itself
   *
   * @throws Exception
   * @param Document $document - document to be removed
   * @param mixed $policy - policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   */
  public function remove(Document $document, $policy = NULL) {
    $collectionId = $this->getCollectionId($document);
    $documentId   = $this->getDocumentId($document);

    $revision = $this->getRevision($document);

    return $this->deleteById($collectionId, $documentId, $revision, $policy);
  }                                


  /**
   * Delete a document from a collection, identified by the collection id and document id
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $documentId - document id as string or number
   * @param  mixed $revision - revision of the document to be deleted
   * @param mixed $policy - policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   * 
   * @deprecated To be deprecated in version 2.0 - This function is being replaced by removeById()
   */
  public function deleteById($collectionId, $documentId, $revision, $policy = NULL) {
    $result = $this->removeById($collectionId, $documentId, $revision, $policy);

    return true;
  }


  /**
   * Remove a document from a collection, identified by the collection id and document id
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $documentId - document id as string or number
   * @param  mixed $revision - revision of the document to be deleted
   * @param mixed $policy - policy to be used in case of conflict
   * @return bool - always true, will throw if there is an error
   */
  public function removeById($collectionId, $documentId, $revision, $policy = NULL) {
   if (!is_null($revision)) {
       $params[ConnectionOptions::OPTION_REVISION]=$revision;
    } 

    if ($policy === NULL) {
      $policy = $this->getConnection()->getOption(ConnectionOptions::OPTION_UPDATE_POLICY);
    }
    $params[ConnectionOptions::OPTION_UPDATE_POLICY]=$policy;
    
    UpdatePolicy::validate($policy);
    
    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $url = UrlHelper::appendParamsUrl($url, $params);
    $result = $this->getConnection()->delete($url);
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
   * Helper function to get a document id from a document or a document id value
   *
   * @throws ClientException
   * @param mixed $document - document id OR document to be updated
   * @return mixed - document id, will throw if there is an error
   */
  private function getRevision($document) {
    if ($document instanceof Document) {
      $revision = $document->getRevision();
    }
    else {
      $revision = null;
    }

    return $revision;
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


  /**
   * Return an array of cursor options
   *
   * @param bool $sanitize - sanitize flag
   * @return array - array of options
   */
  private function getCursorOptions($sanitize) {
    return array(
      Cursor::ENTRY_SANITIZE => $sanitize,
    );
  }
    
}
