<?php

/**
 * ArangoDB PHP client testsuite
 * File: collectionbasictest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDb;

class CollectionBasicTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
    }
    
    /**
     * Test default collection type
     */
    public function testDefaultCollectionType()
    {
        $this->assertEquals(Collection::TYPE_DOCUMENT, Collection::getDefaultType());
    }

    /**
     * Test if Collection and CollectionHandler instances can be initialized
     */
    public function testInitializeCollection()
    {
        $connection = $this->connection;
        $collection = new \triagens\ArangoDb\Collection();
        $this->assertInstanceOf('triagens\ArangoDb\Collection', $collection);
        $collectionHandler = new \triagens\ArangoDb\CollectionHandler($connection);
        $this->assertInstanceOf('triagens\ArangoDb\Collection', $collection);
    }
    
    /**
     * Test setting and getting collection types
     */
    public function testInitializeCollectionWithDocumentType()
    {
        $collection = new \triagens\ArangoDb\Collection();
        $collection->setType(Collection::TYPE_DOCUMENT);

        $this->assertEquals(Collection::TYPE_DOCUMENT, $collection->getType());
    }
    
    /**
     * Test setting and getting collection types
     */
    public function testInitializeCollectionWithEdgeType()
    {
        $collection = new \triagens\ArangoDb\Collection();
        $collection->setType(Collection::TYPE_EDGE);

        $this->assertEquals(Collection::TYPE_EDGE, $collection->getType());
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
        #$collection->properties();

        $this->assertTrue(is_numeric($response), 'Did not return a numeric id!');

        $resultingCollection = $collectionHandler->get($response);

        $resultingAttribute = $resultingCollection->getName();
        $this->assertTrue($name === $resultingAttribute, 'The created collection name and resulting collection name do not match!');

        $this->assertEquals(Collection::getDefaultType(), $resultingCollection->getType());

        $response = $collectionHandler->delete($collection);
    }
    
    public function tearDown()
    {
        unset($this->connection);
    }
}
