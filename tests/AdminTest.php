<?php
/**
 * ArangoDB PHP client testsuite
 * File: statementtest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

class AdminTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection   = getConnection();
        $this->adminHandler = new \triagens\ArangoDb\AdminHandler($this->connection);
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
     * Test if we can get the server connection-statistics
     */
    public function testGetServerStatus()
    {
        $result = $this->adminHandler->getServerStatus();
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('system', $result);
        $system = $result['system'];
        $this->assertArrayHasKey('minorPageFaults', $system);
        $this->assertArrayHasKey('majorPageFaults', $system);
        $this->assertArrayHasKey('userTime', $system);
        $this->assertArrayHasKey('systemTime', $system);
        $this->assertArrayHasKey('numberThreads', $system);
        $this->assertArrayHasKey('residentSize', $system);
        $this->assertArrayHasKey('virtualSize', $system);
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
     * Test if we can get the server connection-statistics
     */
    public function testGetServerConnectionStatistics()
    {
        $result = $this->adminHandler->getServerConnectionStatistics();
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('resolution', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('totalLength', $result);
        $this->assertArrayHasKey('httpConnections', $result);
        $this->assertArrayHasKey('httpDuration', $result);

        $options = array('granularity' => 'hours');
        $result  = $this->adminHandler->getServerConnectionStatistics($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('resolution', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('totalLength', $result);
        $this->assertArrayHasKey('httpConnections', $result);
        $this->assertArrayHasKey('httpDuration', $result);

        $options = array('figures' => 'httpConnections');
        $result  = $this->adminHandler->getServerConnectionStatistics($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('resolution', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('totalLength', $result);
        $this->assertArrayHasKey('httpConnections', $result);
        $this->assertArrayHasKey('httpDuration', $result);

        $options = array('length' => 'all');
        $result  = $this->adminHandler->getServerConnectionStatistics($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('resolution', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('totalLength', $result);
        $this->assertArrayHasKey('httpConnections', $result);
        $this->assertArrayHasKey('httpDuration', $result);
    }

    /**
     * Test if we can get the server request-statistics
     */
    public function testGetServerRequestStatistics()
    {
        $result = $this->adminHandler->getServerRequestStatistics();
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('resolution', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('totalLength', $result);
        $this->assertArrayHasKey('totalTime', $result);
        $this->assertArrayHasKey('bytesSent', $result);
        $this->assertArrayHasKey('bytesReceived', $result);

        $options = array('granularity' => 'hours');
        $result  = $this->adminHandler->getServerRequestStatistics($options);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('resolution', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('totalLength', $result);
        $this->assertArrayHasKey('totalTime', $result);
        $this->assertArrayHasKey('bytesSent', $result);
        $this->assertArrayHasKey('bytesReceived', $result);

        $options = array('figures' => 'totalTime,queueTime,requestTime');
        $result  = $this->adminHandler->getServerRequestStatistics($options);

        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('resolution', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('totalLength', $result);
        $this->assertArrayHasKey('totalTime', $result);
        $this->assertArrayHasKey('queueTime', $result);

        $options = array('length' => 10);
        $result  = $this->adminHandler->getServerRequestStatistics($options);
        #var_dump($result);
        $this->assertTrue(is_array($result), 'Should be an array');
        $this->assertArrayHasKey('resolution', $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('totalLength', $result);
        $this->assertArrayHasKey('totalTime', $result);
        $this->assertArrayHasKey('bytesSent', $result);
        $this->assertArrayHasKey('bytesReceived', $result);
    }

    public function tearDown()
    {
        unset($this->adminHandler);
        unset($this->connection);
    }
}
