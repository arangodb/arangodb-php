<?php
/**
 * ArangoDB PHP client testsuite
 * File: Database.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class DatabaseTest
 * Basic Tests for the Database API implementation
 *
 * @property Connection $connection
 *
 * @package triagens\ArangoDb
 */
class DatabaseTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();

        // remove existing databases to make test repeatable
        $databases = array("ArangoTestSuiteDatabaseTest01", "ArangoTestSuiteDatabaseTest02");
        foreach ($databases as $database) {

            try {
                Database::delete($this->connection, $database);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Test if Databases can be created and deleted
     */
    public function testCreateDatabaseDeleteIt()
    {

        $database = 'ArangoTestSuiteDatabaseTest01';

        $response = Database::create($this->connection, $database);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );

        $response = Database::delete($this->connection, $database);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );

        $response = Database::listDatabases($this->connection);
        $this->assertArrayNotHasKey($database, array_flip($response['result']));
    }


    /**
     * Test if Databases can be created, if they can be listed, if they can be listed for the current user and deleted again
     */
    public function testCreateDatabaseGetListOfDatabasesAndDeleteItAgain()
    {

        $database = 'ArangoTestSuiteDatabaseTest01';

        $response = Database::create($this->connection, $database);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );


        $response = Database::listDatabases($this->connection);

        $this->assertArrayHasKey($database, array_flip($response['result']));

        $responseUser = Database::listUserDatabases($this->connection);

        $this->assertArrayHasKey($database, array_flip($responseUser['result']));


        $response = Database::delete($this->connection, $database);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );
    }


    /**
     * Test if Databases can be created, if they are listed and deleted again
     */
    public function testCreateDatabaseGetInfoOfDatabasesAndDeleteItAgain()
    {

        $database = 'ArangoTestSuiteDatabaseTest01';

        $response = Database::create($this->connection, $database);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );

        $this->connection->setDatabase($database);

        $response = Database::getInfo($this->connection);

        $this->assertTrue($response['result']['name'] == $database);


        $this->connection->setDatabase('_system');

        $response = Database::getInfo($this->connection);
        $this->assertTrue($response['result']['name'] == '_system');

        $response = Database::delete($this->connection, $database);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );
    }


    /**
     * Test if non-existent Databases can be deleted
     */
    public function testDeleteNonExistentDatabase()
    {

        $database = 'ArangoTestSuiteDatabaseTest01';


        // Try to get a non-existent document out of a nonexistent collection
        // This should cause an exception with a code of 404
        try {
            $e = null;
            Database::delete($this->connection, $database);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
    }


    /**
     * Test if Databases can still be created, if current is not _system
     */
    public function testCreateDatabaseSwitchToItAndCreateAnotherOne()
    {

        $database  = 'ArangoTestSuiteDatabaseTest01';
        $database2 = 'ArangoTestSuiteDatabaseTest02';

        $response = Database::create($this->connection, $database);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );


        // Try to create a database from within a non_system database

        $this->connection->setDatabase($database);

        $response = Database::getInfo($this->connection);
        $this->assertTrue($response['result']['name'] == $database);

        try {
            $e        = null;
            $response = Database::create($this->connection, $database2);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 403, 'Should be 403, instead got: ' . $e->getCode());


        $this->connection->setDatabase('_system');

        $response = Database::getInfo($this->connection);
        $this->assertTrue($response['result']['name'] == '_system');

        $response = Database::delete($this->connection, $database);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );
    }

    public function tearDown()
    {
        // clean up
        $databases = array("ArangoTestSuiteDatabaseTest01", "ArangoTestSuiteDatabaseTest02");
        foreach ($databases as $database) {

            try {
                Database::delete($this->connection, $database);
            } catch (Exception $e) {
            }
        }

        unset($this->connection);
    }
}
