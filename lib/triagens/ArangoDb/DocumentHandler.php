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
 * persists them on the server. It does so by issuing the
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
   * @param array $options - optional, array of options
   * <p>Options are :
   * <li>'includeInternals' - true to include the internal attributes. Defaults to false</li>
   * <li>'ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
   * </p>
   * @return Document - the document fetched from the server
   */
  public function get($collectionId, $documentId, array $options = array()) {
    return $this->getById($collectionId, $documentId, $options);
  }


  /**
   * Get a single document from a collection
   *
   * This will throw if the document cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @param mixed $documentId - document identifier
   * @param array $options - optional, array of options
   * <p>Options are :
   * <li>'includeInternals' - true to include the internal attributes. Defaults to false</li>
   * <li>'ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
   * </p>
   * @return Document - the document fetched from the server
   */
  public function getById($collectionId, $documentId, array $options = array()) {
    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $response = $this->getConnection()->get($url);

    $data = $response->getJson();

    return Document::createFromArray($data, $options);
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
   * @deprecated to be removed in version 2.0 - This function is being replaced by  CollectionHandler::getAllIds()
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
   * @return cursor - Returns a cursor containing the result
   *
   * @deprecated to be removed in version 2.0 - This function is being replaced by CollectionHandler::byExample()
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
   * @return mixed - id of document created
   *
   * @deprecated to be removed in version 2.0 - This function is being replaced by save()
   *
   */

  public function add($collectionId, Document $document, $create = null) {
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
  public function save($collectionId, Document $document, $create = null) {
    if ($create === null) {
      $create = $this->getConnection()->getOption(ConnectionOptions::OPTION_CREATE);
    }
    $data = $document->getAll();
    $params = array(self::OPTION_COLLECTION => $collectionId, ConnectionOptions::OPTION_CREATE => UrlHelper::getBoolString($create));
    $url = UrlHelper::appendParamsUrl(Urls::URL_DOCUMENT, $params);

    $response = $this->getConnection()->post($url,  $this->getConnection()->json_encode_wrapper($data));

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
   * Update an existing document in a collection, identified by the including _id and optionally _rev in the patch document.
   * Attention - The behavior of this method has changed since version 1.1
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
   * @param Document $document - The patch document that will update the document in question
   * @param mixed $options - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
   * <p>Options are :
   * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
   * <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
   * <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
   * </p>
   * @return bool - always true, will throw if there is an error
   */
  public function update(Document $document, $options = array()) {
    $collectionId = $this->getCollectionId($document);
    $documentId   = $this->getDocumentId($document);

    return $this->updateById($collectionId, $documentId, $document, $options);
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
   * @param mixed $options - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
   * <p>Options are :
   * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
   * <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
   * </p>
   * @return bool - always true, will throw if there is an error
   */
  public function replace(Document $document, $options = array()) {
    $collectionId = $this->getCollectionId($document);
    $documentId   = $this->getDocumentId($document);

    return $this->replaceById($collectionId, $documentId, $document, $options);
  }


  /**
   * Update an existing document in a collection, identified by collection id and document id
   * Attention - The behavior of this method has changed since version 1.1
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
   * @param Document $document - patch document which contains the attributes and values to be updated
   * @param mixed $options - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
   * <p>Options are :
   * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
   * <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
   * <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
   * </p>
   * @return bool - always true, will throw if there is an error
   */
  public function updateById($collectionId, $documentId, Document $document, $options = array()) {
   // This preserves compatibility for the old policy parameter.
      $params   = array();
      $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_UPDATE_POLICY);
      $params = $this->includeOptionsInParams($options, $params, array(
                                                                      'waitForSync' => $this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC),
                                                                      'keepNull'    => true,
                                                                 ));

    $revision = $document->getRevision();
    if (!is_null($revision)) {
      $params[ConnectionOptions::OPTION_REVISION]=$revision;
    }

    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $url = UrlHelper::appendParamsUrl($url, $params);
    $result = $this->getConnection()->patch($url, $this->getConnection()->json_encode_wrapper($document->getAll()));

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
   * @param mixed $options - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
   * <p>Options are :
   * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
   * <li>'waitForSync' - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
   * </p>
   * @return bool - always true, will throw if there is an error
   */
  public function replaceById($collectionId, $documentId, Document $document, $options = array()) {
      // This preserves compatibility for the old policy parameter.
      $params   = array();
      $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_REPLACE_POLICY);
      $params = $this->includeOptionsInParams($options, $params, array(
                                                                      'waitForSync' => ConnectionOptions::OPTION_WAIT_SYNC
                                                                 ));

    $revision = $document->getRevision();
    if (!is_null($revision)) {
      $params[ConnectionOptions::OPTION_REVISION]=$revision;
    }

    $data = $document->getAll();
    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $url = UrlHelper::appendParamsUrl($url, $params);
    $result = $this->getConnection()->put($url, $this->getConnection()->json_encode_wrapper($data));

    return true;
  }


  /**
   * Delete a document from a collection, identified by the document itself
   *
   * @throws Exception
   * @param Document $document - document to be updated
   * @param mixed $options - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
   * <p>Options are :
   * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
   * <li>'waitForSync' - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
   * </p>
   * @return bool - always true, will throw if there is an error
   *
   * @deprecated to be removed in version 2.0 - This function is being replaced by remove()
   *
   */
  public function delete(Document $document, $options = array()) {
    return $this->remove($document, $options);
  }


  /**
   * Remove a document from a collection, identified by the document itself
   *
   * @throws Exception
   * @param Document $document - document to be removed
   * @param mixed $options - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
   * <p>Options are :
   * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
   * <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
   * </p>
   * @return bool - always true, will throw if there is an error
   */
  public function remove(Document $document, $options = array()) {
    $collectionId = $this->getCollectionId($document);
    $documentId   = $this->getDocumentId($document);

    $revision = $this->getRevision($document);

    return $this->deleteById($collectionId, $documentId, $revision, $options);
  }


  /**
   * Delete a document from a collection, identified by the collection id and document id
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $documentId - document id as string or number
   * @param  mixed $revision - optional revision of the document to be deleted
   * @param mixed $options - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
   * <p>Options are :
   * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
   * <li>'waitForSync' - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
   * </p>
   * @return bool - always true, will throw if there is an error
   *
   * @deprecated to be removed in version 2.0 - This function is being replaced by removeById()
   */
  public function deleteById($collectionId, $documentId, $revision = null, $options = array()) {
    $result = $this->removeById($collectionId, $documentId, $revision, $options);

    return true;
  }


  /**
   * Remove a document from a collection, identified by the collection id and document id
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $documentId - document id as string or number
   * @param  mixed $revision - optional revision of the document to be deleted
   * @param mixed $options - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
   * <p>Options are :
   * <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
   * <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
   * </p>
   * @return bool - always true, will throw if there is an error
   */
  public function removeById($collectionId, $documentId, $revision = null, $options = array()) {
    // This preserves compatibility for the old policy parameter.
    $params   = array();
    $params = $this->validateAndIncludePolicyInParams($options, $params, ConnectionOptions::OPTION_DELETE_POLICY);
    $params = $this->includeOptionsInParams($options, $params, array(
                                                                    'waitForSync' => ConnectionOptions::OPTION_WAIT_SYNC
                                                                    ));

    if (!is_null($revision)) {
      $params[ConnectionOptions::OPTION_REVISION]=$revision;
    }

    $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, $collectionId, $documentId);
    $url = UrlHelper::appendParamsUrl($url, $params);
    $result = $this->getConnection()->delete($url);
    return true;
  }

//todo: (@frankmayer) check if refactoring a bit more if it makes sense...
    /**
     * Helper function that validates and includes the policy setting into the parameters array given.
     * @param array $options - The options array that may hold the policy to include in the parameters. If it's not an array, then the value is the policy value.
     * @param array $params - The parameters into which the options will be included.
     * @param mixed $policyOption - the policyOption key to use.
     *
     * @return array $params - array of parameters for use in a url
     */
    protected function validateAndIncludePolicyInParams($options, $params, $policyOption)
    {
        $policy   = null;

        if (!is_array($options)) {
            $policy = $options;
        } else {
            $policy = array_key_exists('policy', $options) ? $options['policy'] : $policy;
        }

        if ($policy === null) {
            $policy = $this->getConnection()->getOption($policyOption);
        }

        UpdatePolicy::validate($policy);

        $params[$policyOption] = $policy;

        return $params;
    }


   /**
     * Helper function that runs through the options given and includes them into the parameters array given.
     * Only options that are set in $includeArray will be included.
     *
     * @param array $options - The options array that holds the options to include in the parameters
     * @param array $params - The parameters into which the options will be included.
     * @param array $includeArray - The array that defines which options are allowed to be included, and what their default value is. for example: 'waitForSync'=>true
     *
     * @return array $params - array of parameters for use in a url
     */
    protected function includeOptionsInParams($options, $params, $includeArray=array())
    {
        #$value = null;
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                if (array_key_exists($key, $includeArray)  ) {
                    $params[$key] = $value;
                    if ($value === NULL) {
                        $params[$key]= $includeArray[$key];
                   }
                }

            }
        }
        return $params;
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

}
