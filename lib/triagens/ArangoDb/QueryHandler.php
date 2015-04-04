<?php

/**
 * ArangoDB PHP client: query handling
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2015, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

class QueryHandler
{
    /**
     * The connection object
     *
     * @var Connection
     */
    private $_connection = null;

    /**
     * Initialize the query handler
     *
     * @param Connection $connection - the connection to be used
     */
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Return the connection object
     *
     * @return Connection - the connection object
     */
    protected function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Clears the list of slow queries
     *
     * @throws Exception
     */
    public function clearSlow() 
    {
        $url      = UrlHelper::buildUrl(Urls::URL_QUERY, array('slow'));
        $response = $this->_connection->delete($url);
    }

    /**
     * Returns the list of slow queries
     *
     * @throws Exception
     *
     * @return array
     */
    public function getSlow() 
    {
        $url      = UrlHelper::buildUrl(Urls::URL_QUERY, array('slow'));
        $response = $this->_connection->get($url);

        $result = $response->getJson();
        return $result;
    }

    /**
     * Returns the list of currently executing queries
     *
     * @throws Exception
     *
     * @return array
     */
    public function getCurrent() 
    {
        $url      = UrlHelper::buildUrl(Urls::URL_QUERY, array('current'));
        $response = $this->_connection->get($url);

        $result = $response->getJson();
        return $result;
    }

    /**
     * Kills a specific query
     *
     * This will send an HTTP DELETE command to the server to terminate the specified query
     *
     * @param string $id - query id
     *
     * @throws Exception
     *
     * @return bool
     */
    public function kill($id) 
    {
        $url      = UrlHelper::buildUrl(Urls::URL_QUERY, array($id));
        $this->_connection->delete($url);

        return true;
    }

}
