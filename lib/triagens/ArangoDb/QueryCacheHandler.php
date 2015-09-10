<?php

/**
 * ArangoDB PHP client: AQL query result cache handling
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2015, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

class QueryCacheHandler extends
    Handler
{
    
    /**
     * Globally turns on the AQL query result cache
     *
     * @throws Exception
     */
    public function enable() 
    {
        $url = UrlHelper::buildUrl(Urls::URL_QUERY_CACHE, array("properties"));
        $this->getConnection()->put($url, $this->json_encode_wrapper(array("mode" => "on")));
    }
    
    
    /**
     * Globally turns off the AQL query result cache
     *
     * @throws Exception
     */
    public function disable() 
    {
        $url = UrlHelper::buildUrl(Urls::URL_QUERY_CACHE, array("properties"));
        $this->getConnection()->put($url, $this->json_encode_wrapper(array("mode" => "off")));
    }

    
    /**
     * Globally sets the AQL query result cache to demand mode
     *
     * @throws Exception
     */
    public function enableDemandMode() 
    {
        $url = UrlHelper::buildUrl(Urls::URL_QUERY_CACHE, array("properties"));
        $this->getConnection()->put($url, $this->json_encode_wrapper(array("mode" => "demand")));
    }

    /**
     * Clears the AQL query result cache for the current database
     *
     * @throws Exception
     */
    public function clear() 
    {
        $url = UrlHelper::buildUrl(Urls::URL_QUERY_CACHE, array());
        $this->getConnection()->delete($url);
    }

    /**
     * Returns the AQL query result cache properties
     *
     * @throws Exception
     *
     * @return array
     */
    public function getProperties() 
    {
        $url      = UrlHelper::buildUrl(Urls::URL_QUERY_CACHE);
        $response = $this->getConnection()->get($url);

        $result = $response->getJson();
        return $result;
    }

    /**
     * Adjusts the global AQL query result cache properties
     *
     * @throws Exception
     *
     * @param  array $properties - the query result cache properties. 
     *                             the following properties can be used:
     *                             - maxResults: maximum number of results
     *                               that the query result cache will hold
     *                               per database
     *                             - mode: turns the query result cache on or off,
     *                               or sets it to demand mode. Possible values are
     *                               "on", "off", or "demand".
     *                               
     * @return array
     */
    public function setProperties(array $properties) 
    {
        $bodyParams = $properties;

        $url      = UrlHelper::buildUrl(Urls::URL_QUERY_CACHE);
        $response = $this->getConnection()->put($url, $this->json_encode_wrapper($bodyParams));

        $result = $response->getJson();
        return $result;
    }
}
