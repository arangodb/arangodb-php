<?php

/**
 * AvocadoDB PHP client: collection handler
 * 
 * @package AvocadoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\Avocado;

/**
 * A collection handler that fetches collection data from the server and
 * creates collections on the server. It does so by issueing the 
 * appropriate HTTP requests to the server.
 *
 * @package AvocadoDbPhpClient
 */
class CollectionHandler extends Handler {
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
   * This will throw if the collection cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @return int - the number of documents in the collection
   */
  public function getCount($collectionId) {
    $url = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_COUNT);
    $response = $this->getConnection()->get($url);

    $data = $response->getJson();
    $count = $data[self::OPTION_COUNT];

    return (int) $count;
  }
  
  /**
   * Get figures for a collection
   * This will throw if the collection cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @return array - the figures for the collection
   */
  public function getFigures($collectionId) {
    $url = UrlHelper::buildUrl(Urls::URL_COLLECTION, $collectionId, self::OPTION_FIGURES);
    $response = $this->getConnection()->get($url);

    $data = $response->getJson();
    $figures = $data[self::OPTION_FIGURES];

    return $figures;
  }

  /**
   * Adds a new collection on the server
   * This will add the collection on the server and return its id
   * This will throw if the collection cannot be created
   *
   * @throws Exception
   * @param Collection $collection - collection object to be created on the server
   * @return mixed - id of collection created
   */
  public function add(Collection $collection) {
    if ($collection->getWaitForSync() === NULL) {
      $collection->setWaitForSync($this->getConnection()->getOption(ConnectionOptions::OPTION_WAIT_SYNC));
    }

    $params = array(Collection::ENTRY_NAME => $collection->getName(), Collection::ENTRY_WAIT_SYNC => $collection->getWaitForSync());
    $response = $this->getConnection()->post(Urls::URL_COLLECTION, json_encode($params));

    $location = $response->getLocationHeader();
    if (!$location) {
      throw new ClientException('Did not find location header in server response');
    }

    $id = UrlHelper::getDocumentIdFromLocation($location);
    $collection->setId($id);

    return $id;
  }

  /**
   * Delete a collection
   *
   * @throws Exception
   * @param mixed $collection - collection id as string or number or collection object
   * @return bool - always true, will throw if there is an error
   */
  public function delete($collection) {
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
}
