<?php

/**
 * ArangoDB PHP client: collection handler
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 * Provides management of collections
 *
 * The collection handler fetches collection data from the server and
 * creates collections on the server.
 *
 * @package   ArangoDBClient
 * @since     0.2
 */
class CollectionHandler extends Handler
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
    const OPTION_NEW_VALUE = 'newValue';

    /**
     * example parameter
     */
    const OPTION_CREATE_COLLECTION = 'createCollection';

    /**
     * attribute parameter
     */
    const OPTION_ATTRIBUTE = 'attribute';

    /**
     * keys parameter
     */
    const OPTION_KEYS = 'keys';

    /**
     * stream parameter
     */
    const OPTION_STREAM = 'stream';

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
     * latitude parameter
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
     * index parameter
     */
    const OPTION_INDEX = 'index';

    /**
     * limit parameter
     */
    const OPTION_LIMIT = 'limit';

    /**
     * fields
     */
    const OPTION_FIELDS = 'fields';

    /**
     * unique
     */
    const OPTION_UNIQUE = 'unique';

    /**
     * type
     */
    const OPTION_TYPE = 'type';

    /**
     * size option
     */
    const OPTION_SIZE = 'size';

    /**
     * geo index option
     */
    const OPTION_GEO_INDEX = 'geo';

    /**
     * geoJson option
     */
    const OPTION_GEOJSON = 'geoJson';

    /**
     * hash index option
     */
    const OPTION_HASH_INDEX = 'hash';

    /**
     * fulltext index option
     */
    const OPTION_FULLTEXT_INDEX = 'fulltext';

    /**
     * minLength option
     */
    const OPTION_MIN_LENGTH = 'minLength';

    /**
     * skiplist index option
     */
    const OPTION_SKIPLIST_INDEX = 'skiplist';

    /**
     * persistent index option
     */
    const OPTION_PERSISTENT_INDEX = 'persistent';
    
    /**
     * ttl index option
     */
    const OPTION_TTL_INDEX = 'ttl';
    
    /**
     * expireAfter option
     */
    const OPTION_EXPIRE_AFTER = 'expireAfter';
    
    /**
     * inBackground option
     */
    const OPTION_IN_BACKGROUND = 'inBackground';

    /**
     * sparse index option
     */
    const OPTION_SPARSE = 'sparse';

    /**
     * count option
     */
    const OPTION_COUNT = 'count';

    /**
     * query option
     */
    const OPTION_QUERY = 'query';

    /**
     * checksum option
     */
    const OPTION_CHECKSUM = 'checksum';

    /**
     * revision option
     */
    const OPTION_REVISION = 'revision';
    
    /**
     * responsible shard option
     */
    const OPTION_RESPONSIBLE_SHARD = 'responsibleShard';
    
    /**
     * shards option
     */
    const OPTION_SHARDS = 'shards';

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
     * Creates a new collection on the server
     *
     * This will add the collection on the server and return its id
     * The id is mainly returned for backwards compatibility, but you should use the collection name for any reference to the collection.   *
     * This will throw if the collection cannot be created
     *
     * @throws Exception
     *
     * @param mixed $collection - collection object to be created on the server or a string with the name
     * @param array $options    - an array of options.
     *                          <p>Options are:<br>
     *                          <li>'type'                 - 2 -> normal collection, 3 -> edge-collection</li>
     *                          <li>'waitForSync'          - if set to true, then all removal operations will instantly be synchronised to disk / If this is not specified, then the collection's default sync behavior will be applied.</li>
     *                          <li>'journalSize'          - journalSize value.</li>
     *                          <li>'isSystem'             - false->user collection(default), true->system collection .</li>
     *                          <li>'isVolatile'           - false->persistent collection(default), true->volatile (in-memory) collection .</li>
     *                          <li>'keyOptions'           - key options to use.</li>
     *                          <li>'distributeShardsLike' - name of prototype collection for identical sharding.</li>
     *                          <li>'numberOfShards'       - number of shards for the collection.</li>
     *                          <li>'replicationFactor'    - number of replicas to keep (default: 1).</li>
     *                          <li>'writeConcern'         - minimum number of replicas to be successful when writing (default: 1).</li>
     *                          <li>'shardKeys'            - array of shard key attributes.</li>
     *                          <li>'shardingStrategy'     - sharding strategy to use in cluster.</li>
     *                          <li>'smartJoinAttribute'   - attribute name for smart joins (if not shard key).</li>
     *                          </p>
     *
     * @return mixed - id of collection created
     */
    public function create($collection, array $options = [])
    {
        if (is_string($collection)) {
            $name       = $collection;
            $collection = new Collection();
            $collection->setName($name);
            foreach ($options as $key => $value) {
                $collection->{'set' . ucfirst($key)}($value);
            }
        }
        if ($collection->getWaitForSync() === null) {
            $collection->setWaitForSync($this->getConnectionOption(ConnectionOptions::OPTION_WAIT_SYNC));
        }

        if ($collection->getJournalSize() === null) {
            $collection->setJournalSize($this->getConnectionOption(ConnectionOptions::OPTION_JOURNAL_SIZE));
        }

        if ($collection->getIsSystem() === null) {
            $collection->setIsSystem($this->getConnectionOption(ConnectionOptions::OPTION_IS_SYSTEM));
        }

        if ($collection->getIsVolatile() === null) {
            $collection->setIsVolatile($this->getConnectionOption(ConnectionOptions::OPTION_IS_VOLATILE));
        }

        $type   = $collection->getType() ?: Collection::getDefaultType();
        $params = [
            Collection::ENTRY_NAME         => $collection->getName(),
            Collection::ENTRY_TYPE         => $type,
            Collection::ENTRY_WAIT_SYNC    => $collection->getWaitForSync(),
            Collection::ENTRY_JOURNAL_SIZE => $collection->getJournalSize(),
            Collection::ENTRY_IS_SYSTEM    => $collection->getIsSystem(),
            Collection::ENTRY_IS_VOLATILE  => $collection->getIsVolatile(),
            Collection::ENTRY_KEY_OPTIONS  => $collection->getKeyOptions(),
        ];

        // set extra cluster attributes
        if ($collection->getDistributeShardsLike() !== null) {
            $params[Collection::ENTRY_DISTRIBUTE_SHARDS_LIKE] = $collection->getDistributeShardsLike();
        }

        if ($collection->getNumberOfShards() !== null) {
            $params[Collection::ENTRY_NUMBER_OF_SHARDS] = $collection->getNumberOfShards();
        }
        
        if ($collection->getReplicationFactor() !== null) {
            $params[Collection::ENTRY_REPLICATION_FACTOR] = $collection->getReplicationFactor();
        }
        
        if ($collection->getWriteConcern() !== null) {
            $params[Collection::ENTRY_WRITE_CONCERN] = $collection->getWriteConcern();
        }
        
        if ($collection->getShardingStrategy() !== null) {
            $params[Collection::ENTRY_SHARDING_STRATEGY] = $collection->getShardingStrategy();
        }

        if (is_array($collection->getShardKeys())) {
            $params[Collection::ENTRY_SHARD_KEYS] = $collection->getShardKeys();
        }
        
        if ($collection->getSmartJoinAttribute() !== null) {
            $params[Collection::ENTRY_SMART_JOIN_ATTRIBUTE] = $collection->getSmartJoinAttribute();
        }

        $response = $this->getConnection()->post(Urls::URL_COLLECTION, $this->json_encode_wrapper($params));

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
     * exclude system collections
     */
    const OPTION_EXCLUDE_SYSTEM = 'excludeSystem';


    /**
     * Check if a collection exists
     *
     * This will call self::get() internally and checks if there
     * was an exception thrown which represents an 404 request.
     *
     * @throws Exception When any other error than a 404 occurs
     *
     * @param  mixed $collection - collection id as a string or number
     *
     * @return boolean
     */
    public function has($collection)
    {
        $collection = $this->makeCollection($collection);

        try {
            // will throw ServerException if entry could not be retrieved
            $this->get($collection);

            return true;
        } catch (ServerException $e) {
            // we are expecting a 404 to return boolean false
            if ($e->getCode() === 404) {
                return false;
            }

            // just rethrow
            throw $e;
        }
    }


    /**
     * Get the number of documents in a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as a string or number
     *
     * @return int - the number of documents in the collection
     */
    public function count($collection)
    {
        $headers    = [];
        $this->addTransactionHeader($headers, $collection);

        $collection = $this->makeCollection($collection);
        $url        = UrlHelper::buildUrl(Urls::URL_COLLECTION, [$collection, self::OPTION_COUNT]);
        $response   = $this->getConnection()->get($url, $headers);

        $data  = $response->getJson();
        $count = $data[self::OPTION_COUNT];

        return (int) $count;
    }


    /**
     * Get information about a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as a string or number
     *
     * @return Collection - the collection fetched from the server
     */
    public function get($collection)
    {
        $collection = $this->makeCollection($collection);

        $url      = UrlHelper::buildUrl(Urls::URL_COLLECTION, [$collection]);
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
     * @param mixed $collection - collection id as a string or number
     *
     * @return Collection - the collection fetched from the server
     */
    public function getProperties($collection)
    {
        $collection = $this->makeCollection($collection);
        $url        = UrlHelper::buildUrl(Urls::URL_COLLECTION, [$collection, self::OPTION_PROPERTIES]);
        $response   = $this->getConnection()->get($url);

        $data = $response->getJson();

        return Collection::createFromArray($data);
    }


    /**
     * Get figures for a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as a string or number
     *
     * @return array - the figures for the collection
     */
    public function figures($collection)
    {
        $collection = $this->makeCollection($collection);
        $url        = UrlHelper::buildUrl(Urls::URL_COLLECTION, [$collection, self::OPTION_FIGURES]);
        $response   = $this->getConnection()->get($url);

        $data = $response->getJson();

        return $data[self::OPTION_FIGURES];
    }


    /**
     * Calculate a checksum of the collection.
     *
     * Will calculate a checksum of the meta-data (keys and optionally revision ids)
     * and optionally the document data in the collection.
     *
     * @throws Exception
     *
     * @param mixed   $collection           - collection as string or object
     * @param boolean $withRevisions        - optional boolean whether or not to include document revision ids
     *                                      in the checksum calculation.
     * @param boolean $withData             - optional boolean whether or not to include document body data in the
     *                                      checksum calculation.
     *
     * @return array - array containing keys "checksum" and "revision"
     */
    public function getChecksum($collection, $withRevisions = false, $withData = false)
    {
        $url      = UrlHelper::buildUrl(Urls::URL_COLLECTION, [$this->makeCollection($collection), self::OPTION_CHECKSUM]);
        $url      = UrlHelper::appendParamsUrl($url, ['withRevisions' => $withRevisions, 'withData' => $withData]);
        $response = $this->getConnection()->get($url);

        return $response->getJson();
    }

    /**
     * Returns the Collections revision ID
     *
     * The revision id is a server-generated string that clients can use to check whether data in a collection has
     * changed since the last revision check.
     *
     * @throws Exception
     *
     * @param mixed $collection - collection as string or object
     *
     * @return array - containing a key revision
     */
    public function getRevision($collection)
    {
        $url      = UrlHelper::buildUrl(Urls::URL_COLLECTION, [$this->makeCollection($collection), self::OPTION_REVISION]);
        $response = $this->getConnection()->get($url);

        return $response->getJson();
    }

    /**
     * Rename a collection
     *
     * @throws Exception
     *
     * @param mixed  $collection - collection as string or object
     * @param string $name       - new name for collection
     *
     * @return bool - always true, will throw if there is an error
     */
    public function rename($collection, $name)
    {
        $params = [Collection::ENTRY_NAME => $name];
        $this->getConnection()->put(
            UrlHelper::buildUrl(Urls::URL_COLLECTION, [$this->makeCollection($collection), self::OPTION_RENAME]),
            $this->json_encode_wrapper($params)
        );

        return true;
    }

    /**
     * Load a collection into the server's memory
     *
     * This will load the given collection into the server's memory.
     *
     * @throws Exception
     *
     * @param mixed $collection - collection as string or object
     *
     * @return HttpResponse - HTTP response object
     */
    public function load($collection)
    {
        $result = $this->getConnection()->put(
            UrlHelper::buildUrl(Urls::URL_COLLECTION, [$this->makeCollection($collection), self::OPTION_LOAD]),
            ''
        );

        return $result;
    }

    /**
     * Unload a collection from the server's memory
     *
     * This will unload the given collection from the server's memory.
     *
     * @throws Exception
     *
     * @param mixed $collection - collection as string or object
     *
     * @return HttpResponse - HTTP response object
     */
    public function unload($collection)
    {
        $result = $this->getConnection()->put(
            UrlHelper::buildUrl(Urls::URL_COLLECTION, [$this->makeCollection($collection), self::OPTION_UNLOAD]),
            ''
        );

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
        $headers      = [];
        $bodyParams   = [];
        $this->addTransactionHeader($headers, $collection);
        if ($collection instanceof StreamingTransactionCollection) {
            $bodyParams['transactionId'] = $collection->getTrxId();
        }

        $collection   = $this->makeCollection($collection);

        $this->getConnection()->put(
            UrlHelper::buildUrl(Urls::URL_COLLECTION, [$collection, self::OPTION_TRUNCATE]),
            $this->json_encode_wrapper($bodyParams),
            $headers
        );

        return true;
    }


    /**
     * Drop a collection
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as string or number or collection object
     * @param array $options    - an array of options for the drop operation
     *
     * @return bool - always true, will throw if there is an error
     */
    public function drop($collection, array $options = [])
    {
        $appendix = '';
        if (is_array($options) && isset($options['isSystem'])) {
            $appendix = '?isSystem=' . UrlHelper::getBoolString($options['isSystem']);
        }

        $this->getConnection()->delete(UrlHelper::buildUrl(Urls::URL_COLLECTION, [$this->makeCollection($collection)]) . $appendix);

        return true;
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
        return !$collectionId || !(is_string($collectionId) || is_float($collectionId) || is_int($collectionId));
    }

    /**
     * Get list of all available collections per default with the collection names as index.
     * Returns empty array if none are available.
     *
     * @param array $options            - optional - an array of options.
     *                                  <p>Options are :<br>
     *                                  <li>'excludeSystem' -   With a value of true, all system collections will be excluded from the response.</li>
     *                                  <li>'keys' -  With a value of "collections", the index of the resulting array is numerical,
     *                                  With a value of "names", the index of the resulting array are the collection names.</li>
     *                                  </p>
     *
     * @return array
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public function getAllCollections(array $options = [])
    {
        $options = array_merge(['excludeSystem' => false, 'keys' => 'result'], $options);
        $params  = [];
        if ($options['excludeSystem'] === true) {
            $params[self::OPTION_EXCLUDE_SYSTEM] = true;
        }
        $url      = UrlHelper::appendParamsUrl(Urls::URL_COLLECTION, $params);
        $response = $this->getConnection()->get(UrlHelper::buildUrl($url, []));
        $response = $response->getJson();
        if (isset($response[$options['keys']])) {
            $result = [];
            foreach ($response[$options['keys']] as $collection) {
                $result[$collection['name']] = $collection;
            }

            return $result;
        }

        return $response;
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

        $collectionId = $collection;

        return $collectionId;
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

        $collectionId = $collection;

        return $collectionId;
    }


    /**
     * Import documents from a file
     *
     * This will throw on all errors except insertion errors
     *
     * @throws Exception
     *
     * @param mixed $collectionId   - collection as string or object
     * @param mixed $importFileName - The filename that holds the import data.
     * @param array $options        - optional - an array of options.
     *                              <p>Options are :<br>
     *                              'type' -  if type is not set or it's set to '' or null, the Header-Value format must be provided in the import file.<br>
     *                              <p>
     *                              <li>                       if set to 'documents', then the file's content must have its documents line by line. Each line will be interpreted as a document.</li>
     *                              <li>                       if set to 'array' then the file's content must provide the documents as a list of documents instead of the above line by line.</li>
     *                              <br>
     *                              More info on how the import functionality works: <a href ="https://github.com/triAGENS/ArangoDB/wiki/HttpImport">https://github.com/triAGENS/ArangoDB/wiki/HttpImport</a>
     *                              </p>
     *                              <br>
     *                              </li>
     *                              <li>'createCollection' - If true, create the collection if it does not exist. Defaults to false </li>
     *                              </p>
     *
     * @return array - returns an array with the server's response data from the import command
     */
    public function importFromFile($collection, $importFileName, array $options = [])
    {

        $contents = file_get_contents($importFileName);
        if ($contents === false) {
            throw new ClientException('Input file "' . $importFileName . '" could not be found.');
        }

        return $this->import($collection, $contents, $options);
    }


    /**
     * Import documents into a collection
     *
     * This will throw on all errors except insertion errors
     *
     *
     * @param              $collection   mixed $collection - collection as string or object
     * @param string|array $importData   - The data to import. This can be a string holding the data according to the type of import, or an array of documents
     * @param array        $options      - optional - an array of options.
     *                                   <p>Options are :<br>
     *                                   <li>
     *                                   'type' -  if type is not set or it's set to '' or null, the Header-Value format must be provided in the import file.<br>
     *                                   <p>
     *                                   <li>                       if set to 'documents', then the file's content must have its documents line by line. Each line will be interpreted as a document.</li>
     *                                   <li>                       if set to 'array' then the file's content must provide the documents as a list of documents instead of the above line by line.</li>
     *                                   <br>
     *                                   More info on how the import functionality works: <a href ="https://github.com/triAGENS/ArangoDB/wiki/HttpImport">https://github.com/triAGENS/ArangoDB/wiki/HttpImport</a>
     *                                   </p>
     *                                   <br>
     *
     *                                   </li>
     *                                   <li>'createCollection' - If true, create the collection if it does not exist. Defaults to false </li>
     *                                   </p>
     *
     *                                   Other options as described in API Documentation*
     *
     * @see https://www.arangodb.com/docs/stable/http/bulk-imports.html
     *
     * @return array
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public function import($collection, $importData, array $options = [])
    {
        $collection = $this->makeCollection($collection);

        $tmpContent = '';
        if (is_array($importData)) {
            foreach ($importData as $document) {
                /** @var $document Document */
                $tmpContent .= $document->toJson() . "\r\n";
            }
            $importData = $tmpContent;
            unset($tmpContent);
            $options['type'] = 'documents';
        }

        if ((isset($options['createCollection']) && $options['createCollection']) ||
            $this->getConnection()->getOption(ConnectionOptions::OPTION_CREATE)) {
            $this->lazyCreateCollection($collection, $options);
        }

        $params = array_merge(
            [self::OPTION_COLLECTION => $collection],
            $options
        );

        if (array_key_exists('type', $options)) {
            switch ($options['type']) {
                case 'documents':
                    $params[self::OPTION_TYPE] = 'documents';
                    break;
                case 'array':
                    $params[self::OPTION_TYPE] = 'array';
                    break;
            }
        }

        $url = UrlHelper::appendParamsUrl(Urls::URL_IMPORT, $params);

        $response = $this->getConnection()->post($url, $importData);

        return $response->getJson();
    }


    /**
     * Create a hash index
     *
     * @param mixed   $collection   - the collection as name or object
     * @param array   $fields       - an array of fields
     * @param bool    $unique       - whether the values in the index should be unique or not
     * @param bool    $sparse       - whether the index should be sparse
     * @param bool    $inBackground - true if index shall be created in background
     *
     * @deprecated use CollectionHandler::createIndex instead
     *
     * @return array - server response of the created index
     * @throws \ArangoDBClient\Exception
     */
    public function createHashIndex($collection, array $fields, $unique = null, $sparse = null, $inBackground = false)
    {
        $indexOptions = [
            self::OPTION_TYPE   => 'hash',
            self::OPTION_FIELDS => $fields
        ];

        if ($unique) {
            $indexOptions[self::OPTION_UNIQUE] = (bool) $unique;
        }
        if ($sparse) {
            $indexOptions[self::OPTION_SPARSE] = (bool) $sparse;
        }
        if ($inBackground) {
            $indexOptions[self::OPTION_IN_BACKGROUND] = (bool) $inBackground;
        }

        return $this->createIndex($collection, $indexOptions);
    }

    /**
     * Create a fulltext index
     *
     * @param mixed  $collection   - collection as string or object
     * @param array  $fields       - an array of fields
     * @param int    $minLength    - the minimum length of words to index
     * @param bool   $inBackground - true if index shall be created in background
     *
     * @deprecated use CollectionHandler::createIndex instead
     *
     * @return array - server response of the created index
     * @throws \ArangoDBClient\Exception
     */
    public function createFulltextIndex($collection, array $fields, $minLength = null, $inBackground = false)
    {
        $indexOptions = [
            self::OPTION_TYPE   => 'fulltext',
            self::OPTION_FIELDS => $fields
        ];

        if ($minLength) {
            $indexOptions[self::OPTION_MIN_LENGTH] = $minLength;
        }
        if ($inBackground) {
            $indexOptions[self::OPTION_IN_BACKGROUND] = (bool) $inBackground;
        }

        return $this->createIndex($collection, $indexOptions);
    }

    /**
     * Create a skip-list index
     *
     * @param mixed  $collection   - collection as string or object
     * @param array  $fields       - an array of fields
     * @param bool   $unique       - whether the index is unique or not
     * @param bool   $sparse       - whether the index should be sparse
     * @param bool   $inBackground - true if index shall be created in background
     *
     * @deprecated use CollectionHandler::createIndex instead
     *
     * @return array - server response of the created index
     * @throws \ArangoDBClient\Exception
     */
    public function createSkipListIndex($collection, array $fields, $unique = null, $sparse = null, $inBackground = false)
    {
        $indexOptions = [
            self::OPTION_TYPE   => 'skiplist',
            self::OPTION_FIELDS => $fields
        ];

        if ($unique) {
            $indexOptions[self::OPTION_UNIQUE] = (bool) $unique;
        }
        if ($sparse) {
            $indexOptions[self::OPTION_SPARSE] = (bool) $sparse;
        }
        if ($inBackground) {
            $indexOptions[self::OPTION_IN_BACKGROUND] = (bool) $inBackground;
        }

        return $this->createIndex($collection, $indexOptions);
    }

    /**
     * Create a persistent index
     *
     * @param mixed  $collection   - collection as string or object
     * @param array  $fields       - an array of fields
     * @param bool   $unique       - whether the index is unique or not
     * @param bool   $sparse       - whether the index should be sparse
     * @param bool   $inBackground - true if index shall be created in background
     *
     * @deprecated use CollectionHandler::createIndex instead
     *
     * @return array - server response of the created index
     * @throws \ArangoDBClient\Exception
     */
    public function createPersistentIndex($collection, array $fields, $unique = null, $sparse = null, $inBackground = false)
    {
        $indexOptions = [
            self::OPTION_TYPE   => 'persistent',
            self::OPTION_FIELDS => $fields
        ];

        if ($unique) {
            $indexOptions[self::OPTION_UNIQUE] = (bool) $unique;
        }
        if ($sparse) {
            $indexOptions[self::OPTION_SPARSE] = (bool) $sparse;
        }
        if ($inBackground) {
            $indexOptions[self::OPTION_IN_BACKGROUND] = (bool) $inBackground;
        }

        return $this->createIndex($collection, $indexOptions);
    }
    
    /**
     * Create a TTL index
     *
     * @param mixed  $collection   - collection as string or object
     * @param array  $fields       - an array of fields (only a single one allowed)
     * @param number $expireAfter  - number of seconds after index value after which documents expire
     * @param bool   $inBackground - true if index shall be created in background
     *
     * @deprecated use CollectionHandler::createIndex instead
     *
     * @return array - server response of the created index
     * @throws \ArangoDBClient\Exception
     */
    public function createTtlIndex($collection, array $fields, $expireAfter, $inBackground = false)
    {
        $indexOptions = [
            self::OPTION_TYPE         => 'ttl',
            self::OPTION_FIELDS       => $fields,
            self::OPTION_EXPIRE_AFTER => (double) $expireAfter
        ];
    
        if ($inBackground) {
            $indexOptions[self::OPTION_IN_BACKGROUND] = (bool) $inBackground;
        }

        return $this->createIndex($collection, $indexOptions);
    }

    /**
     * Create a geo index
     *
     * @param mixed   $collection   - collection as string or object
     * @param array   $fields       - an array of fields
     * @param bool    $geoJson      - whether to use geoJson or not
     * @param bool    $inBackground - true if index shall be created in background
     *
     * @deprecated use CollectionHandler::createIndex instead
     *
     * @return array - server response of the created index
     * @throws \ArangoDBClient\Exception
     */
    public function createGeoIndex($collection, array $fields, $geoJson = null, $inBackground = false)
    {
        $indexOptions = [
            self::OPTION_TYPE   => 'geo',
            self::OPTION_FIELDS => $fields,
        ];

        if ($geoJson) {
            $indexOptions[self::OPTION_GEOJSON] = (bool) $geoJson;
        }
        if ($inBackground) {
            $indexOptions[self::OPTION_IN_BACKGROUND] = (bool) $inBackground;
        }

        return $this->createIndex($collection, $indexOptions);
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
     * @param mixed  $collection   - collection as string or object
     * @param string $type         - index type: hash, skiplist, geo, ttl, fulltext, or persistent
     * @param array  $attributes   - an array of attributes that can be defined like array('a') or array('a', 'b.c')
     * @param bool   $unique       - true/false to create a unique index
     * @param array  $indexOptions - an associative array of options for the index like array('geoJson' => true, 'sparse' => false)
     *
     * @deprecated use CollectionHandler::createIndex instead
     *
     * @return array - server response of the created index
     */
    public function index($collection, $type, array $attributes = [], $unique = false, array $indexOptions = [])
    {
        $urlParams  = [self::OPTION_COLLECTION => $this->makeCollection($collection)];
        $bodyParams = [
            self::OPTION_TYPE   => $type,
            self::OPTION_FIELDS => $attributes,
        ];

        if ($unique !== null) {
            $bodyParams[self::OPTION_UNIQUE] = (bool) $unique;
        }

        $bodyParams = array_merge($bodyParams, $indexOptions);

        $url      = UrlHelper::appendParamsUrl(Urls::URL_INDEX, $urlParams);
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($bodyParams));

        $httpCode = $response->getHttpCode();
        switch ($httpCode) {
            case 404:
                throw new ClientException('Collection is unknown');
                break;
            case 400:
                throw new ClientException('cannot create unique index due to documents violating uniqueness');
                break;
        }

        return $response->getJson();
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
     * @param mixed  $collection   - collection as string or object
     * @param array  $indexOptions - an associative array of options for the index like array('type' => hash, 'fields' => ..., 'sparse' => false)
     *
     * @return array - server response of the created index
     * @since 3.5
     */
    public function createIndex($collection, array $indexOptions)
    {
        $urlParams  = [self::OPTION_COLLECTION => $this->makeCollection($collection)];

        $url      = UrlHelper::appendParamsUrl(Urls::URL_INDEX, $urlParams);
        $response = $this->getConnection()->post($url, $this->json_encode_wrapper($indexOptions));

        $httpCode = $response->getHttpCode();
        switch ($httpCode) {
            case 404:
                throw new ClientException('Collection is unknown');
                break;
            case 400:
                throw new ClientException('cannot create unique index due to documents violating uniqueness');
                break;
        }

        return $response->getJson();
    }


    /**
     * Get the information about an index in a collection
     *
     * @param string $collection - the id of the collection
     * @param string $indexId    - the id of the index
     *
     * @return array
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public function getIndex($collection, $indexId)
    {
        $url      = UrlHelper::buildUrl(Urls::URL_INDEX, [$collection, $indexId]);
        $response = $this->getConnection()->get($url);

        return $response->getJson();
    }


    /**
     * Get indexes of a collection
     *
     * This will throw if the collection cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collection - collection as string or object
     *
     * @return array $data - the indexes result-set from the server
     */
    public function getIndexes($collection)
    {
        $urlParams = [self::OPTION_COLLECTION => $this->makeCollection($collection)];
        $url       = UrlHelper::appendParamsUrl(Urls::URL_INDEX, $urlParams);
        $response  = $this->getConnection()->get($url);

        return $response->getJson();
    }

    /**
     * Drop an index
     *
     * @throws Exception
     *
     * @param mixed $collection - collection as string or object
     * @param mixed $indexHandle - index handle (collection name / index id)
     *
     * @return bool - always true, will throw if there is an error
     */
    public function dropIndex($collection, $indexHandle = null)
    {
        if ($indexHandle === null) {
            $handle = explode('/', $collection);
        } else {
            $handle = [ $this->makeCollection($collection), $indexHandle ];
        }

        if (count($handle) > 2) {
          throw new ClientException('Invalid index handle');
        }

        $this->getConnection()->delete(UrlHelper::buildUrl(Urls::URL_INDEX, $handle));

        return true;
    }
    
    
    /**
     * Get the responsible shard for a document
     *
     * @throws Exception
     *
     * @param mixed $collection - collection as string or object
     * @param mixed $document   - document
     *
     * @return string - shard id
     * @since 3.5
     */
    public function getResponsibleShard($collection, $document)
    {
        if (is_array($document)) {
            $data = $document;
        } else {
            $data = $document->getAll(['_includeInternals' => true ]);
        }

        $collection = $this->makeCollection($collection);
        $url        = UrlHelper::buildUrl(Urls::URL_COLLECTION, [$collection, self::OPTION_RESPONSIBLE_SHARD]);
        $response = $this->getConnection()->put($url, $this->json_encode_wrapper($data));
        $data     = $response->getJson();

        return $data['shardId'];
    }
    
    
    /**
     * Get the shards of a collection
     *
     * @throws Exception
     *
     * @param mixed $collection - collection as string or object
     *
     * @return array - array with shard ids
     * @since 3.5
     */
    public function getShards($collection) 
    {
        $collection = $this->makeCollection($collection);
        $url        = UrlHelper::buildUrl(Urls::URL_COLLECTION, [$collection, self::OPTION_SHARDS]);
        $response = $this->getConnection()->get($url);
        $data     = $response->getJson();

        return $data['shards'];
    }

    /**
     * Get a random document from the collection.
     *
     * This will throw if the document cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collection - collection as string or object
     *
     * @return Document - the document fetched from the server
     * @since 1.2
     */
    public function any($collection)
    {
        $_documentClass = $this->_documentClass;

        $data = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
        ];

        $response = $this->getConnection()->put(Urls::URL_ANY, $this->json_encode_wrapper($data));
        $data     = $response->getJson();

        if ($data['document']) {
            return $_documentClass::createFromArray($data['document']);
        }

        return null;
    }


    /**
     * Returns all documents of a collection
     *
     * @param mixed $collection       - collection as string or object
     * @param array $options          - optional array of options.
     *                                <p>Options are :<br>
     *                                <li>'_sanitize'         - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                <p>
     *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document.<br>
     *                                The difference is, that if you're returning a result set of documents, the getAll() is already called<br>
     *                                and the hidden attributes would not be applied to the attributes.<br>
     *                                </p>
     *
     * <li>'batchSize' - can optionally be used to tell the server to limit the number of results to be transferred in one batch</li>
     * <li>'skip'      -  Optional, The number of documents to skip in the query.</li>
     * <li>'limit'     -  Optional, The maximal amount of documents to return. 'skip' is applied before the limit restriction.</li>
     * </li>
     * </p>
     *
     * @return Cursor - documents
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public function all($collection, array $options = [])
    {
        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                self::OPTION_LIMIT => null,
                self::OPTION_SKIP  => null,
            ]
        );
        
        $body[self::OPTION_STREAM] = true;

        $response = $this->getConnection()->put(Urls::URL_ALL, $this->json_encode_wrapper($body));
        
        if ($batchPart = $response->getBatchPart()) {
            return $batchPart;
        }

        $options = array_merge(['_documentClass' => $this->_documentClass], $options);

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Get the list of all documents' ids from a collection
     *
     * This will throw if the list cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed $collection - collection id as string or number
     *
     * @return array - ids of documents in the collection
     */
    public function getAllIds($collection)
    {
        $params   = [
            self::OPTION_COLLECTION => $this->makeCollection($collection)
        ];
        $response = $this->getConnection()->put(Urls::URL_ALL_KEYS, $this->json_encode_wrapper($params));

        $data = $response->getJson();
        if (!isset($data[Cursor::ENTRY_RESULT])) {
            throw new ClientException('Got an invalid document list from the server');
        }

        $cursor = new Cursor($this->getConnection(), $response->getJson(), ['_documentClass' => $this->_documentClass]);
        $ids    = [];
        foreach ($cursor->getAll() as $location) {
            $ids[] = UrlHelper::getDocumentIdFromLocation($location);
        }

        return $ids;
    }

    /**
     * Get document(s) by specifying an example
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed $collection        - collection as string or object
     * @param mixed $document          - the example document as a Document object or an array
     * @param array $options           - optional, prior to v1.0.0 this was a boolean value for sanitize, since v1.0.0 it's an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>'_sanitize'         - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                 <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                 <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a result set of documents, the getAll() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     *                                 </p>
     *                                 </li>
     *                                 <li>'batchSize' - can optionally be used to tell the server to limit the number of results to be transferred in one batch</li>
     *                                 <li>'skip'      - Optional, The number of documents to skip in the query.</li>
     *                                 <li>'limit'     - Optional, The maximal amount of documents to return. 'skip' is applied before the limit restriction.</li>
     *                                 </p>
     *
     * @return cursor - Returns a cursor containing the result
     */
    public function byExample($collection, $document, array $options = [])
    {
        $_documentClass = $this->_documentClass;

        if (is_array($document)) {
            $document = $_documentClass::createFromArray($document, $options);
        }

        if (!($document instanceof Document)) {
            throw new ClientException('Invalid example document specification');
        }

        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_EXAMPLE    => $document->getAllAsObject(['_ignoreHiddenAttributes' => true])
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                ConnectionOptions::OPTION_BATCHSIZE => $this->getConnectionOption(
                    ConnectionOptions::OPTION_BATCHSIZE
                ),
                self::OPTION_LIMIT                  => null,
                self::OPTION_SKIP                   => null,
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_EXAMPLE, $this->json_encode_wrapper($body));

        if ($batchPart = $response->getBatchPart()) {
            return $batchPart;
        }

        $options['isNew'] = false;

        $options = array_merge(['_documentClass' => $this->_documentClass], $options);

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
     * @param mixed $collection        - collection as string or object
     * @param mixed $document          - the example document as a Document object or an array
     * @param array $options           - optional, an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>'_sanitize'         - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                 <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                 <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a result set of documents, the getAll() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     *                                 </p>
     *                                 </li>
     *                                 </p>
     *
     * @return Document - the document fetched from the server
     * @since 1.2
     */
    public function firstExample($collection, $document, array $options = [])
    {
        $_documentClass = $this->_documentClass;

        if (is_array($document)) {
            $document = $_documentClass::createFromArray($document, $options);
        }

        if (!($document instanceof Document)) {
            throw new ClientException('Invalid example document specification');
        }

        $data = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_EXAMPLE    => $document->getAll(['_ignoreHiddenAttributes' => true])
        ];

        $response = $this->getConnection()->put(Urls::URL_FIRST_EXAMPLE, $this->json_encode_wrapper($data));
        
        if ($batchPart = $response->getBatchPart()) {
            return $batchPart;
        }
        
        $data     = $response->getJson();

        $options['_isNew'] = false;

        return $_documentClass::createFromArray($data['document'], $options);
    }


    /**
     * Get document(s) by a fulltext query
     *
     * This will find all documents from the collection that match the fulltext query specified in query.
     * In order to use the fulltext operator, a fulltext index must be defined for the collection and the specified attribute.
     *
     *
     * @throws Exception
     *
     * @param mixed $collection        - collection id as string or number
     * @param mixed $attribute         - The attribute that contains the texts.
     * @param mixed $query             - The fulltext query.
     * @param array $options           - optional, prior to v1.0.0 this was a boolean value for sanitize, since v1.0.0 it's an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>'_sanitize'         - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                 <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                 <p>
     *                                 This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                 The difference is, that if you're returning a result set of documents, the getAll() is already called <br>
     *                                 and the hidden attributes would not be applied to the attributes.<br>
     *                                 </p>
     *                                 </li>
     *                                 <li>'batchSize' - can optionally be used to tell the server to limit the number of results to be transferred in one batch</li>
     *                                 <li>'skip'      - Optional, The number of documents to skip in the query.</li>
     *                                 <li>'limit'     - Optional, The maximal amount of documents to return. 'skip' is applied before the limit restriction.</li>
     *                                 <li>'index'     - If given, the identifier of the fulltext-index to use.</li>
     *                                 </p>
     *
     * @deprecated use AQL queries instead
     *
     * @return cursor - Returns a cursor containing the result
     */
    public function fulltext($collection, $attribute, $query, array $options = [])
    {
        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_ATTRIBUTE  => $attribute,
            self::OPTION_QUERY      => $query,
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                ConnectionOptions::OPTION_BATCHSIZE => $this->getConnectionOption(
                    ConnectionOptions::OPTION_BATCHSIZE
                ),
                self::OPTION_LIMIT                  => null,
                self::OPTION_SKIP                   => null,
                self::OPTION_INDEX                  => null,
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_FULLTEXT, $this->json_encode_wrapper($body));

        $options['_isNew'] = false;

        $options = array_merge(['_documentClass' => $this->_documentClass], $options);

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Update document(s) matching a given example
     *
     * This will update the document(s) on the server
     *
     * This will throw if the document cannot be updated
     *
     * @throws Exception
     *
     * @param mixed $collection   - collection as string or number
     * @param mixed $example      - the example document as a Document object or an array
     * @param mixed $newValue     - patch document or array which contains the attributes and values to be updated
     * @param mixed $options      - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                            <p>Options are :
     *                            <li>'keepNull'    - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     *                            <li>'waitForSync' - can be used to force synchronisation of the document update operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                            <li>'limit'       - can be used set a limit on how many documents to update at most. If limit is specified but is less than the number of documents in the collection, it is undefined which of the documents will be updated.</li>
     *                            </p>
     *
     * @deprecated use AQL queries instead
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function updateByExample($collection, $example, $newValue, array $options = [])
    {
        $_documentClass = $this->_documentClass;

        if (is_array($example)) {
            $example = $_documentClass::createFromArray($example);
        }

        if (is_array($newValue)) {
            $newValue = $_documentClass::createFromArray($newValue);
        }

        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_EXAMPLE    => $example->getAllAsObject(['_ignoreHiddenAttributes' => true]),
            self::OPTION_NEW_VALUE  => $newValue->getAllAsObject(['_ignoreHiddenAttributes' => true])
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                ConnectionOptions::OPTION_WAIT_SYNC => $this->getConnectionOption(
                    ConnectionOptions::OPTION_WAIT_SYNC
                ),
                'keepNull'                          => true,
                self::OPTION_LIMIT                  => null,
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_UPDATE_BY_EXAMPLE, $this->json_encode_wrapper($body));

        $responseArray = $response->getJson();

        if ($responseArray['error'] === true) {
            throw new ClientException('Invalid example document specification');
        }

        return $responseArray['updated'];
    }


    /**
     * Replace document(s) matching a given example
     *
     * This will replace the document(s) on the server
     *
     * This will throw if the document cannot be replaced
     *
     * @throws Exception
     *
     * @param mixed $collection   - collection as string or object
     * @param mixed $example      - the example document as a Document object or an array
     * @param mixed $newValue     - patch document or array which contains the attributes and values to be replaced
     * @param mixed $options      - optional, array of options (see below) or the boolean value for $policy (for compatibility prior to version 1.1 of this method)
     *                            <p>Options are :
     *                            <li>'keepNull'    - can be used to instruct ArangoDB to delete existing attributes instead setting their values to null. Defaults to true (keep attributes when set to null)</li>
     *                            <li>'waitForSync' - can be used to force synchronisation of the document replace operation to disk even in case that the waitForSync flag had been disabled for the entire collection</li>
     *                            <li>'limit'       - can be used set a limit on how many documents to replace at most. If limit is specified but is less than the number of documents in the collection, it is undefined which of the documents will be replaced.</li>
     *                            </p>
     *
     * @deprecated use AQL queries instead
     *
     * @return bool - always true, will throw if there is an error
     * @since 1.2
     */
    public function replaceByExample($collection, $example, $newValue, array $options = [])
    {
        $_documentClass = $this->_documentClass;

        if (is_array($example)) {
            $example = $_documentClass::createFromArray($example);
        }

        if (is_array($newValue)) {
            $newValue = $_documentClass::createFromArray($newValue);
        }

        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_EXAMPLE    => $example->getAllAsObject(['_ignoreHiddenAttributes' => true]),
            self::OPTION_NEW_VALUE  => $newValue->getAllAsObject(['_ignoreHiddenAttributes' => true])
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                ConnectionOptions::OPTION_WAIT_SYNC => $this->getConnectionOption(
                    ConnectionOptions::OPTION_WAIT_SYNC
                ),
                'keepNull'                          => true,
                self::OPTION_LIMIT                  => null,
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_REPLACE_BY_EXAMPLE, $this->json_encode_wrapper($body));

        $responseArray = $response->getJson();

        if ($responseArray['error'] === true) {
            throw new ClientException('Invalid example document specification');
        }

        return $responseArray['replaced'];
    }


    /**
     * Remove document(s) by specifying an example
     *
     * This will throw on any error
     *
     * @throws Exception
     *
     * @param mixed $collection        - collection as string or object
     * @param mixed $document          - the example document as a Document object or an array
     * @param array $options           - optional - an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>
     *                                 'waitForSync' -  if set to true, then all removal operations will instantly be synchronised to disk.<br>
     *                                 If this is not specified, then the collection's default sync behavior will be applied.
     *                                 </li>
     *                                 </p>
     *                                 <li>'limit' -  Optional, The maximal amount of documents to return. 'skip' is applied before the limit restriction.</li>
     *
     * @return int - number of documents that were deleted
     *
     * @since 1.2
     */
    public function removeByExample($collection, $document, array $options = [])
    {
        $_documentClass = $this->_documentClass;

        if (is_array($document)) {
            $document = $_documentClass::createFromArray($document, $options);
        }

        if (!($document instanceof Document)) {
            throw new ClientException('Invalid example document specification');
        }

        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_EXAMPLE    => $document->getAllAsObject(['_ignoreHiddenAttributes' => true])
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                ConnectionOptions::OPTION_WAIT_SYNC => $this->getConnectionOption(
                    ConnectionOptions::OPTION_WAIT_SYNC
                ),
                self::OPTION_LIMIT                  => null,
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_REMOVE_BY_EXAMPLE, $this->json_encode_wrapper($body));

        $responseArray = $response->getJson();

        if ($responseArray['error'] === true) {
            throw new ClientException('Invalid example document specification');
        }

        return $responseArray['deleted'];
    }

    /**
     * Remove document(s) by specifying an array of keys
     *
     * This will throw on any error
     *
     * @throws Exception
     *
     * @param mixed $collection        - collection as string or object
     * @param array $keys              - array of document keys
     * @param array $options           - optional - an array of options.
     *                                 <p>Options are :<br>
     *                                 <li>
     *                                 'waitForSync' -  if set to true, then all removal operations will instantly be synchronised to disk.<br>
     *                                 If this is not specified, then the collection's default sync behavior will be applied.
     *                                 </li>
     *                                 </p>
     *
     * @return array - an array containing an attribute 'removed' with the number of documents that were deleted, an an array 'ignored' with the number of not removed keys/documents
     *
     * @since 2.6
     */
    public function removeByKeys($collection, array $keys, array $options = [])
    {
        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_KEYS       => $keys
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                ConnectionOptions::OPTION_WAIT_SYNC => $this->getConnectionOption(
                    ConnectionOptions::OPTION_WAIT_SYNC
                )
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_REMOVE_BY_KEYS, $this->json_encode_wrapper($body));
        
        if ($batchPart = $response->getBatchPart()) {
            return $batchPart;
        }

        $responseArray = $response->getJson();

        return [
            'removed' => $responseArray['removed'],
            'ignored' => $responseArray['ignored']
        ];
    }


    /**
     * Bulk lookup documents by specifying an array of keys
     *
     * This will throw on any error
     *
     * @throws Exception
     *
     * @param mixed $collection          - collection as string or object
     * @param array $keys                - array of document keys
     * @param array $options             - optional array of options.
     *                                   <p>Options are :<br>
     *                                   <li>'_sanitize'         - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                   <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                   </p>
     *
     * @return array - an array containing all documents found for the keys specified.
     *                 note that if for a given key not document is found, it will not be returned nor will the document's non-existence be reported.
     *
     * @since 2.6
     */
    public function lookupByKeys($collection, array $keys, array $options = [])
    {
        $_documentClass = $this->_documentClass;

        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_KEYS       => $keys
        ];

        $response = $this->getConnection()->put(Urls::URL_LOOKUP_BY_KEYS, $this->json_encode_wrapper($body));

        $responseArray = $response->getJson();

        $result = [];
        foreach ($responseArray['documents'] as $document) {
            $result[] = $_documentClass::createFromArray($document, $options);
        }

        return $result;
    }


    /**
     * Get document(s) by specifying range
     *
     * This will throw if the list cannot be fetched from the server
     *
     *
     * @throws Exception
     *
     * @param mixed  $collection      - collection as string or object
     * @param string $attribute       - the attribute path , like 'a', 'a.b', etc...
     * @param mixed  $left            - The lower bound.
     * @param mixed  $right           - The upper bound.
     * @param array  $options         - optional array of options.
     *                                <p>Options are :<br>
     *                                <li>'_sanitize'         - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                <p>
     *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document.<br>
     *                                The difference is, that if you're returning a result set of documents, the getAll() is already called<br>
     *                                and the hidden attributes would not be applied to the attributes.<br>
     *                                </p>
     *
     *                                <li>'closed'    - If true, use interval including left and right, otherwise exclude right, but include left.
     *                                <li>'batchSize' - can optionally be used to tell the server to limit the number of results to be transferred in one batch</li>
     *                                <li>'skip'      -  Optional, The number of documents to skip in the query.</li>
     *                                <li>'limit'     -  Optional, The maximal amount of documents to return. 'skip' is applied before the limit restriction.</li>
     *                                </li>
     *                                </p>
     *
     * @deprecated use AQL queries instead
     *
     * @return Cursor - documents matching the example [0...n]
     */
    public function range($collection, $attribute, $left, $right, array $options = [])
    {
        if ($attribute === '') {
            throw new ClientException('Invalid attribute specification');
        }

        if (strpos($attribute, '.') !== false) {
            // split attribute name
            $attribute = explode('.', $attribute);
        }

        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_ATTRIBUTE  => $attribute,
            self::OPTION_LEFT       => $left,
            self::OPTION_RIGHT      => $right
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                self::OPTION_CLOSED => null,
                self::OPTION_LIMIT  => null,
                self::OPTION_SKIP   => null,
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_RANGE, $this->json_encode_wrapper($body));

        $options = array_merge(['_documentClass' => $this->_documentClass], $options);

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }


    /**
     * Get document(s) by specifying near
     *
     * This will throw if the list cannot be fetched from the server
     *
     * @throws Exception
     *
     * @param mixed  $collection      - collection as string or object
     * @param double $latitude        - The latitude of the coordinate.
     * @param double $longitude       - The longitude of the coordinate.
     * @param array  $options         - optional array of options.
     *                                <p>Options are :<br>
     *                                <li>'_sanitize'         - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                <p>
     *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. <br>
     *                                The difference is, that if you're returning a result set of documents, the getAll() is already called <br>
     *                                and the hidden attributes would not be applied to the attributes.<br>
     *                                </p>
     *
     *                                <li>'distance'  - If given, the attribute key used to store the distance. (optional)
     *                                <li>'batchSize' - can optionally be used to tell the server to limit the number of results to be transferred in one batch</li>
     *                                <li>'skip'      -  Optional, The number of documents to skip in the query.</li>
     *                                <li>'limit'     -  Optional, The maximal amount of documents to return. 'skip' is applied before the limit restriction.</li>
     *                                </li>
     *                                </p>
     *
     * @deprecated use AQL queries instead
     *
     * @return Cursor - documents matching the example [0...n]
     */
    public function near($collection, $latitude, $longitude, array $options = [])
    {
        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_LATITUDE   => $latitude,
            self::OPTION_LONGITUDE  => $longitude
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                self::OPTION_DISTANCE => null,
                self::OPTION_LIMIT    => null,
                self::OPTION_SKIP     => null,
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_NEAR, $this->json_encode_wrapper($body));

        $options = array_merge(['_documentClass' => $this->_documentClass], $options);

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
     * @param mixed  $collection      - collection as string or object
     * @param double $latitude        - The latitude of the coordinate.
     * @param double $longitude       - The longitude of the coordinate.
     * @param int    $radius          - The maximal radius (in meters).
     * @param array  $options         - optional array of options.
     *                                <p>Options are :<br>
     *                                <li>'_sanitize'         - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
     *                                <li>'_hiddenAttributes' - Set an array of hidden attributes for created documents.
     *                                <p>
     *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document.<br>
     *                                The difference is, that if you're returning a result set of documents, the getAll() is already called <br>
     *                                and the hidden attributes would not be applied to the attributes.<br>
     *                                </p>
     *
     *                                <li>'distance'  - If given, the attribute key used to store the distance. (optional)
     *                                <li>'batchSize' - can optionally be used to tell the server to limit the number of results to be transferred in one batch</li>
     *                                <li>'skip'      -  Optional, The number of documents to skip in the query.</li>
     *                                <li>'limit'     -  Optional, The maximal amount of documents to return. 'skip' is applied before the limit restriction.</li>
     *                                </li>
     *                                </p>
     *
     * @deprecated use AQL queries instead
     *
     * @return Cursor - documents matching the example [0...n]
     */
    public function within($collection, $latitude, $longitude, $radius, array $options = [])
    {
        $body = [
            self::OPTION_COLLECTION => $this->makeCollection($collection),
            self::OPTION_LATITUDE   => $latitude,
            self::OPTION_LONGITUDE  => $longitude,
            self::OPTION_RADIUS     => $radius
        ];

        $body = $this->includeOptionsInBody(
            $options,
            $body,
            [
                self::OPTION_DISTANCE => null,
                self::OPTION_LIMIT    => null,
                self::OPTION_SKIP     => null,
            ]
        );

        $response = $this->getConnection()->put(Urls::URL_WITHIN, $this->json_encode_wrapper($body));

        $options = array_merge(['_documentClass' => $this->_documentClass], $options);

        return new Cursor($this->getConnection(), $response->getJson(), $options);
    }

    /**
     * @param $collection
     * @param $options
     */
    private function lazyCreateCollection($collection, $options)
    {
        $collectionOptions = [];
        if (isset($options['createCollectionType'])) {
            if ($options['createCollectionType'] === 'edge' ||
                $options['createCollectionType'] === 3
            ) {
                // edge collection
                $collectionOptions['type'] = 3;
            } else {
                // document collection
                $collectionOptions['type'] = 2;
            }
        }

        try {
            // attempt to create the collection
            $this->create($collection, $collectionOptions);
        } catch (Exception $e) {
            // collection may have existed already
        }
    }
}

class_alias(CollectionHandler::class, '\triagens\ArangoDb\CollectionHandler');
