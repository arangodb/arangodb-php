<?php
/**
 * ArangoDB PHP client testsuite
 * File: QueueTest.php
 *
 * @package triagens\ArangoDb
 * @author  Jan Steemann
 */

namespace triagens\ArangoDb;

/**
 * @property Connection   connection
 * @property AdminHandler adminHandler
 */
class QueueTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection   = getConnection();
        $this->adminHandler = new AdminHandler($this->connection);
    }


    /**
     * Execute an operation and expect its success
     */
    private function expectSuccess() {
        $result = $this->adminHandler->getServerVersion();
        $this->assertTrue(is_string($result), 'Version must be a string!');
    }


    /**
     * Execute the same operation but now expect its failure
     * Note that the operation itself should be valid, but it won't succeed
     * if the queue specification is wrong
     */
    private function expectFailure() {
        try {
            $this->adminHandler->getServerVersion();
            $this->assertFalse(true);
        }
        catch (\Exception $e) {
        }
    }


    /**
     * Test with custom queue disabled
     */
    public function testNoQueue()
    {
        $this->connection->disableCustomQueue();
        $this->expectSuccess();
    }

    /**
     * Test with an invalid custom queue
     */
    public function testInvalidQueue()
    {
        $this->connection->enableCustomQueue("foobar");
        $this->expectFailure();
    }

    /**
     * Test with and without custom queues
     */
    public function testDisableEnableQueue1()
    {
        $this->expectSuccess();

        $this->connection->enableCustomQueue("foobar");
        $this->expectFailure();

        $this->connection->disableCustomQueue();
        $this->expectSuccess();
    }

    /**
     * Test with and without custom queues
     */
    public function testDisableEnableQueue2()
    {
        $this->expectSuccess();

        $this->connection->enableCustomQueue("foobar", 1);
        $this->expectFailure();

        $this->expectSuccess();

        $this->connection->enableCustomQueue("foobaz", null);
        $this->expectFailure();
        $this->expectFailure();

        $this->connection->disableCustomQueue();
        $this->expectSuccess();
    }

    /**
     * Test with queue counter
     */
    public function testQueueCounted()
    {
        $this->expectSuccess();

        $this->connection->enableCustomQueue("foobar", 5);
        for ($i = 0; $i < 5; ++$i) {
            $this->expectFailure();
        }

        $this->expectSuccess();
    }

    public function tearDown()
    {
        $this->connection->disableCustomQueue();
        unset($this->adminHandler);
        unset($this->connection);
    }
}
