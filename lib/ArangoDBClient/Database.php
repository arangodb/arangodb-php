<?php

/**
 * ArangoDB PHP client: single database
 *
 * @package   ArangoDBClient
 * @author    Frank Mayer
 * @copyright Copyright 2013, triagens GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 * A class for managing ArangoDB Databases
 *
 * This class provides functions to manage Databases through ArangoDB's Database API<br>
 *
 * @link      https://www.arangodb.com/docs/stable/http/database.html
 *
 * @package   ArangoDBClient
 * @since     1.4
 */
class Database
{
    /**
     * Databases index
     */
    const ENTRY_DATABASE_NAME = 'name';

    /**
     * Users index
     */
    const ENTRY_DATABASE_USERS = 'users';
    
    /**
     * Options index
     */
    const ENTRY_OPTIONS = 'options';

    /**
     * creates a database
     *
     * This creates a new database<br>
     *
     * @param Connection $connection - the connection to be used
     * @param string     $name       - database name, for example 'myDatabase'
     * @param array      $options    - extra options for new collections in this database.
     *                                 <p>Options are :<br>
     *                                 <li>'replicationFactor'</li>
     *                                 <li>'writeConcern'</li>
     *                                 <li>'sharding'</li>
     *
     * @link https://www.arangodb.com/docs/stable/http/database.html
     *
     * @return array $responseArray - The response array.
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public static function create(Connection $connection, $name, array $options = [])
    {
        $payload = [
            self::ENTRY_DATABASE_NAME  => $name,
            self::ENTRY_DATABASE_USERS => [
                [
                    'username' => $connection->getOption(ConnectionOptions::OPTION_AUTH_USER),
                    'passwd'   => $connection->getOption(ConnectionOptions::OPTION_AUTH_PASSWD)
                ]
            ],
        ];

        if (count($options) > 0) {
            $payload[self::ENTRY_OPTIONS] = $options;
        }

        $response = $connection->post(Urls::URL_DATABASE, $connection->json_encode_wrapper($payload));

        return $response->getJson();
    }


    /**
     * Deletes a database
     *
     * This will delete an existing database.
     *
     * @param Connection $connection - the connection to be used
     * @param string     $name       - the database specification, for example 'myDatabase'
     *
     * @link https://www.arangodb.com/docs/stable/http/database.html
     *
     * @return array $responseArray - The response array.
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public static function delete(Connection $connection, $name)
    {
        $url = UrlHelper::buildUrl(Urls::URL_DATABASE, [$name]);

        $response = $connection->delete($url);

        return $response->getJson();
    }


    /**
     * List databases
     *
     * This will list the databases that exist on the server
     *
     * @param Connection $connection - the connection to be used
     *
     * @link https://www.arangodb.com/docs/stable/http/database.html
     *
     * @return array $responseArray - The response array.
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public static function listDatabases(Connection $connection)
    {
        return self::databases($connection);
    }

    /**
     * List databases
     *
     * This will list the databases that exist on the server
     *
     * @param Connection $connection - the connection to be used
     *
     * @link https://www.arangodb.com/docs/stable/http/database.html
     *
     * @return array $responseArray - The response array.
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public static function databases(Connection $connection)
    {
        $response = $connection->get(Urls::URL_DATABASE);

        return $response->getJson();
    }

    /**
     * List user databases
     *
     * Retrieves the list of all databases the current user can access without
     * specifying a different username or password.
     *
     * @param Connection $connection - the connection to be used
     *
     * @link https://www.arangodb.com/docs/stable/http/database.html
     *
     * @return array $responseArray - The response array.
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public static function listUserDatabases(Connection $connection)
    {
        $url = UrlHelper::buildUrl(Urls::URL_DATABASE, ['user']);

        $response = $connection->get($url);

        return $response->getJson();
    }


    /**
     * Retrieves information about the current database
     *
     * This will get information about the currently used database from the server
     *
     * @param Connection $connection - the connection to be used
     *
     * @link https://www.arangodb.com/docs/stable/http/database.html
     *
     * @return array $responseArray - The response array.
     * @throws \ArangoDBClient\Exception
     * @throws \ArangoDBClient\ClientException
     */
    public static function getInfo(Connection $connection)
    {
        $url = UrlHelper::buildUrl(Urls::URL_DATABASE, ['current']);

        $response = $connection->get($url);

        return $response->getJson();
    }
}

class_alias(Database::class, '\triagens\ArangoDb\Database');
