<?php

/**
 * ArangoDB PHP client: collection handler
 *
 * @package   ArangoDbPhpClient
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * A collection handler that fetches collection data from the server and
 * creates collections on the server. It does so by issuing the
 * appropriate HTTP requests to the server.
 *
 * @package ArangoDbPhpClient
 */
class CollectionHandler extends
    Handler
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
     * example parameter
     */
    const OPTION_CREATE_COLLECTION = 'createCollection';

    /**
     * attribute parameter
     */
    const OPTION_ATTRIBUTE = 'attribute';

    /**
     * left parameter
     */
    const OPTION_LEFT = 'left';

    /**
     * right parameter
     */
    const OPTION_RIGHT = 'right';

    /**
     * closed parameter
     */
    const OPTION_CLOSED = 'closed';

    /**
     * latidude parameter
     */
    const OPTION_LATITUDE = 'latitude';

    /**
     * longitude parameter
     */
    const OPTION_LONGITUDE = 'longitude';

    /**
     * distance parameter
     */
    const OPTION_DISTANCE = 'distance';

    /**
     * radius parameter
     */
    const OPTION_RADIUS = 'radius';

    /**
     * skip parameter
     */
    const OPTION_SKIP = 'skip';

    /**
     * limit parameter
     */
    const OPTION_LIMIT = 'limit';

    /**
     * count fields
     */
    const OPTION_FIELDS = 'fields';

    /**
     * count unique
     */
    const OPTION_UNIQUE = 'unique';

    /**
     * count unique
     */
    const OPTION_TYPE = 'type';

    /**
     * count option
     */
    const OPTION_COUNT = 'count';

    /**
     * properties option
     */
    const OPTION_PROPERTIES = 'properties';

    /**
     * figures option
     */
    const OPTION_FIGURES = 'figures';

    /**
     * load option
     */
    const OPTION_LOAD = 'load';

    /**
     * unload option
     */
    const OPTION_UNLOAD = 'unload';

    /**
     * truncate option
     */
    const OPTION_TRUNCATE = 'truncate';

    /**
     * rename option
     */
    const OPTION_RENAME = 'rename';


    /**
     * Get information about a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     *
     * @return Collection - the collection fetched from the server
     */
    public function get($collectionId)
    {
        $url      = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId);
        $response = $this->getConnection()->get($url);

        $data = $response->getJson();

        return Collection::createFromArray($data);
    }


    /**
     * Get properties of a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     *
     * @return Collection - the collection fetched from the server
     */
    public function getProperties($collectionId)
    {
        $url      = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_PROPERTIES);
        $response = $this->getConnection()->get($url);

        $data = $response->getJson();

        return Collection::createFromArray($data);
    }


    /**
     * Get the number of documents in a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     *
     * @return int - the number of documents in the collection
     *
     * @deprecated to be removed in version 2.0 - This function is being replaced by count()
     */
    public function getCount($collectionId)
    {
        return $this->count($collectionId);
    }


    /**
     * Get the number of documents in a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     *
     * @return int - the number of documents in the collection
     */
    public function count($collectionId)
    {
        $url      = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_COUNT);
        $response = $this->getConnection()->get($url);

        $data  = $response->getJson();
        $count = $data[self::OPTION_COUNT];

        return (int) $count;
    }


    /**
     * Get figures for a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     *
     * @return array - the figures for the collection
     *
     * @deprecated to be removed in version 2.0 - This function is being replaced by figures()
     */
    public function getFigures($collectionId)
    {
        return $this->figures($collectionId);
    }


    /**
     * Get figures for a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     *
     * @return array - the figures for the collection
     */
    public function figures($collectionId)
    {
        $url      = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_FIGURES);
        $response = $this->getConnection()->get($url);

        $data    = $response->getJson();
        $figures = $data[self::OPTION_FIGURES];

        return $figures;
    }


    /**
     * Adds a new collection on the server
     *
     * This will add the collection on the server and return its id
     *
     * This will throw if the collection cannot be created
     *
     * @throws Exception
     *
     * @param Collection $collection - collection object to be created on the server
     *
     * @return mixed - id of collection created
     *
     * @deprecated to be removed in version 2.0 - This function is being replaced by create()
     */
    public function add(Collection $collection)
    {
        return $this->create($collection);
    }


    /**
     * Creates a new collection on the server
     *
     * This will add the collection on the server and return its id
     * The id is mainly returned for backwards compatibility, but you should use the collection name for any reference to the collection.   *
     * This will throw if the collection cannot be created
     *
     * @throws Exception
     *
     * @param Collection $collection - collection object to be created on the server
     *
     * @return mixed - id of collection created
     */
    public function create(Collection $collection)
    {
        if ($collection->getWaitForSync() === null) {
            $collection->setWaitForSync($this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC));
        }

        if ($collection->getJournalSize() === null) {
            $collection->setJournalSize($this->getConnection()->getOption(ConnectionOptions::OPTION_JOURNAL_SIZE));
        }

        if ($collection->getIsSystem() === null) {
            $collection->setIsSystem($this->getConnection()->getOption(ConnectionOptions::OPTION_IS_SYSTEM));
        }

        if ($collection->getIsVolatile() === null) {
            $collection->setIsVolatile($this->getConnection()->getOption(ConnectionOptions::OPTION_IS_VOLATILE));
        }

        $type     = $collection->getType() ? $collection->getType() : Collection::getDefaultType();
        $params   = array(
            Collection::ENTRY_NAME         => $collection->getName(),
            Collection::ENTRY_TYPE         => $type,
            Collection::ENTRY_WAIT_SYNC    => $collection->getWaitForSync(),
            Collection::ENTRY_JOURNAL_SIZE => $collection->getJournalSize(),
            Collection::ENTRY_IS_SYSTEM    => $collection->getIsSystem(),
            Collection::ENTRY_IS_VOLATILE  => $collection->getIsVolatile()
        );
        $response = $this->getConnection()->post(Urls::URL_COLLECTION, $this->getConnection()->json_encode_wrapper($params));

        //    $location = $response->getLocationHeader();
        //    if (!$location) {
        //      throw new ClientException('Did not find location header in server response');
        //    }
        $jsonResponse = $response->getJson();
        $id           = $jsonResponse['id'];
        $collection->setId($id);

        return $id;
    }


    /**
     * Creates an index on a collection on the server
     *
     * This will create an index on the collection on the server and return its id
     *
     * This will throw if the index cannot be created
     *
     * @throws Exception
     *
     * @param mixed   $collectionId - The id of the collection where the index is to be created
     * @param string  $type         - index type: hash, skiplist or geo
     * @param array   $attributes   - an array of attributes that can be defined like array('a') or array('a', 'b.c')
     * @param boolean $unique       - true/false to create a unique index
     *
     * @return mixed - id of collection created
     */
    public function index($collectionId, $type = "", $attributes = array(), $unique = false)
    {

        $urlParams  = array(self::OPTION_COLLECTION => $collectionId);
        $bodyParams = array(
            self::OPTION_TYPE   => $type,
            self::OPTION_FIELDS => $attributes,
            self::OPTION_UNIQUE => $unique
        );
        $url        = UrlHelper::appendParamsUrl(Urls::URL_INDEX, $urlParams);
        $response   = $this->getConnection()->post($url, $this->getConnection()->json_encode_wrapper($bodyParams));

        $httpCode = $response->getHttpCode();
        switch ($httpCode) {
            case 404:
                throw new ClientException('Collection-identifier is unknown');

                break;
            case 400:
                throw new ClientException('cannot create unique index due to documents violating uniqueness');
                break;
        }

        $result = $response->getJson();

        return $result;
    }


    /**
     * Get indexes of a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as a string or number
     *
     * @return array $data - the indexes result-set from the server
     */
    public function getIndexes($collectionId)
    {
        $urlParams = array(self::OPTION_COLLECTION => $collectionId);
        $url       = UrlHelper::appendParamsUrl(Urls::URL_INDEX, $urlParams);
        $response  = $this->getConnection()->get($url);

        $data = $response->getJson();

        return $data;
    }


    /**
     * Drop an index
     *
     * @throws Exception
     *
     * @param mixed $indexHandle - index handle (collection name / index id)
     *
     * @return bool - always true, will throw if there is an error
     */
    public function dropIndex($indexHandle)
    {
        $handle = explode("/", $indexHandle);
        $result = $this->getConnection()->delete(UrlHelper::buildUrl(Urls::URL_INDEX, $handle[0], $handle[1]));

        return true;
    }


    /**
     * Delete a collection
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as string or number or collection object
     *
     * @return bool - always true, will throw if there is an error
     *
     * @deprecated to be removed in version 2.0 - This function is being replaced by drop()
     */
    public function delete($collection)
    {
        return $this->drop($collection);
    }


    /**
     * Drop a collection
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as string or number or collection object
     *
     * @return bool - always true, will throw if there is an error
     */
    public function drop($collection)
    {
        $collectionName = $this->getCollectionName($collection);

        if ($this->isValidCollectionId($collectionName)) {
            throw new ClientException('Cannot alter a collection without a collection id');
        }

        $result = $this->getConnection()->delete(UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionName));

        return true;
    }

    /**
     * Rename a collection
     *
     * @throws Exception
     *
     * @param mixed  $collection - collection id as string or number or collection object
     * @param string $name       - new name for collection
     *
     * @return bool - always true, will throw if there is an error
     */
    public function rename($collection, $name)
    {
        $collectionId = $this->getCollectionId($collection);

        if ($this->isValidCollectionId($collectionId)) {
            throw new ClientException('Cannot alter a collection without a collection id');
        }

        $params = array(Collection::ENTRY_NAME => $name);
        $result = $this->getConnection()->put(UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_RENAME), $this->getConnection()->json_encode_wrapper($params));

        return true;
    }

    /**
     * Load a collection into the server's memory
     *
     * This will load the given collection into the server's memory.
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as string or number or collection object
     *
     * @return bool - always true, will throw if there is an error
     */
    public function load($collection)
    {
        $collectionId = $this->getCollectionId($collection);

        if ($this->isValidCollectionId($collectionId)) {
            throw new ClientException('Cannot alter a collection without a collection id');
        }

        $result = $this->getConnection()->put(UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_LOAD), '');

        return $result;
    }


    /**
     * Unload a collection from the server's memory
     *
     * This will unload the given collection from the server's memory.
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as string or number or collection object
     *
     * @return bool - always true, will throw if there is an error
     */
    public function unload($collection)
    {
        $collectionId = $this->getCollectionId($collection);

        if ($this->isValidCollectionId($collectionId)) {
            throw new ClientException('Cannot alter a collection without a collection id');
        }

        $result = $this->getConnection()->put(UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_UNLOAD), '');

        return $result;
    }


    /**
     * Truncate a collection
     *
     * This will remove all documents from the collection but will leave the metadata and indexes intact.
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as string or number or collection object
     *
     * @return bool - always true, will throw if there is an error
     */
    public function truncate($collection)
    {
        $collectionId = $this->getCollectionId($collection);

        if ($this->isValidCollectionId($collectionId)) {
            throw new ClientException('Cannot alter a collection without a collection id');
        }

        $result = $this->getConnection()->put(UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_TRUNCATE), '');

        return true;
    }


    /**
     * Get document(s) by specifying an example
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed      $collectionId - collection id as string or number
     * @param mixed      $document     - the example document as a Document object or an array
     * @param bool|array $options      - optional, prior to v1.0.0 this was a boolean value for sanitize, since v1.0.0 it's an array of options.
     * <p>Options are :<br>
     * <li>'sanitize' - true to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li>'hiddenAttributes' - set an array of hidden attributes for created documents.
     * <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a resultset of documents, the getall() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     * </p>
     * </li>
     * </p>
     *
     * @return cursor - Returns a cursor containing the result
     */
    public function byExample($collectionId, $document, $options = array())
    {
        // This preserves compatibility for the old sanitize parameter.
        $sanitize = false;
        if (!is_array($options)) {
            $sanitize = $options;
            $options  = array();
        }
        else {
            $sanitize = array_key_exists('sanitize', $options) ? $options['sanitize'] : $sanitize;
        }
        $options = array_merge($options, $this->getCursorOptions($sanitize));
        if (is_array($document)) {
            $document = Document::createFromArray($document, $options);
        }

        if (!($document instanceof Document)) {
            throw new ClientException('Invalid example document specification');
        }

        $data = array(
            self::OPTION_COLLECTION => $collectionId,
            self::OPTION_EXAMPLE    => $document->getAll(array('ignoreHiddenAttributes' => true))
        );

        $response = $this->getConnection()->put(Urls::URL_EXAMPLE, $this->getConnection()->json_encode_wrapper($data));

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Get the first document matching a given example.
     *
     * This will throw if the document cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed      $collectionId - collection id as string or number
     * @param mixed      $document     - the example document as a Document object or an array
     * @param bool|array $options      - optional, an array of options.
     * <p>Options are :<br>
     * <li>'sanitize' - true to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li>'hiddenAttributes' - set an array of hidden attributes for created documents.
     * <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a resultset of documents, the getall() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     * </p>
     * </li>
     * </p>
     *
     * @return Document - the document fetched from the server
     */
    public function firstExample($collectionId, $document, $options = array())
    {
        // This preserves compatibility for the old sanitize parameter.
        $sanitize = false;
        if (!is_array($options)) {
            $sanitize = $options;
            $options  = array();
        }
        else {
            $sanitize = array_key_exists('sanitize', $options) ? $options['sanitize'] : $sanitize;
        }
        $options = array_merge($options, $this->getCursorOptions($sanitize));
        if (is_array($document)) {
            $document = Document::createFromArray($document, $options);
        }

        if (!($document instanceof Document)) {
            throw new ClientException('Invalid example document specification');
        }

        $data = array(
            self::OPTION_COLLECTION => $collectionId,
            self::OPTION_EXAMPLE    => $document->getAll(array('ignoreHiddenAttributes' => true))
        );

        $response = $this->getConnection()->put(Urls::URL_FIRST_EXAMPLE, $this->getConnection()->json_encode_wrapper($data));
        $data = $response->getJson();

        return Document::createFromArray($data['document'], $options);
    }


    /**
     * Remove document(s) by specifying an example
     *
     * This will throw on any error
     *
     * @throws Exception
     *
     * @param mixed      $collectionId - collection id as string or number
     * @param mixed      $document     - the example document as a Document object or an array
     * @param bool|array $options      - optional - an array of options.
     * <p>Options are :<br>
     * <li>
     * 'waitForSync' -  if set to true, then all removal operations will instantly be synchronised to disk.<br>
     *                                 If this is not specified, then the collection's default sync behavior will be applied.
     * </li>
     * </p>
     *
     * @return int - number of documents that were deleted
     */
    public function removeByExample($collectionId, $document, $options = array())
    {
        if (is_array($document)) {
            $document = Document::createFromArray($document, $options);
        }

        if (!($document instanceof Document)) {
            throw new ClientException('Invalid example document specification');
        }

        $data = array(
            self::OPTION_COLLECTION => $collectionId,
            self::OPTION_EXAMPLE    => $document->getAll(array('ignoreHiddenAttributes' => true))
        );

        $response = $this->getConnection()->put(Urls::URL_REMOVE_BY_EXAMPLE, $this->getConnection()->json_encode_wrapper($data));

        $responseArray = $response->getJson();

        if ($responseArray['error'] === true) {
            throw new ClientException('Invalid example document specification');
        }

        return $responseArray['deleted'];
    }


    /**
     * Get document(s) by specifying range
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed  $collectionId    - collection id as string or number
     * @param string $attribute       - the attribute path , like 'a', 'a.b', etc...
     * @param mixed  $left            - The lower bound.
     * @param mixed  $right           - The upper bound.
     * @param array  $options         - optional array of options.
     * <p>Options are :<br>
     * <li>'sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li>'hiddenAttributes' - Set an array of hidden attributes for created documents.
     * <p>
     *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document.<br>
     *                                The difference is, that if you're returning a resultset of documents, the getall() is already called<br>
     *                                and the hidden attributes would not be applied to the attributes.<br>
     * </p>
     *
     * <li>'closed' - If true, use interval including left and right, otherwise exclude right, but include left.
     * <li>'skip' - The documents to skip in the query.
     * <li>'limit' - The maximal amount of documents to return.
     * </li>
     * </p>
     *
     * @return array - documents matching the example [0...n]
     */
    public function range($collectionId, $attribute, $left, $right, $options = array())
    {
        $closed   = null;
        $skip     = null;
        $limit    = null;
        $sanitize = false;
        $options  = array_merge($options, $this->getCursorOptions($sanitize));
        extract($options, EXTR_IF_EXISTS);

        if ($attribute === '') {
            throw new ClientException('Invalid attribute specification');
        }

        $data = array(
            self::OPTION_COLLECTION => $collectionId,
            self::OPTION_ATTRIBUTE  => $attribute,
            self::OPTION_LEFT       => $left,
            self::OPTION_RIGHT      => $right
        );
        if ($closed) {
            $data[self::OPTION_CLOSED] = $closed;
        }
        ;
        if ($skip) {
            $data[self::OPTION_SKIP] = $skip;
        }
        ;
        if ($limit) {
            $data[self::OPTION_LIMIT] = $limit;
        }
        ;

        $response = $this->getConnection()->put(Urls::URL_RANGE, $this->getConnection()->json_encode_wrapper($data));

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Get document(s) by specifying near
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed  $collectionId    - collection id as string or number
     * @param double $latitude        - The latitude of the coordinate.
     * @param double $longitude       - The longitude of the coordinate.
     * @param array  $options         - optional array of options.
     * <p>Options are :<br>
     * <li>'sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li>'hiddenAttributes' - Set an array of hidden attributes for created documents.
     * <p>
     *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                The difference is, that if you're returning a resultset of documents, the getall() is already called <br>
     *                                and the hidden attributes would not be applied to the attributes.<br>
     * </p>
     *
     * <li>'distance' - If given, the attribute key used to store the distance. (optional)
     * <li>'skip' - The documents to skip in the query.
     * <li>'limit' - The maximal amount of documents to return.
     * </li>
     * </p>
     *
     * @return array - documents matching the example [0...n]
     */
    public function near($collectionId, $latitude, $longitude, $options = array())
    {
        $distance = null;
        $skip     = null;
        $limit    = null;
        $sanitize = false;
        $options  = array_merge($options, $this->getCursorOptions($sanitize));
        extract($options, EXTR_IF_EXISTS);

        $data = array(
            self::OPTION_COLLECTION => $collectionId,
            self::OPTION_LATITUDE   => $latitude,
            self::OPTION_LONGITUDE  => $longitude
        );
        if ($skip) {
            $data[self::OPTION_SKIP] = $skip;
        }
        ;
        if ($limit) {
            $data[self::OPTION_LIMIT] = $limit;
        }
        ;
        if ($distance) {
            $data[self::OPTION_DISTANCE] = $distance;
        }
        ;
        $response = $this->getConnection()->put(Urls::URL_NEAR, $this->getConnection()->json_encode_wrapper($data));

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Get document(s) by specifying within
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed  $collectionId    - collection id as string or number
     * @param double $latitude        - The latitude of the coordinate.
     * @param double $longitude       - The longitude of the coordinate.
     * @param int    $radius          - The maximal radius (in meters).
     * @param array  $options         - optional array of options.
     * <p>Options are :<br>
     * <li>'sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     * <li>'hiddenAttributes' - Set an array of hidden attributes for created documents.
     * <p>
     *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document.<br>
     *                                The difference is, that if you're returning a resultset of documents, the getall() is already called <br>
     *                                and the hidden attributes would not be applied to the attributes.<br>
     * </p>
     *
     * <li>'distance' - If given, the attribute key used to store the distance. (optional)
     * <li>'skip' - The documents to skip in the query.
     * <li>'limit' - The maximal amount of documents to return.
     * </li>
     * </p>
     *
     * @return array - documents matching the example [0...n]
     */
    public function within($collectionId, $latitude, $longitude, $radius, $options = array())
    {
        $distance = null;
        $skip     = null;
        $limit    = null;
        $sanitize = false;
        $options  = array_merge($options, $this->getCursorOptions($sanitize));
        extract($options, EXTR_IF_EXISTS);

        $data = array(
            self::OPTION_COLLECTION => $collectionId,
            self::OPTION_LATITUDE   => $latitude,
            self::OPTION_LONGITUDE  => $longitude,
            self::OPTION_RADIUS     => $radius
        );
        if ($skip) {
            $data[self::OPTION_SKIP] = $skip;
        }
        ;
        if ($limit) {
            $data[self::OPTION_LIMIT] = $limit;
        }
        ;
        if ($distance) {
            $data[self::OPTION_DISTANCE] = $distance;
        }
        ;
        $response = $this->getConnection()->put(Urls::URL_WITHIN, $this->getConnection()->json_encode_wrapper($data));

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Get the list of all documents' ids from a collection
     *
     * This will throw if the list cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as string or number
     *
     * @return array - ids of documents in the collection
     */
    public function getAllIds($collectionId)
    {
        $url      = UrlHelper::appendParamsUrl(Urls::URL_DOCUMENT, array(self::OPTION_COLLECTION => $collectionId));
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
     * Return an array of cursor options
     *
     * @param bool $sanitize - sanitize flag
     *
     * @return array - array of options
     */
    private function getCursorOptions($sanitize)
    {
        return array(
            Cursor::ENTRY_SANITIZE => $sanitize,
        );
    }


    /**
     * Checks if the collectionId given, is valid. Returns true if it is, or false if it is not.
     *
     * @param $collectionId
     *
     * @return bool
     */
    public function isValidCollectionId($collectionId)
    {
        return !$collectionId || !(is_string($collectionId) || is_double($collectionId) || is_int($collectionId));
    }


    /**
     * Gets the collectionId from the given collectionObject or string/integer
     *
     * @param mixed $collection
     *
     * @return mixed
     */
    public function getCollectionId($collection)
    {
        if ($collection instanceof Collection) {
            $collectionId = $collection->getId();

            return $collectionId;
        }
        else {
            $collectionId = $collection;

            return $collectionId;
        }
    }


    /**
     * Gets the collectionId from the given collectionObject or string/integer
     *
     * @param mixed $collection
     *
     * @return mixed
     */
    public function getCollectionName($collection)
    {
        if ($collection instanceof Collection) {
            $collectionId = $collection->getName();

            return $collectionId;
        }
        else {
            $collectionId = $collection;

            return $collectionId;
        }
    }


    /**
     * Import documents from a file
     *
     * This will throw on all errors except insertion errors
     *
     * @throws Exception
     *
     * @param mixed $collectionId   - collection id as string or number
     * @param mixed $importFileName - The filename that holds the import data.
     * @param array $options        - optional - an array of options.
     * <p>Options are :<br>
     * 'type' -  if type is not set or it's set to '' or null, the Header-Value format must be provided in the import file.<br>
     *                              if set to 'documents', then the file's content must have its documents line by line. Each line will be interpreted as a document.<br>
     *                              if set to 'array' then the file's content must provide the documents as a list of documents instead of the above line by line.<br>
     * <br>
     *                              More info on how the import functionality works: <a href ="https://github.com/triAGENS/ArangoDB/wiki/HttpImport">https://github.com/triAGENS/ArangoDB/wiki/HttpImport</a>
     * <br>
     * </li>
     * <li>'createCollection' - If true, create the collection if it doesn't exist. Defaults to false </li>
     * </p>
     *
     * @return int - number of documents that were deleted
     */
    public function importFromFile(
        $collectionId, $importFileName, $options = array(
        'createCollection' => false,
        'type'             => null
    )
    ) {

        $contents = file_get_contents($importFileName);
        if ($contents === false) {
            throw new ClientException('Input file "' . $importFileName . '" could not be found.');
        }

        $result = $this->import($collectionId, $contents, $options);

        return $result;
    }


    /**
     * Import documents into a collection
     *
     * This will throw on all errors except insertion errors
     *
     * @throws Exception
     *
     * @param mixed $collectionId - collection id as string or number
     * @param mixed $importData   - The data to import. This can be a string holding the data according to the type of import, or an array of documents
     * @param array $options      - optional - an array of options.
     * <p>Options are :<br>
     * <li>
     * 'type' -  if type is not set or it's set to '' or null, the Header-Value format must be provided in the import file.<br>
     *                            if set to 'documents', then the file's content must have its documents line by line. Each line will be interpreted as a document.<br>
     *                            if set to 'array' then the file's content must provide the documents as a list of documents instead of the above line by line.<br>
     * <br>
     *                            More info on how the import functionality works: <a href ="https://github.com/triAGENS/ArangoDB/wiki/HttpImport">https://github.com/triAGENS/ArangoDB/wiki/HttpImport</a>
     * <br>
     * </li>
     * <li>'createCollection' - If true, create the collection if it doesn't exist. Defaults to false </li>
     * </p>
     *
     * @return int - number of documents that were deleted
     */
    public function import(
        $collectionId, $importData, $options = array(
        'createCollection' => false,
        'type'             => null
    )
    ) {
        $tmpContent = '';
        if (is_array($importData)) {
            foreach ($importData as $document) {
                $tmpContent .= $document->toJson() . "\r\n";
            }
            $importData = $tmpContent;
            unset($tmpContent);
            $options['type'] = 'documents';
        }

        $params[self::OPTION_COLLECTION] = $collectionId;
        if (array_key_exists('createCollection', $options)) {
            $params[self::OPTION_CREATE_COLLECTION] = $options['createCollection'] == true ? true : false;
        }
        if (array_key_exists('type', $options)) {
            switch ($options['type']) {
                case "documents":
                    $params[self::OPTION_TYPE] = 'documents';
                    break;
                case "array":
                    $params[self::OPTION_TYPE] = 'array';
                    break;
            }
        }

        $url = UrlHelper::appendParamsUrl(Urls::URL_IMPORT, $params);

        $response = $this->getConnection()->post($url, $importData);

        $responseArray = $response->getJson();

        return $responseArray;
    }
}
