<?php

/**
 * ArangoDB PHP client: URL helper methods
 *
 * @package   ArangoDbPhpClient
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Some helper methods to construct and process URLs
 *
 * @package ArangoDbPhpClient
 */
abstract class UrlHelper
{
    /**
     * Get the document id from a location header
     *
     * @param string $location - HTTP response location header
     *
     * @return string - document id parsed from header
     */
    public static function getDocumentIdFromLocation($location)
    {
        @list(, , , , $id) = explode('/', $location);

        return $id;
    }

    /**
     * Get the collection id from a location header
     *
     * @param string $location - HTTP response location header
     *
     * @return string - collection id parsed from header
     */
    public static function getCollectionIdFromLocation($location)
    {
        @list(, , , $id) = explode('/', $location);

        return $id;
    }

    /**
     * Construct a URL from a base URL and additional parts, seperated with '/' each
     *
     * This function accepts variable arguments.
     *
     * @param string $baseUrl - base URL
     *
     * @return string - assembled URL
     */
    public static function buildUrl($baseUrl)
    {
        $argv = func_get_args();
        $argc = count($argv);

        $url = $baseUrl;
        for ($i = 1; $i < $argc; ++$i) {
            $url .= '/' . urlencode($argv[$i]);
        }

        return $url;
    }

    /**
     * Append parameters to a URL
     *
     * Parameter values will be URL-encoded
     *
     * @param string $baseUrl - base URL
     * @param array  $params  - an array of parameters
     *
     * @return string - the assembled URL
     */
    public static function appendParamsUrl($baseUrl, array $params)
    {
        $url = $baseUrl . '?' . http_build_query($params);

        return $url;
    }

    /**
     * Get a string from a boolean value
     *
     * @param mixed $value - the value
     *
     * @return string - "true" if $value evaluates to true, "false" otherwise
     */
    public static function getBoolString($value)
    {
        return $value ? 'true' : 'false';
    }
}
