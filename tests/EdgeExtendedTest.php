<?php
/**
 * ArangoDB PHP client testsuite
 * File: EdgeExtendedTest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

class EdgeExtendedTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->collection        = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection);
        $this->edgeHandler = new EdgeHandler($this->connection);
    }


    /**
     * Test for correct exception codes if nonexistant objects are tried to be gotten, replaced, updated or removed
     */
    public function testGetReplaceUpdateAndRemoveOnNonExistantObjects()
    {
        // Setup objects
        $edgeHandler = $this->edgeHandler;
        $edge        = Edge::createFromArray(
            array(
                 'someAttribute'      => 'someValue', 'someOtherAttribute' => 'someOtherValue',
                 'someThirdAttribute' => 'someThirdValue'
            )
        );


        // Try to get a non-existent edge out of a nonexistent collection
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $edgeHandler->get('nonExistantCollection', 'nonexistantId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to get a non-existent edge out of an existent collection
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $edgeHandler->get($this->collection->getId(), 'nonexistantId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to update a non-existent edge
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $edgeHandler->updateById($this->collection->getId(), 'nonexistantId', $edge);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to replace a non-existent edge
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $edgeHandler->replaceById($this->collection->getId(), 'nonexistantId', $edge);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to remove a non-existent edge
        // This should cause an exception with a code of 404
        try {
            unset ($e);
            $result1 = $edgeHandler->removeById($this->collection->getId(), 'nonexistantId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
    }


    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
