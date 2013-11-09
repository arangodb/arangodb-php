<?php
/**
 * ArangoDB PHP client testsuite
 * File: Endpoint.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class EndpointTest
 * Basic Tests for the Endpoint API implementation
 *
 * @property Connection                    $connection
 *
 * @package triagens\ArangoDb
 */
class EndpointTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
    }


    /**
     * Test if Endpoints can be created for all databases
     */
    public function testCreateEndpointForAllDatabasesAndDeleteIt()
    {

        $endpoint  = 'tcp://127.0.0.1:8532';
        $databases = array();

        $response = Endpoint::create($this->connection, $endpoint, $databases);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );

        $response = Endpoint::delete($this->connection, $endpoint);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );
    }


    /**
     * Test if Endpoints can be created and listed
     */
    public function testCreateSecondEndpointForAllDatabasesGetListOfEndpointsAndDeleteItAgain()
    {

        $endpoint  = 'tcp://127.0.0.1:8532';
        $databases = array();

        $response = Endpoint::create($this->connection, $endpoint, $databases);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );


        $response = Endpoint::listEndpoints($this->connection);

        $this->assertCount(2, $response);


        $response = Endpoint::delete($this->connection, $endpoint);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );

        $response = Endpoint::listEndpoints($this->connection);

        $this->assertCount(1, $response);
    }


    /**
     * Test if Endpoints can be created for all databases
     */
    public function testCreateEndpointForSystemDatabaseOnlyModifyItAndDeleteIt()
    {

        $endpoint  = 'tcp://127.0.0.1:8532';
        $databases = array('_system');

        $response = Endpoint::create($this->connection, $endpoint, $databases);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );


        $databases = array();

        $response = Endpoint::modify($this->connection, $endpoint, $databases);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );


        $response = Endpoint::delete($this->connection, $endpoint);

        $this->assertTrue(
             $response['error'] == false,
             'result[\'error\'] Did not return false, instead returned: ' . print_r($response, 1)
        );
    }


    /**
     * Test if Endpoints can be created for all databases
     */
    public function testDeleteNonExistentEndpoint()
    {

        $endpoint = 'tcp://127.0.0.1:8532';


        // Try to get a non-existent document out of a nonexistent collection
        // This should cause an exception with a code of 404
        try {
            $e        = null;
            $response = Endpoint::delete($this->connection, $endpoint);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
    }


    public function tearDown()
    {

        unset($this->connection);
    }
}
