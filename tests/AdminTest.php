<?php
/**
 * ArangoDB PHP client testsuite
 * File: AdminTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * @property Connection   connection
 * @property AdminHandler adminHandler
 */
class AdminTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection   = getConnection();
        $this->adminHandler = new AdminHandler($this->connection);
    }


    /**
     * Test if we can get the server version
     */
    public function testGetServerVersion()
    {
        $result = $this->adminHandler->getServerVersion();
        $this->assertTrue(is_string($result), 'Version must be a string!');
    }

    /**
     * Test if we can get the server version with details
     */
    public function testGetServerVersionWithDetails()
    {
        $result = $this->adminHandler->getServerVersion(true);
        $this->assertInternalType('array', $result, "The server version details must be an array!");
        $this->assertInternalType(
             'array',
             $result['details'],
             "The server version details must have a `details` array!"
        );

        // intentionally dumping the result, so that we have a bit more info about the Arango build we're testing in the log.
        var_dump($result);

        $details = $result['details'];
        $this->assertArrayHasKey('build-date', $details);
        $this->assertArrayHasKey('configure', $details);
        $this->assertArrayHasKey('icu-version', $details);
        //        $this->assertArrayHasKey('libev-version', $details);
        $this->assertArrayHasKey('openssl-version', $details);
        $this->assertArrayHasKey('server-version', $details);
        $this->assertArrayHasKey('v8-version', $details);
    }

    /**
     * Test if we can get the server version
     */
    public function testGetServerTime()
    {
        $result = $this->adminHandler->getServerTime();
        $this->assertTrue(is_double($result), 'Time must be a double!');
    }


    /**
     * Test if we can get the server log
     * Rather dumb tests just checking that an array is returned
     */
    public function testGetServerLog()
    {
        $result = $this->adminHandler->getServerLog();
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('lid', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('totalAmount', $result);

        $options = array('upto' => 3);
        $result  = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('lid', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('totalAmount', $result);

        $options = array('level' => 1);
        $result  = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('lid', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('totalAmount', $result);

        $options = array('search' => 'ArangoDB');
        $result  = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('lid', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('totalAmount', $result);

        $options = array('sort' => 'desc');
        $result  = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('lid', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('totalAmount', $result);

        $options = array('start' => 1);
        $result  = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('lid', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('totalAmount', $result);

        $options = array('size' => 10, 'offset' => 10);
        $result  = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('lid', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('totalAmount', $result);
    }


    /**
     * Test if we can get the server version
     */
    public function testServerModuleCache()
    {
        $result = $this->adminHandler->flushServerModuleCache();
        $this->assertTrue($result, 'Should be true!');
    }


    /**
     * Test if we can get the server version
     */
    public function testReloadServerRouting()
    {
        $result = $this->adminHandler->reloadServerRouting();
        $this->assertTrue($result, 'Should be true!');
    }


    /**
     * Test if we can get the server statistics
     */
    public function testGetServerStatistics()
    {
        $result = $this->adminHandler->getServerStatistics();
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('system', $result);
        $system = $result['system'];
        $this->assertArrayHasKey('minorPageFaults', $system);
        $this->assertArrayHasKey('majorPageFaults', $system);
        $this->assertArrayHasKey('userTime', $system);
        $this->assertArrayHasKey('systemTime', $system);
        $this->assertArrayHasKey('numberOfThreads', $system);
        $this->assertArrayHasKey('residentSize', $system);
        $this->assertArrayHasKey('virtualSize', $system);
        $this->assertArrayHasKey('client', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
    }


    /**
     * Test if we can get the server statistics-description
     */
    public function testGetServerStatisticsDescription()
    {
        $result = $this->adminHandler->getServerStatisticsDescription();
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('groups', $result);
        $this->assertArrayHasKey('figures', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
    }


    public function tearDown()
    {
        unset($this->adminHandler);
        unset($this->connection);
    }
}
