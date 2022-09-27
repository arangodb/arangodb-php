<?php

/**
 * ArangoDB PHP client: document handler
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @author    Frank Mayer
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace ArangoDBClient;


/**
 * A handler that manages documents
 *
 * A document handler that fetches documents from the server and
 * persists them on the server. It does so by issuing the
 * appropriate HTTP requests to the server.<br>
 *
 * <br>
 *
 * @package   ArangoDBClient
 * @since     0.2
 */
class DocumentHandler extends Handler
{
    /**
     * documents array index
     */
    const ENTRY_DOCUMENTS = 'documents';

    /**
     * collection parameter
     */
    const OPTION_COLLECTION = 'collection';

    /**
     * example parameter
     */
    const OPTION_EXAMPLE = 'example';
    
    /**
     * overwrite option (deprecated)
     */
    const OPTION_OVERWRITE = 'overwrite';
    
    /**
     * overwriteMode option
     */
    const OPTION_OVERWRITE_MODE = 'overwriteMode';
    
    /**
     * option for returning the old document
     */
    const OPTION_RETURN_OLD = 'returnOld';
    
    /**
     * option for returning the new document
     */
    const OPTION_RETURN_NEW = 'returnNew';
    
    /**
     * silent option 
     */
    const OPTION_SILENT = 'silent';


    /**
     * Get a single document from a collection
     *
     * Alias method for getById()
     *
     * @throws Exception
     *
     * @param string $collection  - collection id as a string or number
     * @param mixed  $documentId  - document identifier
     * @param array  $options     - optional, array of options
     *                            <p>Options are :
     *                            <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                            <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                            <li>'revision' - the documents revision</li>
     *                            <li>'ifMatch' - boolean if given revision should match or not</li>
     *                            </p>
     *
     * @return Document - the document fetched from the server
     */
    public function get($collection, $documentId, array $options = [])
    {
        return $this->getById($collection, $documentId, $options);
    }


    /**
     * Check if a document exists
     *
     * This will call self::get() internally and checks if there
     * was an exception thrown which represents an 404 request.
     *
     * @throws Exception When any other error than a 404 occurs
     *
     * @param string $collection - collection id as a string or number
     * @param mixed  $documentId - document identifier
     *
     * @return boolean
     */
    public function has($collection, $documentId)
    {
        // get current exception logging status, and then turn it off
        // temporarily
        $old = Exception::getLogging();
        Exception::setLogging(false);

        try {
            // will throw ServerException if entry could not be retrieved
            $this->get($collection, $documentId);

            // restore previous Exception logging status
            Exception::setLogging($old);

            return true;
        } catch (ServerException $e) {
            // restore previous Exception logging status
            Exception::setLogging($old);

            // we are expecting a 404 to return boolean false
            if ($e->getCode() === 404) {
                return false;
            }

            // just rethrow
            throw $e;
        }

    }


    /**
     * Get a single document from a collection
     *
     * This will throw if the document cannot be fetched from the server.
     *
     * @throws Exception
     *
     * @param string $collection  - collection id as a string or number
     * @param mixed  $documentId  - document identifier
     * @param array  $options     - optional, array of options
     *                            <p>Options are :
     *                            <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                            <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                            <li>'ifMatch' - boolean if given revision should match or not</li>
     *                            <li>'revision' - The document is returned if it matches/not matches revision.</li>
     *                            </p>
     *
     * @return Document - the document fetched from the server
     */
    public function getById($collection, $documentId, array $options = [])
    {
        if (strpos($documentId, '/') !== false) {
            @list($collection ,$documentId) = explode('/', $documentId);
        }
        $data              = $this->getDocument(Urls::URL_DOCUMENT, $collection, $documentId, $options);
        $options['_isNew'] = false;

        return $this->createFromArrayWithContext($data, $options);
    }


    /**
     * Get a single document (internal method)
     *
     * This method is the workhorse for getById() in this handler and the edges handler
     *
     * @throws Exception
     *
     * @param string $url         - the server-side URL being called
     * @param string $collection  - collection id as a string or number
     * @param mixed  $documentId  - document identifier
     * @param array  $options     - optional, array of options
     *                            <p>Options are :
     *                            <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                            <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                            <li>'ifMatch' - boolean if given revision should match or not</li>
     *                            <li>'revision' - The document is returned if it matches/not matches revision.</li>
     *                            </p>
     *
     * @internal
     *
     * @return array - the document fetched from the server
     */
    protected function getDocument($url, $collection, $documentId, array $options = [])
    {
        $headers        = [];
        $this->addTransactionHeader($headers, $collection);

        $collection     = $this->makeCollection($collection);

        $url            = UrlHelper::buildUrl($url, [$collection, $documentId]);
        if (array_key_exists('ifMatch', $options) && array_key_exists('revision', $options)) {
            if ($options['ifMatch'] === true) {
                $headers['If-Match'] = '"' . $options['revision'] . '"';
            } else {
                $headers['If-None-Match'] = '"' . $options['revision'] . '"';
            }
        }

        $response = $this->getConnection()->get($url, $headers);

        if ($response->getHttpCode() === 304) {
            throw new ClientException('Document has not changed.');
        }

        return $response->getJson();
    }


    /**
     * Gets information about a single documents from a collection
     *
     * This will throw if the document cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param string  $collection - collection id as a string or number.
     * @param mixed   $documentId - document identifier.
     * @param boolean $ifMatch    -  boolean if given revision should match or not.
     * @param string  $revision   - The document is returned if it matches/not matches revision.
     *
     * @return array - an array containing the complete header including the key httpCode.
     */
    public function getHead($collection, $documentId, $revision = null, $ifMatch = null)
    {
        return $this->head(Urls::URL_DOCUMENT, $collection, $documentId, $revision, $ifMatch);
    }


    /**
     * Get meta-data for a single document (internal method)
     *
     * This method is the workhorse for getHead() in this handler and the edges handler
     *
     * @throws Exception
     *
     * @param string  $url        - the server-side URL being called
     * @param string  $collection - collection id as a string or number
     * @param mixed   $documentId - document identifier
     * @param mixed   $revision   - optional document revision
     * @param boolean $ifMatch    -  boolean if given revision should match or not.
     *
     * @internal
     *
     * @return array - the document meta-data
     */
    protected function head($url, $collection, $documentId, $revision = null, $ifMatch = null)
    {
        $headers        = [];
        $this->addTransactionHeader($headers, $collection);

        $collection     = $this->makeCollection($collection);

        $url            = UrlHelper::buildUrl($url, [$collection, $documentId]);
        if ($revision !== null && $ifMatch !== null) {
            if ($ifMatch) {
                $headers['If-Match'] = '"' . $revision . '"';
            } else {
                $headers['If-None-Match'] = '"' . $revision . '"';
            }
        }
        
        $response            = $this->getConnection()->head($url, $headers);
        $headers             = $response->getHeaders();
        $headers['httpCode'] = $response->getHttpCode();

        return $headers;
    }


    /**
     * Intermediate function to call the createFromArray function from the right context
     *
     * @param $data
     * @param $options
     *
     * @return Document
     * @throws \ArangoDBClient\ClientException
     */
    protected function createFromArrayWithContext($data, $options)
    {
        $_documentClass = $this->_documentClass;

        return $_documentClass::createFromArray($data, $options);
    }


    /**
     * Store a document to a collection
     *
     * This is an alias/shortcut to save() and replace(). Instead of having to determine which of the 3 functions to use,
     * simply pass the document to store() and it will figure out which one to call.
     *
     * This will throw if the document cannot be saved or replaced.
     *
     * @throws Exception
     *
     * @param Document $document       - the document to be added, can be passed as a document or an array
     * @param mixed    $collection     - collection id as string or number
     * @param array    $options        - optional, array of options
     *                                 <p>Options are :<br>
     *                                 <li>'createCollection' - create the collection if it does not yet exist.</li>
     *                                 <li>'waitForSync' -  if set to true, then all removal operations will instantly be synchronised to disk / If this is not specified, then the collection's default sync behavior will be applied.</li>
     *                                 </p>
     *
     * @return mixed - id of document created
     * @deprecated use insert with overwriteMode=update or overwriteMode=replace instead
     * @since 1.0
     */
    public function store(Document $document, $collection = null, array $options = [])
    {
        if ($document->getIsNew()) {

            if ($collection === null) {
                throw new ClientException('A collection id is required to store a new document.');
            }

            $result = $this->save($collection, $document, $options);
            $document->setIsNew(false);

            return $result;
        }

        if ($collection) {
            throw new ClientException('An existing document cannot be stored into a new collection');
        }

        return $this->replace($document, $options);
    }


    /**
     * insert a document into a collection
     *
     * This will add the document to the collection and return the document's id
     *
     * This will throw if the document cannot be saved
     *
     * @throws Exception
     *
     * @param mixed          $collection - collection id as string or number
     * @param Document|array $document   - the document to be added, can be passed as a document or an array
     * @param array          $options    - optional, array of options
     *                                   <p>Options are :<br>
     *                                   <li>'createCollection' - create the collection if it does not yet exist.</li>
     *                                   <li>'waitForSync' -  if set to true, then all removal operations will instantly be synchronised to disk / If this is not specified, then the collection's default sync behavior will be applied.</li>
     *                                   <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes on update instead setting their values to null. Defaults to true (keep attributes when set to null). only useful with overwriteMode = update</li>
     *                                   <li>'mergeObjects' - if true, updates to object attributes will merge the previous and the new objects. if false, replaces the object attribute with the new value. only useful with overwriteMode = update</li>
     *                                   <li>'overwriteMode' -  determines overwrite behavior in case a document with the same _key already exists. possible values: 'ignore', 'update', 'replace', 'conflict'.</li>
     *                                   <li>'overwrite' -  deprecated: if set to true, will turn the insert into a replace operation if a document with the specified key already exists.</li>
     *                                   <li>'returnNew' -  if set to true, then the newly created document will be returned.</li>
     *                                   <li>'returnOld' -  if set to true, then the updated/replaced document will be returned - useful only when using overwriteMode = insert/update.</li>
     *                                   <li>'silent' -  whether or not to return information about the created document (e.g. _key and _rev).</li>
     *                                   </p>
     *
     * @return mixed - id of document created
     * @since 1.0
     */
    public function insert($collection, $document, array $options = [])
    {
        if (is_array($document)) {
            $data = $document;
        } else {
            $data = $document->getAllForInsertUpdate();
        }
        
        $response = $this->post($collection, $data, $options);

        // This makes sure that if we're in batch mode, it will not go further and choke on the checks below.
        // Caution: Instead of a document ID, we are returning the batchpart object
        // The Id of the BatchPart can be retrieved by calling getId() on it.
        // We're basically returning an object here, in order not to accidentally use the batch part id as the document id
        if ($batchPart = $response->getBatchPart()) {
            return $batchPart;
        }
        
        if (@$options[self::OPTION_SILENT]) {
          // nothing will be returned here
          return null;
        }
        
        $json = $response->getJson();
                
        if (@$options[self::OPTION_RETURN_OLD] || @$options[self::OPTION_RETURN_NEW]) {
            return $json;
        }

        $_documentClass = $this->_documentClass;
        if (is_array($document)) {
            return $json[$_documentClass::ENTRY_KEY];
        }

        $document->setInternalKey($json[$_documentClass::ENTRY_KEY]);
        $document->setInternalId($json[$_documentClass::ENTRY_ID]);
        $document->setRevision($json[$_documentClass::ENTRY_REV]);

        $location = $response->getLocationHeader();
        if (!$location) {
            throw new ClientException('Did not find location header in server response');
        }

        $id = UrlHelper::getDocumentIdFromLocation($location);
        if ($id !== $document->getId()) {
            throw new ClientException('Got an invalid response from the server');
        }

        $document->setIsNew(false);

        return $document->getId();
    }
    
    
    /**
     * insert multiple document into a collection
     *
     * This will add the documents to the collection and return the documents' metadata
     *
     * This will throw if the documents cannot be saved
     *
     * @throws Exception
     *
     * @param mixed          $collection - collection id as string or number
     * @param array          $documents  - the documents to be added, always an array
     * @param array          $options    - optional, array of options
     *                                   <p>Options are :<br>
     *                                   <li>'createCollection' - create the collection if it does not yet exist.</li>
     *                                   <li>'waitForSync' -  if set to true, then all removal operations will instantly be synchronised to disk / If this is not specified, then the collection's default sync behavior will be applied.</li>
     *                                   <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes on update instead setting their values to null. Defaults to true (keep attributes when set to null). only useful with overwriteMode = update</li>
     *                                   <li>'mergeObjects' - if true, updates to object attributes will merge the previous and the new objects. if false, replaces the object attribute with the new value. only useful with overwriteMode = update</li>
     *                                   <li>'overwriteMode' -  determines overwrite behavior in case a document with the same _key already exists. possible values: 'ignore', 'update', 'replace', 'conflict'.</li>
     *                                   <li>'overwrite' -  deprecated: if set to true, will turn the insert into a replace operation if a document with the specified key already exists.</li>
     *                                   <li>'returnNew' -  if set to true, then the newly created document will be returned.</li>
     *                                   <li>'returnOld' -  if set to true, then the updated/replaced document will be returned - useful only when using overwriteMode = insert/update.</li>
     *                                   <li>'silent' -  whether or not to return information about the created document (e.g. _key and _rev).</li>
     *                                   </p>
     *
     * @return array - array of document metadata (one entry per document)
     * @since 3.8
     */
    public function insertMany($collection, array $documents, array $options = [])
    {
        $data = [];
        foreach ($documents as $document) {
            if (is_array($document)) {
                $data[] = $document;
            } else {
                $data[] = $document->getAllForInsertUpdate();
            }
        }

        $response = $this->post($collection, $data, $options);

        // This makes sure that if we're in batch mode, it will not go further and choke on the checks below.
        // Caution: Instead of a document ID, we are returning the batchpart object
        // The Id of the BatchPart can be retrieved by calling getId() on it.
        // We're basically returning an object here, in order not to accidentally use the batch part id as the document id
        if ($batchPart = $response->getBatchPart()) {
            return $batchPart;
        }

        return $response->getJson();
    }
    
    
    /**
     * Insert documents into a collection (internal method)
     *
     * @throws Exception
     *
     * @param string   $collection   - collection id as string or number
     * @param mixed    $data         - document data
     * @param array    $options      - array of options
     *
     * @internal
     *
     * @return HttpResponse - the insert response
     */
    protected function post($collection, $data, array $options)
    {
        $headers = [];
        $this->addTransactionHeader($headers, $collection);

        $collection     = $this->makeCollection($collection);
        
        if (!isset($options[self::OPTION_OVERWRITE_MODE]) &&
            isset($options[self::OPTION_OVERWRITE])) {
            // map "overwrite" to "overwriteMode"
            $options[self::OPTION_OVERWRITE_MODE] = $options[self::OPTION_OVERWRITE] ? 'replace' : 'conflict';
            unset($options[self::OPTION_OVERWRITE]);
        }

        $params = $this->includeOptionsInParams(
            $options, [
                'waitForSync'      => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC),
                'returnOld'        => (bool) @$options[self::OPTION_RETURN_OLD],
                'returnNew'        => (bool) @$options[self::OPTION_RETURN_NEW],
            ]
        );

        if ((isset($options['createCollection']) && $options['createCollection']) ||
            $this->getConnection()->getOption(ConnectionOptions::OPTION_CREATE)) {
            $this->lazyCreateCollection($collection, $params);
        }

        $url = UrlHelper::appendParamsUrl(Urls::URL_DOCUMENT . '/' . $collection, $params);

        return $this->getConnection()->post($url, $this->json_encode_wrapper($data, false), $headers);
    }

    
    /**
     * Insert a document into a collection
     * 
     * This is an alias for insert().
     *
     * * @deprecated use insert instead
     */
    public function save($collection, $document, array $options = []) 
    {
        return $this->insert($collection, $document, $options);
    }

    /**
     * Update an existing document in a collection, identified by the including _id and optionally _rev in the patch document.
     * Attention - The behavior of this method has changed since version 1.1
     *
     * This will update the document on the server
     *
     * This will throw if the document cannot be updated
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the document to-be-replaced is the same as the one given.
     *
     * @throws Exception
     *
     * @param Document $document - The patch document that will update the document in question
     * @param array    $options  - optional, array of options
     *                           <p>Options are :
     *                           <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                           <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     *                           <li>'mergeObjects' - if true, updates to object attributes will merge the previous and the new objects. if false, replaces the object attribute with the new value</li>
     *                           <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                           <li>'returnNew' -  if set to true, then the updated document will be returned.</li>
     *                           <li>'returnOld' -  if set to true, then the previous version of the document will be returned.</li>
     *                           <li>'silent' -  whether or not to return information about the created document (e.g. _key and _rev).</li>
     *                           </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function update(Document $document, array $options = [])
    {
        return $this->updateById($document->getCollectionId(), $this->getDocumentId($document), $document, $options);
    }


    /**
     * Update an existing document in a collection, identified by collection id and document id
     * Attention - The behavior of this method has changed since version 1.1
     *
     * This will update the document on the server
     *
     * This will throw if the document cannot be updated
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the document to-be-updated is the same as the one given.
     *
     * @throws Exception
     *
     * @param string   $collection   - collection id as string or number
     * @param mixed    $documentId   - document id as string or number
     * @param Document $document     - patch document which contains the attributes and values to be updated
     * @param array    $options      - optional, array of options
     *                               <p>Options are :
     *                               <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                               <li>'keepNull' - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     *                               <li>'mergeObjects' - if true, updates to object attributes will merge the previous and the new objects. if false, replaces the object attribute with the new value</li>
     *                               <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                               <li>'returnNew' -  if set to true, then the updated document will be returned.</li>
     *                               <li>'returnOld' -  if set to true, then the previous version of the document will be returned.</li>
     *                               <li>'silent' -  whether or not to return information about the created document (e.g. _key and _rev).</li>
     *                               </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function updateById($collection, $documentId, Document $document, array $options = [])
    {
        return $this->patch(Urls::URL_DOCUMENT, $collection, $documentId, $document, $options);
    }


    /**
     * Update an existing document in a collection (internal method)
     *
     * @throws Exception
     *
     * @param string   $url          - server-side URL being called
     * @param string   $collection   - collection id as string or number
     * @param mixed    $documentId   - document id as string or number
     * @param Document $document     - patch document which contains the attributes and values to be updated
     * @param array    $options      - optional, array of options
     *
     * @internal
     *
     * @return bool - always true, will throw if there is an error
     */
    protected function patch($url, $collection, $documentId, Document $document, array $options = [])
    {
        $headers = [];
        $this->addTransactionHeader($headers, $collection);

        $collection     = $this->makeCollection($collection);

        $params = $this->includeOptionsInParams(
            $options, [
                'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC),
                'ignoreRevs'  => true,
                'policy'      => $this->getConnectionOption(ConnectionOptions::OPTION_UPDATE_POLICY),
                'returnOld'   => (bool) @$options[self::OPTION_RETURN_OLD],
                'returnNew'   => (bool) @$options[self::OPTION_RETURN_NEW],
            ]
        );


        if (isset($params[ConnectionOptions::OPTION_UPDATE_POLICY]) &&
            $params[ConnectionOptions::OPTION_UPDATE_POLICY] === UpdatePolicy::ERROR
        ) {

            $revision = $document->getRevision();
            if (null !== $revision) {
                $params['ignoreRevs'] = false;
                $headers['if-match']  = '"' . $revision . '"';
            }
        }
        
        $url = UrlHelper::buildUrl($url, [$collection, $documentId]);
        $url = UrlHelper::appendParamsUrl($url, $params);
        
        $result = $this->getConnection()->patch($url, $this->json_encode_wrapper($document->getAllForInsertUpdate()), $headers);
        
        if (@$params[self::OPTION_SILENT]) {
          // nothing will be returned here
          return null;
        }

        $json = $result->getJson();
        $_documentClass = $this->_documentClass;
        $document->setRevision($json[$_documentClass::ENTRY_REV]);
        
        if (@$options[self::OPTION_RETURN_OLD] || @$options[self::OPTION_RETURN_NEW]) {
            return $json;
        }

        return true;
    }


    /**
     * Replace an existing document in a collection, identified by the document itself
     *
     * This will replace the document on the server
     *
     * This will throw if the document cannot be replaced
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the to-be-replaced document is the same as the one given.
     *
     * @throws Exception
     *
     * @param Document $document - document to be replaced
     * @param array    $options  - optional, array of options
     *                           <p>Options are :
     *                           <li>'policy' - replace policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                           <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                           <li>'silent' -  whether or not to return information about the replaced document (e.g. _key and _rev).</li>
     *                           <li>'ifMatch' - boolean if given revision should match or not</li>
     *                           <li>'revision' - The document is returned if it matches/not matches revision.</li></p>
     *                           <li>'returnNew' -  if set to true, then the replaced document will be returned.</li>
     *                           <li>'returnOld' -  if set to true, then the previous version of the document will be returned.</li>
     *                           </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function replace(Document $document, array $options = [])
    {
        return $this->replaceById($document, $this->getDocumentId($document), $document, $options);
    }


    /**
     * Replace an existing document in a collection, identified by collection id and document id
     *
     * This will update the document on the server
     *
     * This will throw if the document cannot be Replaced
     *
     * If policy is set to error (locally or globally through the ConnectionOptions)
     * and the passed document has a _rev value set, the database will check
     * that the revision of the to-be-replaced document is the same as the one given.
     *
     * @throws Exception
     *
     * @param mixed    $collection   - collection id as string or number
     * @param mixed    $documentId   - document id as string or number
     * @param Document $document     - document to be updated
     * @param array    $options      - optional, array of options
     *                               <p>Options are :
     *                               <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                               <li>'waitForSync' - can be used to force synchronisation of the document replacement operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                               <li>'silent' -  whether or not to return information about the replaced document (e.g. _key and _rev).</li>
     *                               <li>'ifMatch' - boolean if given revision should match or not</li>
     *                               <li>'revision' - The document is returned if it matches/not matches revision.</li></p>
     *                               <li>'returnNew' -  if set to true, then the replaced document will be returned.</li>
     *                               <li>'returnOld' -  if set to true, then the previous version of the document will be returned.</li>
     *                               </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function replaceById($collection, $documentId, Document $document, array $options = [])
    {
        return $this->put(Urls::URL_DOCUMENT, $collection, $documentId, $document, $options);
    }


    /**
     * Replace an existing document in a collection (internal method)
     *
     * @throws Exception
     *
     * @param string   $url          - the server-side URL being called
     * @param string   $collection   - collection id as string or number
     * @param mixed    $documentId   - document id as string or number
     * @param Document $document     - document to be updated
     * @param array    $options      - optional, array of options
     *
     * @internal
     *
     * @return bool - always true, will throw if there is an error
     */
    protected function put($url, $collection, $documentId, Document $document, array $options = [])
    {
        $headers = [];
        $this->addTransactionHeader($headers, $collection);

        $collection     = $this->makeCollection($collection);

        $params = $this->includeOptionsInParams(
            $options, [
                'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC),
                'ignoreRevs'  => true,
                'policy'      => $this->getConnectionOption(ConnectionOptions::OPTION_REPLACE_POLICY),
                'returnOld'   => (bool) @$options[self::OPTION_RETURN_OLD],
                'returnNew'   => (bool) @$options[self::OPTION_RETURN_NEW],
            ]
        );

        if (isset($params[ConnectionOptions::OPTION_REPLACE_POLICY]) &&
            $params[ConnectionOptions::OPTION_REPLACE_POLICY] === UpdatePolicy::ERROR
        ) {
            $revision = $document->getRevision();
            if (null !== $revision) {
                $params['ignoreRevs'] = false;
                $headers['if-match']  = '"' . $revision . '"';
            }
        }
        
        $data = $document->getAllForInsertUpdate();

        $url    = UrlHelper::buildUrl($url, [$collection, $documentId]);
        $url    = UrlHelper::appendParamsUrl($url, $params);
        $result = $this->getConnection()->put($url, $this->json_encode_wrapper($data), $headers);

        if (@$params[self::OPTION_SILENT]) {
          // nothing will be returned here
          return null;
        }

        $json = $result->getJson();
        $_documentClass = $this->_documentClass;
        $document->setRevision($json[$_documentClass::ENTRY_REV]);
        
        if (@$options[self::OPTION_RETURN_OLD] || @$options[self::OPTION_RETURN_NEW]) {
            return $json;
        }

        return true;
    }


    /**
     * Remove a document from a collection, identified by the document itself
     *
     * @throws Exception
     *
     * @param Document $document - document to be removed
     * @param array    $options  - optional, array of options
     *                           <p>Options are :
     *                           <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                           <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                           <li>'silent' -  whether or not to return information about the replaced document (e.g. _key and _rev).</li>
     *                           <li>'ifMatch' - boolean if given revision should match or not</li>
     *                           <li>'revision' - The document is returned if it matches/not matches revision.</li></p>
     *                           <li>'returnOld' -  if set to true, then the previous version of the document will be returned.</li>
     *                           </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function remove(Document $document, array $options = [])
    {
        return $this->removeById($document, $this->getDocumentId($document), $this->getRevision($document), $options);
    }


    /**
     * Remove a document from a collection, identified by the collection id and document id
     *
     * @throws Exception
     *
     * @param mixed $collection    - collection id as string or number
     * @param mixed $documentId    - document id as string or number
     * @param mixed $revision      - optional revision of the document to be deleted
     * @param array $options       - optional, array of options
     *                             <p>Options are :
     *                             <li>'policy' - update policy to be used in case of conflict ('error', 'last' or NULL [use default])</li>
     *                             <li>'waitForSync' - can be used to force synchronisation of the document removal operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                             <li>'silent' -  whether or not to return information about the replaced document (e.g. _key and _rev).</li>
     *                             <li>'ifMatch' - boolean if given revision should match or not</li>
     *                             <li>'revision' - The document is returned if it matches/not matches revision.</li></p>
     *                             <li>'returnOld' -  if set to true, then the previous version of the document will be returned.</li>
     *                             </p>
     *
     * @return bool - always true, will throw if there is an error
     */
    public function removeById($collection, $documentId, $revision = null, array $options = [])
    {
        $headers = [];
        $this->addTransactionHeader($headers, $collection);

        $collection = $this->makeCollection($collection);

        $params = $this->includeOptionsInParams(
            $options, [
                'waitForSync' => $this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC),
                'ignoreRevs'  => true,
                'policy'      => $this->getConnectionOption(ConnectionOptions::OPTION_DELETE_POLICY),
                'returnOld'   => (bool) @$options[self::OPTION_RETURN_OLD],
            ]
        );

        if (isset($params[ConnectionOptions::OPTION_DELETE_POLICY]) &&
            $params[ConnectionOptions::OPTION_DELETE_POLICY] === UpdatePolicy::ERROR
        ) {

            if (null !== $revision) {
                $params['ignoreRevs'] = false;
                $headers['if-match']  = '"' . $revision . '"';
            }
        }

        $url = UrlHelper::buildUrl(Urls::URL_DOCUMENT, [$collection, $documentId]);
        $url = UrlHelper::appendParamsUrl($url, $params);
        
        if (@$options[self::OPTION_RETURN_OLD]) {
            $result = $this->getConnection()->delete($url, $headers);
            $json = $result->getJson();
            return $json;
        }

        $this->getConnection()->delete($url, $headers);
        return true;
    }


    /**
     * Helper function to get a document id from a document or a document id value
     *
     * @throws ClientException
     *
     * @param mixed $document - document id OR document to be updated
     *
     * @return mixed - document id, will throw if there is an error
     */
    private function getDocumentId($document)
    {
        $documentId = $document;
        if ($document instanceof Document) {
            $documentId = $document->getId();
        }

        if (!(is_int($documentId) || is_string($documentId) || is_float($documentId) || trim($documentId) === '')) {
            throw new ClientException('Cannot alter a document without a document id');
        }

        return $documentId;
    }


    /**
     * Helper function to get a document id from a document or a document id value
     *
     * @throws ClientException
     *
     * @param mixed $document - document id OR document to be updated
     *
     * @return mixed - document id, will throw if there is an error
     */
    private function getRevision($document)
    {
        $revision = null;

        if ($document instanceof Document) {
            $revision = $document->getRevision();
        }

        return $revision;
    }


    /**
     * @param mixed $collection   collection name or id
     * @param array $options      - optional, array of options
     *                            <p>Options are :
     *                            <li>'createCollectionType' - "document" or 2 for document collection</li>
     *                            <li>                         "edge" or 3 for edge collection</li>
     *                            <li>'waitForSync'       - if set to true, then all removal operations will instantly be synchronised to disk / If this is not specified, then the collection's default sync behavior will be applied.</li>
     *                            </p>
     */
    protected function lazyCreateCollection($collection, $options)
    {
        $collectionHandler = new CollectionHandler($this->getConnection());

        $params = [];

        if (array_key_exists('createCollectionType', $options)) {
            $params['type'] = $options['createCollectionType'];
        }

        if (array_key_exists('waitForSync', $options)) {
            $params['waitForSync'] = $options['waitForSync'];
        }

        try {
            // attempt to create the collection
            $collectionHandler->create($collection, $params);
        } catch (Exception $e) {
            // collection may have existed already
        }
    }

}

class_alias(DocumentHandler::class, '\triagens\ArangoDb\DocumentHandler');
