<?php

/**
 * ArangoDB PHP client: collection handler
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
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
class CollectionHandler extends Handler {
  
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
   * attribute parameter
   */
  const OPTION_ATTRIBUTE    = 'attribute';

  /**
   * left parameter
   */
  const OPTION_LEFT    = 'left';

  /**
   * right parameter
   */
  const OPTION_RIGHT    = 'right';

 /**
   * closed parameter
   */
  const OPTION_CLOSED    = 'closed';

 /**
   * latidude parameter
   */
  const OPTION_LATITUDE    = 'latitude';

 /**
   * longitude parameter
   */
  const OPTION_LONGITUDE    = 'longitude';

 /**
   * distance parameter
   */
  const OPTION_DISTANCE    = 'distance';

 /**
   * radius parameter
   */
  const OPTION_RADIUS    = 'radius';

 /**
   * skip parameter
   */
  const OPTION_SKIP    = 'skip';

 /**
   * limit parameter
   */
  const OPTION_LIMIT    = 'limit';

  /**
   * count fields
   */
  const OPTION_FIELDS     = 'fields';

  /**
   * count unique
   */
  const OPTION_UNIQUE     = 'unique';

  /**
   * count unique
   */
  const OPTION_TYPE     = 'type';

  /**
   * count option
   */
  const OPTION_COUNT     = 'count';

  /**
   * figures option
   */
  const OPTION_FIGURES   = 'figures';
  
  /**
   * truncate option
   */
  const OPTION_TRUNCATE  = 'truncate';
  
  /**
   * rename option
   */
  const OPTION_RENAME    = 'rename';

  /**
   * Get information about a collection
   * 
   * This will throw if the collection cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @return Collection - the collection fetched from the server
   */
  public function get($collectionId) {
    $url = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId);
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
   * @param mixed $collectionId - collection id as a string or number
   * @return int - the number of documents in the collection
   * 
   * @deprecated to be removed in version 2.0 - This function is being replaced by count()
   */
  public function getCount($collectionId) {
    return $this->count($collectionId);

  }
  
  
  /**
   * Get the number of documents in a collection
   * 
   * This will throw if the collection cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @return int - the number of documents in the collection
   */
  public function count($collectionId) {
    $url = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_COUNT);
    $response = $this->getConnection()->get($url);

    $data = $response->getJson();
    $count = $data[self::OPTION_COUNT];

    return (int) $count;
  }
  
  /**
   * Get figures for a collection
   * 
   * This will throw if the collection cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @return array - the figures for the collection
   * 
   * @deprecated to be removed in version 2.0 - This function is being replaced by figures()
   */
  public function getFigures($collectionId) {
    return $this->figures($collectionId);
  }
  
  /**
   * Get figures for a collection
   * 
   * This will throw if the collection cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @return array - the figures for the collection
   */
  public function figures($collectionId) {
    $url = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_FIGURES);
    $response = $this->getConnection()->get($url);

    $data = $response->getJson();
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
   * @param Collection $collection - collection object to be created on the server
   * @return mixed - id of collection created
   * 
   * @deprecated to be removed in version 2.0 - This function is being replaced by create()
   */
  public function add(Collection $collection) {
    return $this->create($collection);
  }

  /**
   * Creates a new collection on the server
   * 
   * This will add the collection on the server and return its id
   * 
   * This will throw if the collection cannot be created
   *
   * @throws Exception
   * @param Collection $collection - collection object to be created on the server
   * @return mixed - id of collection created
   */
  public function create(Collection $collection) {
    if ($collection->getWaitForSync() === NULL) {
      $collection->setWaitForSync($this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC));
    }

    $type = $collection->getType() ? $collection->getType() : Collection::getDefaultType();
    $params = array(Collection::ENTRY_NAME => $collection->getName(), Collection::ENTRY_TYPE => $type, Collection::ENTRY_WAIT_SYNC => $collection->getWaitForSync());
    $response = $this->getConnection()->post(Urls::URL_COLLECTION, json_encode($params));

    $location = $response->getLocationHeader();
    if (!$location) {
      throw new ClientException('Did not find location header in server response');
    }

    $id = UrlHelper::getCollectionIdFromLocation($location);
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
   * @param CollectionId $collectionId - The id of the collection where the index is to be created
   * @param Type $type - index type: hash, skiplist or geo 
   * @param Attributes $attributes - an array of attributes that can be defined like this ['a'] or ['a', 'b.c']
   * @param Unique $unique - true/false to create a unique index 
   * @return mixed - id of collection created
   */
  public function index($collectionId, $type="", $attributes=array(), $unique=false) {

    $urlParams = array(self::OPTION_COLLECTION => $collectionId);
    $bodyParams = array(self::OPTION_TYPE => $type, self::OPTION_FIELDS => $attributes, self::OPTION_UNIQUE => $unique);
    $url = UrlHelper::appendParamsUrl(Urls::URL_INDEX, $urlParams); 
    $response = $this->getConnection()->post($url, json_encode($bodyParams));

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
   * Delete a collection
   * 
   * @throws Exception
   * @param mixed $collection - collection id as string or number or collection object
   * @return bool - always true, will throw if there is an error
   * 
   * @deprecated to be removed in version 2.0 - This function is being replaced by drop()
   */
  public function delete($collection) {
    return $this->drop($collection);
  }
  
  /**
   * Drop a collection
   *
   * @throws Exception
   * @param mixed $collection - collection id as string or number or collection object
   * @return bool - always true, will throw if there is an error
   */
  public function drop($collection) {
    if ($collection instanceof Collection) {
      $collectionId = $collection->getId();
    }
    else {
      $collectionId = $collection;
    }

    if (!$collectionId || !(is_string($collectionId) || is_double($collectionId) || is_int($collectionId))) {
      throw new ClientException('Cannot alter a collection without a collection id');
    }

    $result = $this->getConnection()->delete(UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId));

    return true;
  }
  
  /**
   * Rename a collection
   *
   * @throws Exception
   * @param mixed $collection - collection id as string or number or collection object
   * @param string $name - new name for collection
   * @return bool - always true, will throw if there is an error
   */
  public function rename($collection, $name) {
    if ($collection instanceof Collection) {
      $collectionId = $collection->getId();
    }
    else {
      $collectionId = $collection;
    }

    if (!$collectionId || !(is_string($collectionId) || is_double($collectionId) || is_int($collectionId))) {
      throw new ClientException('Cannot alter a collection without a collection id');
    }

    $params = array(Collection::ENTRY_NAME => $newName);
    $result = $this->getConnection()->put(UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_RENAME), json_encode($params));

    return true;
  }

  /**
   * Truncate a collection
   * 
   * This will remove all documents from the collection but will leave the metadata and indexes intact.
   *
   * @throws Exception
   * @param mixed $collection - collection id as string or number or collection object
   * @return bool - always true, will throw if there is an error
   */
  public function truncate($collection) {
    if ($collection instanceof Collection) {
      $collectionId = $collection->getId();
    }
    else {
      $collectionId = $collection;
    }

    if (!$collectionId || !(is_string($collectionId) || is_double($collectionId) || is_int($collectionId))) {
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
   * @param mixed $collectionId - collection id as string or number
   * @param mixed $document - the example document as a Document object or an array
   * @param bool|array $options - optional, prior to v1.0.0 this was a boolean value for sanitize, since v1.0.0 it's an array of options.
   * <p>Options are : 
   * <li>'sanitize' - true to remove _id and _rev attributes from result documents. Defaults to false.</li>
   * <li>'hiddenAttributes' - set an array of hidden attributes for created documents.
   * <p>
   *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. 
   *                                The difference is, that if you're returning a resultset of documents, the getall() is already called 
   *                                and the hidden attributes would not be applied to the attributes.
   * </p>
   * </li>
   * </p>
   * 
   * @return array - documents matching the example [0...n]
   */
  public function byExample($collectionId, $document, $options = array()) {
    // This preserves compatibility for the old sanitize parameter.
    $sanitize=false;
    if (!is_array($options)){
      $sanitize = $options;
      $options=array();
    }else{
       $sanitize = array_key_exists('sanitize',$options) ? $options['sanitize'] : $sanitize;
    }
    $options=array_merge($options, $this->getCursorOptions($sanitize));
    if (is_array($document)) {
      $document = Document::createFromArray($document, $options);
    }

    if (!($document instanceof Document)) {
      throw new ClientException('Invalid example document specification');
    }
    
    $data = array(self::OPTION_COLLECTION => $collectionId, self::OPTION_EXAMPLE => $document->getAll(array('ignoreHiddenAttributes'=>true)));

    $response = $this->getConnection()->put(Urls::URL_EXAMPLE, json_encode($data));
    
    return new Cursor($this->getConnection(), $response->getJson(), $options );
  }  
  

  /**
   * Get document(s) by specifying range
   * 
   * This will throw if the list cannot be fetched from the server
   * 
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param string $attribute - the attribute path , like 'a', 'a.b', etc...
   * @param mixed $left - The lower bound.
   * @param mixed $right - The upper bound.
   * @param array $options - optional array of options.
   * <p>Options are : 
   * <li>'sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
   * <li>'hiddenAttributes' - Set an array of hidden attributes for created documents.
   * <p>
   *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. 
   *                                The difference is, that if you're returning a resultset of documents, the getall() is already called 
   *                                and the hidden attributes would not be applied to the attributes.
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
  public function range($collectionId, $attribute, $left, $right, $options = array()) {
    $closed = NULL;
    $skip = NULL;
    $limit = NULL;
    $sanitize = false;
    $options = array_merge($options, $this->getCursorOptions($sanitize));
    extract($options, EXTR_IF_EXISTS);

    if ($attribute ==='') {
      throw new ClientException('Invalid attribute specification');
    }
    
    $data = array(self::OPTION_COLLECTION => $collectionId, self::OPTION_ATTRIBUTE => $attribute, self::OPTION_LEFT => $left, self::OPTION_RIGHT => $right);
    if ($closed) {$data[self::OPTION_CLOSED] = $closed;};
    if ($skip) {$data[self::OPTION_SKIP] = $skip;};
    if ($limit) {$data[self::OPTION_LIMIT] = $limit;};
   
    $response = $this->getConnection()->put(Urls::URL_RANGE, json_encode($data));
    
    return new Cursor($this->getConnection(), $response->getJson(), $options );
  }    
  
  
  /**
   * Get document(s) by specifying near
   * 
   * This will throw if the list cannot be fetched from the server
   * 
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param double $latitude - The latitude of the coordinate.
   * @param double $longitude - The longitude of the coordinate.
   * @param array $options - optional array of options.
   * <p>Options are : 
   * <li>'sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
   * <li>'hiddenAttributes' - Set an array of hidden attributes for created documents.
   * <p>
   *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. 
   *                                The difference is, that if you're returning a resultset of documents, the getall() is already called 
   *                                and the hidden attributes would not be applied to the attributes.
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
  public function near($collectionId, $latitude, $longitude, $options = array()) {
    $distance = NULL;
    $skip = NULL;
    $limit = NULL;
    $sanitize = false;
    $options = array_merge($options, $this->getCursorOptions($sanitize));
    extract($options, EXTR_IF_EXISTS);
    
    $data = array(self::OPTION_COLLECTION => $collectionId, self::OPTION_LATITUDE => $latitude, self::OPTION_LONGITUDE => $longitude);
    if ($skip) {$data[self::OPTION_SKIP] = $skip;};
    if ($limit) {$data[self::OPTION_LIMIT] = $limit;};
    if ($distance) {$data[self::OPTION_DISTANCE] = $distance;};
    $response = $this->getConnection()->put(Urls::URL_NEAR, json_encode($data));

    return new Cursor($this->getConnection(), $response->getJson(), $options);
  }    
  
  
  /**
   * Get document(s) by specifying within
   * 
   * This will throw if the list cannot be fetched from the server
   * 
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as string or number
   * @param double $latitude - The latitude of the coordinate.
   * @param double $longitude - The longitude of the coordinate.
   * @param int $radius - The maximal radius (in meters).
   * @param array $options - optional array of options.
   * <p>Options are : 
   * <li>'sanitize' - True to remove _id and _rev attributes from result documents. Defaults to false.</li>
   * <li>'hiddenAttributes' - Set an array of hidden attributes for created documents.
   * <p>
   *                                This is actually the same as setting hidden attributes using setHiddenAttributes() on a document. 
   *                                The difference is, that if you're returning a resultset of documents, the getall() is already called 
   *                                and the hidden attributes would not be applied to the attributes.
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
  public function within($collectionId, $latitude, $longitude, $radius, $options = array()) {
    $distance = NULL;
    $skip = NULL;
    $limit = NULL;
    $sanitize = false;
    $options = array_merge($options, $this->getCursorOptions($sanitize));
    extract($options, EXTR_IF_EXISTS);
    
    $data = array(self::OPTION_COLLECTION => $collectionId, self::OPTION_LATITUDE => $latitude, self::OPTION_LONGITUDE => $longitude, self::OPTION_RADIUS => $radius);
    if ($skip) {$data[self::OPTION_SKIP] = $skip;};
    if ($limit) {$data[self::OPTION_LIMIT] = $limit;};
    if ($distance) {$data[self::OPTION_DISTANCE] = $distance;};
    $response = $this->getConnection()->put(Urls::URL_WITHIN, json_encode($data));

    return new Cursor($this->getConnection(), $response->getJson(), $options);
  }    
  
  
  /**
   * Get the list of all documents' ids from a collection
   * 
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
