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
        $response = $this->adminHandler->getServerVersion();
        $this->assertTrue(is_string($response), 'Version must be a string!');
    }


    /**
     * Test if we can get the server version
     */
    public function testGetServerTime()
    {
        $response = $this->adminHandler->getServerTime();
        $this->assertTrue(is_double($response), 'Time must be a double!');
    }


    /**
     * Test if we can get the server log
     * Rather dumb tests just checking that an array is returned
     */
    public function testGetServerLog()
    {
        $response = $this->adminHandler->getServerLog();
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('lid', $response);
        $this->assertArrayHasKey('level', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('text', $response);
        $this->assertArrayHasKey('totalAmount', $response);

        $options  = array('upto' => 3);
        $response = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('lid', $response);
        $this->assertArrayHasKey('level', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('text', $response);
        $this->assertArrayHasKey('totalAmount', $response);

        $options  = array('level' => 1);
        $response = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('lid', $response);
        $this->assertArrayHasKey('level', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('text', $response);
        $this->assertArrayHasKey('totalAmount', $response);

        $options  = array('search' => 'ArangoDB');
        $response = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('lid', $response);
        $this->assertArrayHasKey('level', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('text', $response);
        $this->assertArrayHasKey('totalAmount', $response);

        $options  = array('sort' => 'desc');
        $response = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('lid', $response);
        $this->assertArrayHasKey('level', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('text', $response);
        $this->assertArrayHasKey('totalAmount', $response);

        $options  = array('start' => 1);
        $response = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('lid', $response);
        $this->assertArrayHasKey('level', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('text', $response);
        $this->assertArrayHasKey('totalAmount', $response);

        $options  = array('size' => 10, 'offset' => 10);
        $response = $this->adminHandler->getServerLog($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('lid', $response);
        $this->assertArrayHasKey('level', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('text', $response);
        $this->assertArrayHasKey('totalAmount', $response);
    }


    /**
     * Test if we can get the server connection-statistics
     */
    public function testGetServerStatus()
    {
        $response = $this->adminHandler->getServerStatus();
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('system', $response);
        $system = $response['system'];
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
        $response = $this->adminHandler->flushServerModuleCache();
        $this->assertFalse($response['error'], 'Should be false!');
    }


    /**
     * Test if we can get the server version
     */
    public function testReloadServerRouting()
    {
        $response = $this->adminHandler->reloadServerRouting();
        $this->assertFalse($response['error'], 'Should be false!');
    }


    /**
     * Test if we can get the server connection-statistics
     */
    public function testGetServerConnectionStatistics()
    {
        $response = $this->adminHandler->getServerConnectionStatistics();
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('resolution', $response);
        $this->assertArrayHasKey('start', $response);
        $this->assertArrayHasKey('length', $response);
        $this->assertArrayHasKey('totalLength', $response);
        $this->assertArrayHasKey('httpConnections', $response);
        $this->assertArrayHasKey('httpDuration', $response);


        $options  = array('granularity' => 'hours');
        $response = $this->adminHandler->getServerConnectionStatistics($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('resolution', $response);
        $this->assertArrayHasKey('start', $response);
        $this->assertArrayHasKey('length', $response);
        $this->assertArrayHasKey('totalLength', $response);
        $this->assertArrayHasKey('httpConnections', $response);
        $this->assertArrayHasKey('httpDuration', $response);


        $options  = array('figures' => 'httpConnections');
        $response = $this->adminHandler->getServerConnectionStatistics($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('resolution', $response);
        $this->assertArrayHasKey('start', $response);
        $this->assertArrayHasKey('length', $response);
        $this->assertArrayHasKey('totalLength', $response);
        $this->assertArrayHasKey('httpConnections', $response);
        $this->assertArrayHasKey('httpDuration', $response);


        $options  = array('length' => 'all');
        $response = $this->adminHandler->getServerConnectionStatistics($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('resolution', $response);
        $this->assertArrayHasKey('start', $response);
        $this->assertArrayHasKey('length', $response);
        $this->assertArrayHasKey('totalLength', $response);
        $this->assertArrayHasKey('httpConnections', $response);
        $this->assertArrayHasKey('httpDuration', $response);
    }


    /**
     * Test if we can get the server request-statistics
     */
    public function testGetServerRequestStatistics()
    {
        $response = $this->adminHandler->getServerRequestStatistics();
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('resolution', $response);
        $this->assertArrayHasKey('start', $response);
        $this->assertArrayHasKey('length', $response);
        $this->assertArrayHasKey('totalLength', $response);
        $this->assertArrayHasKey('totalTime', $response);
        $this->assertArrayHasKey('bytesSent', $response);
        $this->assertArrayHasKey('bytesReceived', $response);


        $options  = array('granularity' => 'hours');
        $response = $this->adminHandler->getServerRequestStatistics($options);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('resolution', $response);
        $this->assertArrayHasKey('start', $response);
        $this->assertArrayHasKey('length', $response);
        $this->assertArrayHasKey('totalLength', $response);
        $this->assertArrayHasKey('totalTime', $response);
        $this->assertArrayHasKey('bytesSent', $response);
        $this->assertArrayHasKey('bytesReceived', $response);


        $options  = array('figures' => 'totalTime,queueTime,requestTime');
        $response = $this->adminHandler->getServerRequestStatistics($options);

        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('resolution', $response);
        $this->assertArrayHasKey('start', $response);
        $this->assertArrayHasKey('length', $response);
        $this->assertArrayHasKey('totalLength', $response);
        $this->assertArrayHasKey('totalTime', $response);
        $this->assertArrayHasKey('queueTime', $response);


        $options  = array('length' => 10);
        $response = $this->adminHandler->getServerRequestStatistics($options);
        #var_dump($response);
        $this->assertTrue(is_array($response), 'Should be an array');
        $this->assertArrayHasKey('resolution', $response);
        $this->assertArrayHasKey('start', $response);
        $this->assertArrayHasKey('length', $response);
        $this->assertArrayHasKey('totalLength', $response);
        $this->assertArrayHasKey('totalTime', $response);
        $this->assertArrayHasKey('bytesSent', $response);
        $this->assertArrayHasKey('bytesReceived', $response);
    }


    public function tearDown()
    {
        unset($this->adminHandler);
        unset($this->connection);
    }
}
