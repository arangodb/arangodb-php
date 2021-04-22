<?php
/**
 * ArangoDB PHP client testsuite
 * File: AdminTest.php
 *
 * @package ArangoDBClient
 * @author  Frank Mayer
 */

namespace ArangoDBClient;

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
     * Test if we can get the storage engine
     */
    public function testEngine()
    {
        $result = $this->adminHandler->getEngine();
        static::assertEquals("rocksdb", $result["name"]);
        static::assertTrue(isset($result["supports"]));
    }
    
    
    /**
     * Test if we can get the storage engine statistics
     */
    public function testEngineStats()
    {
        $result = $this->adminHandler->getEngineStats();
        static::assertTrue(is_array($result));

        if (isCluster($this->connection)) {
            foreach ($result as $server => $entry) {
                static::assertTrue(isset($entry["cache.limit"]));
                static::assertTrue(isset($entry["cache.allocated"]));
                static::assertTrue(isset($entry["columnFamilies"]));
            }
        } else {
            static::assertTrue(isset($result["cache.limit"]));
            static::assertTrue(isset($result["cache.allocated"]));
            static::assertTrue(isset($result["columnFamilies"]));
        }
    }


    /**
     * Test if we can get the server version
     */
    public function testGetServerVersion()
    {
        $result = $this->adminHandler->getServerVersion();
        static::assertTrue(is_string($result), 'Version must be a string!');
    }

    /**
     * Test if we can get the server version with details
     */
    public function testGetServerVersionWithDetails()
    {
        $result = $this->adminHandler->getServerVersion(true);
        static::assertInternalType('array', $result, 'The server version details must be an array!');
        static::assertInternalType(
            'array',
            $result['details'],
            'The server version details must have a `details` array!'
        );

        // intentionally dumping the result, so that we have a bit more info about the Arango build we're testing in the log.

        $details = $result['details'];
        static::assertArrayHasKey('build-date', $details);
        static::assertArrayHasKey('icu-version', $details);
        static::assertArrayHasKey('openssl-version-compile-time', $details);
        static::assertArrayHasKey('server-version', $details);
        static::assertArrayHasKey('v8-version', $details);
    }

    /**
     * Test if we can get the server version
     */
    public function testGetServerTime()
    {
        $result = $this->adminHandler->getServerTime();
        static::assertTrue(is_float($result), 'Time must be a double (float)!');
    }
    
    
    /**
     * Test if we can get the server log
     * Rather dumb tests just checking that an array is returned
     */
    public function testGetServerLogEntries()
    {
        $result = $this->adminHandler->getServerLogEntries();
        static::assertTrue(is_array($result['messages']), 'Should be an array');

        foreach ($result['messages'] as $entry) {
            static::assertArrayHasKey('id', $entry);
            static::assertArrayHasKey('topic', $entry);
            static::assertArrayHasKey('level', $entry);
            static::assertArrayHasKey('date', $entry);
            static::assertArrayHasKey('message', $entry);
        }
    }


    /**
     * Test if we can get the server log
     * Rather dumb tests just checking that an array is returned
     */
    public function testGetServerLog()
    {
        $result = $this->adminHandler->getServerLog();
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('lid', $result);
        static::assertArrayHasKey('level', $result);
        static::assertArrayHasKey('timestamp', $result);
        static::assertArrayHasKey('text', $result);
        static::assertArrayHasKey('totalAmount', $result);

        $options = ['upto' => 3];
        $result  = $this->adminHandler->getServerLog($options);
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('lid', $result);
        static::assertArrayHasKey('level', $result);
        static::assertArrayHasKey('timestamp', $result);
        static::assertArrayHasKey('text', $result);
        static::assertArrayHasKey('totalAmount', $result);

        $options = ['level' => 1];
        $result  = $this->adminHandler->getServerLog($options);
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('lid', $result);
        static::assertArrayHasKey('level', $result);
        static::assertArrayHasKey('timestamp', $result);
        static::assertArrayHasKey('text', $result);
        static::assertArrayHasKey('totalAmount', $result);

        $options = ['search' => 'ArangoDB'];
        $result  = $this->adminHandler->getServerLog($options);
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('lid', $result);
        static::assertArrayHasKey('level', $result);
        static::assertArrayHasKey('timestamp', $result);
        static::assertArrayHasKey('text', $result);
        static::assertArrayHasKey('totalAmount', $result);

        $options = ['sort' => 'desc'];
        $result  = $this->adminHandler->getServerLog($options);
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('lid', $result);
        static::assertArrayHasKey('level', $result);
        static::assertArrayHasKey('timestamp', $result);
        static::assertArrayHasKey('text', $result);
        static::assertArrayHasKey('totalAmount', $result);

        $options = ['start' => 1];
        $result  = $this->adminHandler->getServerLog($options);
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('lid', $result);
        static::assertArrayHasKey('level', $result);
        static::assertArrayHasKey('timestamp', $result);
        static::assertArrayHasKey('text', $result);
        static::assertArrayHasKey('totalAmount', $result);

        $options = ['size' => 10, 'offset' => 10];
        $result  = $this->adminHandler->getServerLog($options);
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('lid', $result);
        static::assertArrayHasKey('level', $result);
        static::assertArrayHasKey('timestamp', $result);
        static::assertArrayHasKey('text', $result);
        static::assertArrayHasKey('totalAmount', $result);
    }
    
    
    /**
     * Test if we can get the server metrics
     */
    public function testGetServerMetrics()
    {
        $result = $this->adminHandler->getServerMetrics();

        static::assertTrue(count($result) > 50, "must have at least 50 metrics");

        static::assertTrue(isset($result["arangodb_server_statistics_server_uptime_total"]));
        $metric = $result["arangodb_server_statistics_server_uptime_total"];
        static::assertEquals("arangodb_server_statistics_server_uptime_total", $metric["name"]);
        static::assertTrue(is_string($metric["help"]));
        static::assertEquals("counter", $metric["type"]);
        static::assertTrue(is_numeric($metric["value"]));
        static::assertTrue($metric["value"] > 0);

        static::assertTrue(isset($result["arangodb_client_connection_statistics_connection_time"]));
        $metric = $result["arangodb_client_connection_statistics_connection_time"];
        static::assertEquals("arangodb_client_connection_statistics_connection_time", $metric["name"]);
        static::assertTrue(is_string($metric["help"]));
        static::assertEquals("histogram", $metric["type"]);
        static::assertFalse(isset($metric["value"]));
        static::assertTrue(is_numeric($metric["count"]));
        static::assertTrue(is_numeric($metric["sum"]));
        static::assertTrue(is_array($metric["buckets"]));
    }


    /**
     * Test if we can get the server version
     */
    public function testReloadServerRouting()
    {
        $result = $this->adminHandler->reloadServerRouting();
        static::assertTrue($result, 'Should be true!');
    }


    /**
     * Test if we can get the server statistics
     */
    public function testGetServerStatistics()
    {
        $result = $this->adminHandler->getServerStatistics();
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('system', $result);
        $system = $result['system'];
        static::assertArrayHasKey('minorPageFaults', $system);
        static::assertArrayHasKey('majorPageFaults', $system);
        static::assertArrayHasKey('userTime', $system);
        static::assertArrayHasKey('systemTime', $system);
        static::assertArrayHasKey('numberOfThreads', $system);
        static::assertArrayHasKey('residentSize', $system);
        static::assertArrayHasKey('virtualSize', $system);
        static::assertArrayHasKey('client', $result);
        static::assertArrayHasKey('error', $result);
        static::assertArrayHasKey('code', $result);
    }


    /**
     * Test if we can get the server statistics-description
     */
    public function testGetServerStatisticsDescription()
    {
        $result = $this->adminHandler->getServerStatisticsDescription();
        static::assertTrue(is_array($result), 'Should be an array');
        static::assertArrayHasKey('groups', $result);
        static::assertArrayHasKey('figures', $result);
        static::assertArrayHasKey('error', $result);
        static::assertArrayHasKey('code', $result);
    }


    public function tearDown()
    {
        unset($this->adminHandler, $this->connection);
    }
}
