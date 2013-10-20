<?php

/**
 * ArangoDB PHP client: endpoint
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Endpoint specification
 *
 * An endpoint contains the server location the client connects to
 * the following endpoint types are currently supported (more to be added later):
 * <ul>
 * <li> tcp://host:port for tcp connections
 * <li> unix://socket for UNIX sockets (provided the server supports this)
 * <li> ssl://host:port for SSL connections (provided the server supports this)
 * </ul>
 *
 * Note: SSL support is added in ArangoDB server 1.1<br>
 *
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class Endpoint
{
    /**
     * Current endpoint value
     *
     * @var string
     */
    private $_value;

    /**
     * TCP endpoint type
     */
    const TYPE_TCP = 'tcp';

    /**
     * SSL endpoint type
     */
    const TYPE_SSL = 'ssl';

    /**
     * UNIX socket endpoint type
     */
    const TYPE_UNIX = 'unix';

    /**
     * Regexp for TCP endpoints
     */
    const REGEXP_TCP = '/^tcp:\/\/(.+?):(\d+)\/?$/';

    /**
     * Regexp for SSL endpoints
     */
    const REGEXP_SSL = '/^ssl:\/\/(.+?):(\d+)\/?$/';

    /**
     * Regexp for UNIX socket endpoints
     */
    const REGEXP_UNIX = '/^unix:\/\/(.+)$/';

    /**
     * Endpoint index
     */
    const ENTRY_ENDPOINT = 'endpoint';

    /**
     * Databases index
     */
    const ENTRY_DATABASES = 'databases';


    /**
     * Create a new endpoint
     *
     * @param string $value - endpoint specification
     *
     * @throws ClientException
     * @return \triagens\ArangoDb\Endpoint
     */
    public function __construct($value)
    {
        if (!self::isValid($value)) {
            throw new ClientException(sprintf("invalid endpoint specification '%s'", $value));
        }

        $this->_value = $value;
    }

    /**
     * Return a string representation of the endpoint
     *
     * @return string - string representation of the endpoint
     */
    public function __toString()
    {
        return $this->_value;
    }

    /**
     * Return the type of an endpoint
     *
     * @param string $value - endpoint specification value
     *
     * @return string - endpoint type
     */
    public static function getType($value)
    {
        if (preg_match(self::REGEXP_TCP, $value)) {
            return self::TYPE_TCP;
        }

        if (preg_match(self::REGEXP_SSL, $value)) {
            return self::TYPE_SSL;
        }

        if (preg_match(self::REGEXP_UNIX, $value)) {
            return self::TYPE_UNIX;
        }

        return null;
    }

    /**
     * Return the host name of an endpoint
     *
     * @param string $value - endpoint specification value
     *
     * @return string - host name
     */
    public static function getHost($value)
    {
        if (preg_match(self::REGEXP_TCP, $value, $matches)) {
            return $matches[1];
        }

        if (preg_match(self::REGEXP_SSL, $value, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * check whether an endpoint specification is valid
     *
     * @param string $value - endpoint specification value
     *
     * @return bool - true if endpoint specification is valid, false otherwise
     */
    public static function isValid($value)
    {
        if (!is_string($value)) {
            return false;
        }

        $type = self::getType($value);
        if ($type === null) {
            return false;
        }

        return true;
    }


    /**
     * creates an endpoint
     *
     * This creates a new endpoint<br>
     * This is an alias function to Endpoint::modify, as ArangoDB's API has one call to support both new and modify
     *
     * @param Connection $connection - the connection to be used
     * @param string     $endpoint   - the endpoint specification, e.g. tcp://127.0.0.1:8530
     * @param array      $databases  - a list of database names the endpoint is responsible for.
     *                               *
     *
     * @link http://www.arangodb.org/manuals/1.4/HttpEndpoint.html
     * @return array $responseArray - The response array.
     */
    public static function create(Connection $connection, $endpoint, array $databases)
    {
        return self::modify($connection, $endpoint, $databases);
    }


    /**
     * modifies an endpoint
     *
     * This will modify an existing or create a new endpoint.
     *
     * @param Connection $connection - the connection to be used
     * @param string     $endpoint   - the endpoint specification, e.g. tcp://127.0.0.1:8530
     * @param array      $databases  - a list of database names the endpoint is responsible for.
     *
     * @link http://www.arangodb.org/manuals/1.4/HttpEndpoint.html
     * @return array $responseArray - The response array.
     */
    public static function modify(Connection $connection, $endpoint, array $databases)
    {
        $payload = array(self::ENTRY_ENDPOINT => $endpoint, self::ENTRY_DATABASES => $databases);

        $response = $connection->post(Urls::URL_ENDPOINT, $connection->json_encode_wrapper($payload));

        $responseArray = $response->getJson();

        return $responseArray;
    }


    /**
     * Deletes an endpoint
     *
     * This will delete an existing endpoint.
     *
     * @param Connection $connection - the connection to be used
     * @param string     $endpoint   - the endpoint specification, e.g. tcp://127.0.0.1:8530
     *
     * @link                         http://www.arangodb.org/manuals/1.4/HttpEndpoint.html
     * @return array $responseArray - The response array.
     */
    public static function delete(Connection $connection, $endpoint)
    {
        $url = UrlHelper::buildUrl(Urls::URL_ENDPOINT, array($endpoint));

        $response = $connection->delete($url);

        $responseArray = $response->getJson();

        return $responseArray;
    }


    /**
     * List endpoints
     *
     * This will list the endpoints that are configured on the server
     *
     * @param Connection $connection - the connection to be used
     *
     * @link                         http://www.arangodb.org/manuals/1.4/HttpEndpoint.html
     * @return array $responseArray - The response array.
     */
    public static function listEndpoints(Connection $connection)
    {
        $response = $connection->get(Urls::URL_ENDPOINT);

        $responseArray = $response->getJson();

        return $responseArray;
    }
}
