<?php
/**
 * ArangoDB PHP client testsuite
 * File: Database.php
 *
 * @package ArangoDBClient
 * @author  Frank Mayer
 */

namespace ArangoDBClient;

/**
 * Class DatabaseTest
 * Basic Tests for the Database API implementation
 *
 * @property Connection $connection
 *
 * @package ArangoDBClient
 */
class CustomDocumentClassTest extends
    \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->connection = getConnection();

        // remove existing databases to make test repeatable
        $database = 'ArangoTestSuiteDatabaseTest03';
        try {
            Database::delete($this->connection, $database);
        } catch (Exception $e) {
        }

        Database::create($this->connection, $database);

        $this->collectionHandler = new CustomCollectionHandler($this->connection);
    }

    public function tearDown()
    {
        // clean up
        $database = ['ArangoTestSuiteDatabaseTest03'];
        try {
            Database::delete($this->connection, $database);
        } catch (Exception $e) {
        }

        unset($this->connection);
    }


}

/**
 * Class CustomCollectionHandler
 * @package ArangoDBClient
 */
class CustomCollectionHandler extends CollectionHandler {

}