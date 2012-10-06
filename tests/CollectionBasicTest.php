<?php

/**
 * ArangoDB PHP client testsuite
 * File: collectionbasictest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDB;

class CollectionBasicTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
    }

    /**
     * Test if Collection and CollectionHandler instances can be initialized
     */
    public function testInitializeCollection()
    {
        $connection = $this->connection;
        $collection = new \triagens\ArangoDb\Collection();
        $this->assertInstanceOf('triagens\ArangoDB\Collection', $collection);
        $collectionHandler = new \triagens\ArangoDb\CollectionHandler($connection);
        $this->assertInstanceOf('triagens\ArangoDB\Collection', $collection);
    }

    /**
     * Try to create and delete a collection
     */
    public function testCreateAndDeleteCollection()
    {
        $connection = $this->connection;
        $collection = new \triagens\ArangoDb\Collection();
        $collectionHandler = new \triagens\ArangoDb\CollectionHandler($connection);

        $name = 'ArangoDB-PHP-TestSuite-TestCollection-01';
        $collection->setName($name);

        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Did not return a numeric id!');

        $resultingCollection = $collectionHandler->get($response);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue($name === $resultingAttribute, 'The created collection name and resulting collection name do not match!');

        $response = $collectionHandler->delete($collection);
    }

    public function tearDown()
    {
        unset($this->connection);
    }
}
