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
class CollectionHandler {
  /**
   * Connection object
   * @param Connection
   */
  private $_connection;

  /**
   * URL base part for all collection-related REST calls
   */
  const URL              = '/_api/collection';

  /**
   * count option
   */
  const OPTION_COUNT     = 'count';
  
  /**
   * figures option
   */
  const OPTION_FIGURES   = 'figures';

  /**
   * Construct a new collection handler
   *
   * @param Connection $connection - connection to be used
   * @return void
   */
  public function __construct(Connection $connection) {
    $this->_connection = $connection;
  }
  
  /**
   * Get information about a collection
   * This will throw if the collection cannot be fetched from the server
   *
   * @throws Exception
   * @param mixed $collectionId - collection id as a string or number
   * @return Collection - the collection fetched from the server
   */
  public function get($collectionId) {
    $url = UrlHelper::buildUrl(self::URL, $collectionId);
    $response = $this->_connection->get($url);

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
    $url = UrlHelper::buildUrl(self::URL, $collectionId, self::OPTION_COUNT);
    $response = $this->_connection->get($url);

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
    $url = UrlHelper::buildUrl(self::URL, $collectionId, self::OPTION_FIGURES);
    $response = $this->_connection->get($url);

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
      $collection->setWaitForSync($this->_connection->getOption(ConnectionOptions::OPTION_WAIT_SYNC));
    }

    $params = array(Collection::ENTRY_NAME => $collection->getName(), Collection::ENTRY_WAIT_SYNC => $collection->getWaitForSync());
    $response = $this->_connection->post(self::URL, json_encode($params));

    $location = $response->getHeader('location');
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

    $result = $this->_connection->delete(UrlHelper::buildUrl(self::URL, $collectionId));

    return true;
  }

}
