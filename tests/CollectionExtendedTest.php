<?php
/**
 * ArangoDB PHP client testsuite
 * File: collectionextendedtest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDB;

class CollectionExtendedTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
        $this->collection = new \triagens\ArangoDb\Collection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
    }

    /**
     * test for creation, get, and delete of a collection with waitForSync default value (no setting)
     */
    public function testCreateAndDeleteCollectionWithWaitForSyncDefault()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;

        $resultingAttribute = $collection->getWaitForSync();
        print_r($resultingAttribute);
        $this->assertTrue(null === $resultingAttribute, 'Default waitForSync in API should be NULL!');

        $collection->setName('ArangoDB-PHP-TestSuite-TestCollection-01');

        $response = $collectionHandler->add($collection);

        $this->assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($response);

        $resultingAttribute = $resultingCollection->getWaitForSync();
        $this->assertTrue(false === $resultingAttribute, 'Default Server waitForSync should return false!');

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

    /**
     * test for creation, get, and delete of a collection with waitForSync set to true
     */
    public function testCreateGetAndDeleteCollectionWithWaitForSyncTrue()
    {
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;

        $collection->setWaitForSync(true);
        $resultingAttribute = $collection->getWaitForSync();
        $this->assertTrue(true === $resultingAttribute, 'WaitForSync should be true!');
        $collection->setName('ArangoDB-PHP-TestSuite-TestCollection-01');

        $response = $collectionHandler->add($collection);

        $resultingCollection = $collectionHandler->get($response);

        $resultingAttribute = $resultingCollection->getWaitForSync();
        $this->assertTrue(true === $resultingAttribute, 'Server waitForSync should return true!');

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

    /**
     * test for creation, get, and delete of a collection given its settings through createFromArray()
     */
    public function testCreateGetAndDeleteCollectionThroughCreateFromArrayWithWaitForSyncTrue()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB-PHP-TestSuite-TestCollection-01', 'waitForSync' => true));
        $response = $collectionHandler->add($collection);

        $resultingCollection = $collectionHandler->get($response);

        $resultingAttribute = $resultingCollection->getWaitForSync();
        $this->assertTrue(true === $resultingAttribute, 'Server waitForSync should return true!');

        $response = $collectionHandler->delete($collection);
        $this->assertTrue(true === $response, 'Delete should return true!');
    }

    /**
     * test to set some attributes and get all attributes of the collection through getAll()
     */
    public function testGetAll()
    {
        $collection = Collection::createFromArray(array('name' => 'ArangoDB-PHP-TestSuite-TestCollection-01', 'waitForSync' => true));
        $result = $collection->getAll();

        $this->assertArrayHasKey('id', $result, 'Id field should exist, empty or with an id');
        $this->assertTrue(true === ($result['name'] == 'ArangoDB-PHP-TestSuite-TestCollection-01'), 'name should return ArangoDB-PHP-TestSuite-TestCollection-01!');
        $this->assertTrue(true === ($result['waitForSync'] == true), 'waitForSync should return true!');

    }

    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB-PHP-TestSuite-TestCollection-01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
