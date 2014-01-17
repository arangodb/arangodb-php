<?php
/**
 * ArangoDB PHP client testsuite
 * File: EdgeExtendedTest.php
 *
 * @package triagens\ArangoDb
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
        $this->edgeCollection    = new Collection();
        $this->edgeCollection->setName('ArangoDBPHPTestSuiteTestEdgeCollection01');
        $this->edgeCollection->set('type', 3);
        $this->collectionHandler->add($this->edgeCollection);
        $this->documentCollection    = new Collection();
        $this->documentCollection->setName('ArangoDBPHPTestSuiteTestCollection01');
        $this->collectionHandler->add($this->documentCollection);
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


    /**
     * test for updating a edge using update()
     */
    public function testUpdateEdge()
    {
        $connection      = $this->connection;
        $edgeHandler = new EdgeHandler($connection);


        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocument        = new Edge();

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';


        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();


        $edgeDocument->set('label', 'knows');
        $edgeId = $edgeHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            $edgeDocument
        );
        $this->assertTrue(is_numeric($edgeId), 'Did not return an id!');

        $edgeDocument->set('labels', 'anything');
        $result = $edgeHandler->update($edgeDocument);

        $this->assertTrue($result);

        $resultingEdge = $edgeHandler->get($edgeCollection->getId(), $edgeId);
        $this->assertObjectHasAttribute('_id', $resultingEdge, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingEdge->labels == 'anything'),
            'Should be :anything, is: ' . $resultingEdge->labels
        );
        $this->assertTrue(
            ($resultingEdge->label == 'knows'),
            'Should be :knows, is: ' . $resultingEdge->label
        );
        $response = $edgeHandler->delete($resultingEdge);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for updating a edge using update() with wrong encoding
     * We expect an exception here:
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testUpdateEdgeWithWrongEncoding()
    {
        $edgeHandler = $this->edgeHandler;

        $edge   = Edge::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $edgeId = $edgeHandler->add($this->collection->getId(), $edge);
        $edgeHandler->get($this->collection->getId(), $edgeId);
        $this->assertTrue(is_numeric($edgeId), 'Did not return an id!');

        $patchEdge = new Edge();
        $patchEdge->set('_id', $edge->getHandle());
        $patchEdge->set('_rev', $edge->getRevision());

        // inject wrong encoding
        $isoValue = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "someWrongEncodedValueü");

        $patchEdge->set('someOtherAttribute', $isoValue);
        $result = $edgeHandler->update($patchEdge);

        $this->assertTrue($result);

        $resultingEdge = $edgeHandler->get($this->collection->getId(), $edgeId);
        $this->assertObjectHasAttribute('_id', $resultingEdge, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingEdge->someAttribute == 'someValue'),
            'Should be :someValue, is: ' . $resultingEdge->someAttribute
        );
        $this->assertTrue(
            ($resultingEdge->someOtherAttribute == 'someOtherValue2'),
            'Should be :someOtherValue2, is: ' . $resultingEdge->someOtherAttribute
        );
        $response = $edgeHandler->delete($resultingEdge);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for updating a edge using update()
     */
    public function testUpdateEdgeDoNotKeepNull()
    {
        $connection      = $this->connection;
        $edgeHandler = new EdgeHandler($connection);


        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocument        = new Edge();

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';


        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();


        $edgeDocument->set('label', null);
        $edgeId = $edgeHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            $edgeDocument
        );
        $this->assertTrue(is_numeric($edgeId), 'Did not return an id!');

        $edgeDocument->set('labels', 'anything');
        $result = $edgeHandler->update($edgeDocument, array("keepNull" => false));

        $this->assertTrue($result);

        $resultingEdge = $edgeHandler->get($edgeCollection->getId(), $edgeId);
        $this->assertObjectHasAttribute('_id', $resultingEdge, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingEdge->label == null),
            'Should be : null, is: ' . $resultingEdge->label
        );
        $this->assertTrue(
            ($resultingEdge->labels == 'anything'),
            'Should be :anything, is: ' . $resultingEdge->labels
        );
        $response = $edgeHandler->delete($resultingEdge);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for replacing a edge using replace()
     */
    public function testReplaceEdge()
    {
        $connection      = $this->connection;
        $edgeHandler = new EdgeHandler($connection);


        $edgeCollection = $this->edgeCollection;

        $document1       = new Document();
        $document2       = new Document();
        $documentHandler = new DocumentHandler($connection);

        $edgeDocument        = new Edge();

        $document1->someAttribute = 'someValue1';
        $document2->someAttribute = 'someValue2';


        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document1);
        $documentHandler->add('ArangoDBPHPTestSuiteTestCollection01', $document2);
        $documentHandle1 = $document1->getHandle();
        $documentHandle2 = $document2->getHandle();


        $edgeDocument->set('label', null);
        $edgeDocument->set('labelt', "as");
        $edgeId = $edgeHandler->saveEdge(
            $edgeCollection->getName(),
            $documentHandle1,
            $documentHandle2,
            $edgeDocument
        );
        $this->assertTrue(is_numeric($edgeId), 'Did not return an id!');

        $edgePutDocument        = new Edge();
        $edgePutDocument->set('_id', $edgeDocument->getHandle());
        $edgePutDocument->set('_rev', $edgeDocument->getRevision());
        $edgePutDocument->set('labels', "as");
        $result = $edgeHandler->replace($edgePutDocument);

        $this->assertTrue($result);
        $resultingEdge = $edgeHandler->get($edgeCollection->getId(), $edgeId);

        $this->assertObjectHasAttribute('_id', $resultingEdge, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingEdge->label == null),
            'Should be :null, is: ' . $resultingEdge->label
        );
        $this->assertTrue(
            ($resultingEdge->labelt == null),
            'Should be :null, is: ' . $resultingEdge->labelt
        );

        $this->assertTrue($resultingEdge->labels == "as");

        $response = $edgeHandler->delete($resultingEdge);
        $this->assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for replacing a edge using replace() with wrong encoding
     * We expect an exception here:
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testReplaceEdgeWithWrongEncoding()
    {
        $edgeHandler = $this->edgeHandler;

        $edge   = Edge::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $edgeId = $edgeHandler->add($this->collection->getId(), $edge);

        $this->assertTrue(is_numeric($edgeId), 'Did not return an id!');

        // inject wrong encoding
        $isoKey   = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "someWrongEncododedAttribute");
        $isoValue = iconv("UTF-8", "ISO-8859-1//TRANSLIT", "someWrongEncodedValueü");

        $edge->set($isoKey, $isoValue);
        $edge->set('someOtherAttribute', 'someOtherValue2');
        $result = $edgeHandler->replace($edge);

        $this->assertTrue($result);
        $resultingEdge = $edgeHandler->get($this->collection->getId(), $edgeId);

        $this->assertObjectHasAttribute('_id', $resultingEdge, '_id field should exist, empty or with an id');

        $this->assertTrue(
            ($resultingEdge->someAttribute == 'someValue2'),
            'Should be :someValue2, is: ' . $resultingEdge->someAttribute
        );
        $this->assertTrue(
            ($resultingEdge->someOtherAttribute == 'someOtherValue2'),
            'Should be :someOtherValue2, is: ' . $resultingEdge->someOtherAttribute
        );

        $response = $edgeHandler->delete($resultingEdge);
        $this->assertTrue($response, 'Delete should return true!');
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
        try {
            $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestEdgeCollection01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        try {
            $this->collectionHandler->delete('ArangoDBPHPTestSuiteTestCollection01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }



        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
        unset($this->edgeHandler);
        unset($this->edgeCollection);
        unset($this->documentCollection);
    }
}
