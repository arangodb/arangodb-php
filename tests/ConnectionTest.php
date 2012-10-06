<?php
/**
 * ArangoDB PHP client testsuite
 * File: connectiontest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDB;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test if Connection instance can be initialized
     */
    public function testInitializeConnection()
    {
        $connection = getConnection();
        $this->assertInstanceOf('triagens\ArangoDB\Connection', $connection);
    }

    /**
     * This is just a test to really test connectivity with the server before moving on to further tests.
     */
    public function testGetStatus()
    {
        $connection = getConnection();
        $response = $connection->get('/_admin/status');
        $this->assertTrue($response->getHttpCode() == 200, 'Did not return http code 200');
    }
}
