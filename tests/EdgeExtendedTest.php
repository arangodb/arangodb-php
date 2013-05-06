<?php
/**
 * ArangoDB PHP client testsuite
 * File: EdgeExtendedTest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class EdgeExtendedTest
 *
 * @property Connection        $connection
 * @property Collection        $collection
 * @property Collection        $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler   $documentHandler
 * @property EdgeHandler       $edgeHandler
 *
 * @package triagens\ArangoDb
 */
class EdgeExtendedTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        $this->collection        = new Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestEdgeCollection_01');
        $this->collectionHandler->add($this->collection);
        $this->edgeHandler = new EdgeHandler($this->connection);
    }


    /**
     * Test for correct exception codes if non-existent objects are tried to be gotten, replaced, updated or removed
     */
    public function testGetReplaceUpdateAndRemoveOnNonExistentObjects()
    {
        // Setup objects
        $edgeHandler = $this->edgeHandler;
        $edge        = Edge::createFromArray(
            array(
                 'someAttribute'      => 'someValue',
                 'someOtherAttribute' => 'someOtherValue',
                 'someThirdAttribute' => 'someThirdValue'
            )
        );


        // Try to get a non-existent edge out of a nonexistent collection
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $edgeHandler->get('nonExistentCollection', 'nonexistentId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to get a non-existent edge out of an existent collection
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $edgeHandler->get($this->collection->getId(), 'nonexistentId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to update a non-existent edge
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $edgeHandler->updateById($this->collection->getId(), 'nonexistentId', $edge);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to replace a non-existent edge
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $edgeHandler->replaceById($this->collection->getId(), 'nonexistentId', $edge);
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());


        // Try to remove a non-existent edge
        // This should cause an exception with a code of 404
        try {
            $e = null;
            $edgeHandler->removeById($this->collection->getId(), 'nonexistentId');
        } catch (\Exception $e) {
            // don't bother us... just give us the $e
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e);
        $this->assertTrue($e->getCode() == 404, 'Should be 404, instead got: ' . $e->getCode());
    }


    public function tearDown()
    {
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestEdgeCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestEdgeCollection_02');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
