<?php
/**
 * ArangoDB PHP client testsuite
 * File: CollectionExtendedTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class CollectionExtendedTest
 *
 * @property Connection $connection
 * @property Collection $collection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler $documentHandler
 *
 * @package triagens\ArangoDb
 */
class CollectionExtendedTest extends
    \PHPUnit_Framework_TestCase
{
    /**
     * Test set-up
     */
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collection        = new Collection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        $this->documentHandler   = new DocumentHandler($this->connection);

        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
    }


    /**
     * test for creation, get, and delete of a collection with waitForSync default value (no setting)
     */
    public function testCreateGetAndDeleteCollectionWithWaitForSyncDefault()
    {
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;

        $resultingAttribute = $collection->getWaitForSync();
        static::assertNull($resultingAttribute, 'Default waitForSync in collection should be NULL!');

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);


        $response = $collectionHandler->add($collection);

        static::assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $collectionHandler->get($name);

        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, getProperties, and delete of a volatile (in-memory-only) collection
     */
    public function testCreateGetAndDeleteVolatileCollection()
    {
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;

        $resultingAttribute = $collection->getIsVolatile();
        static::assertNull($resultingAttribute, 'Default waitForSync in API should be NULL!');

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);
        $collection->setIsVolatile(true);


        $response = $collectionHandler->add($collection);

        static::assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $collectionHandler->get($name);

        $properties = $collectionHandler->getProperties($name);
        static::assertTrue($properties->getIsVolatile(), '"isVolatile" should be true!');


        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, getProperties, and delete of a volatile (in-memory-only) collection
     */
    public function testCreateGetAndDeleteSystemCollection()
    {
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;

        $resultingAttribute = $collection->getIsSystem();
        static::assertNull($resultingAttribute, 'Default isSystem in API should be NULL!');

        $name = '_ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);
        $collection->setIsSystem(true);


        $response = $collectionHandler->add($collection);

        static::assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $collectionHandler->get($name);

        $properties = $collectionHandler->getProperties($name);
        static::assertTrue($properties->getIsSystem(), '"isSystem" should be true!');

        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for getting all collection exclude system collections
     */
    public function testGetAllNonSystemCollections()
    {
        $collectionHandler = $this->collectionHandler;

        $collections = array(
            'ArangoDB_PHP_TestSuite_TestCollection_01',
            'ArangoDB_PHP_TestSuite_TestCollection_02'
        );

        foreach ($collections as $col) {
            $collection = new Collection();
            $collection->setName($col);
            $collectionHandler->add($collection);
        }

        $collectionList = $collectionHandler->getAllCollections($options = array('excludeSystem' => true));

        foreach ($collections as $col) {
            static::assertArrayHasKey($col, $collectionList, 'Collection name should be in collectionList');
        }

        static::assertArrayNotHasKey(
            '_structures',
            $collectionList,
            'System collection _structure should not be returned'
        );

        foreach ($collections as $col) {
            $collectionHandler->delete($col);
        }
    }

    /**
     * test for getting the Checksum for a collection containing 3 documents in different varieties
     */
    public function testGetChecksum()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collectionHandler = $this->collectionHandler;
        $documentHandler   = $this->documentHandler;

        $collection = new Collection();
        $collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');

        $collection->setId($collectionHandler->create($collection));

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->save($collection->getId(), $document);
        $document2 = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->save($collection->getId(), $document2);
        $document3 = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->save($collection->getId(), $document3);

        $checksum1 = $collectionHandler->getChecksum($collection->getName(), true, true);
        $checksum2 = $collectionHandler->getChecksum($collection->getName());
        $checksum3 = $collectionHandler->getChecksum($collection->getName(), false, true);
        $checksum4 = $collectionHandler->getChecksum($collection->getName(), true);
        $revision  = $checksum1['revision'];
        static::assertEquals($revision, $checksum2['revision']);
        static::assertEquals($revision, $checksum3['revision']);
        static::assertEquals($revision, $checksum4['revision']);

        static::assertNotEquals($checksum1['checksum'], $checksum2['checksum']);
        static::assertNotEquals($checksum1['checksum'], $checksum3['checksum']);
        static::assertNotEquals($checksum1['checksum'], $checksum4['checksum']);
        static::assertNotEquals($checksum2['checksum'], $checksum3['checksum']);
        static::assertNotEquals($checksum2['checksum'], $checksum4['checksum']);
        static::assertNotEquals($checksum3['checksum'], $checksum4['checksum']);

        $collectionHandler->drop($collection);
    }

    /**
     *
     * test for getting the Checksum for a non existing collection
     */
    public function testGetChecksumWithException()
    {
        $collectionHandler = $this->collectionHandler;
        try {
            $collectionHandler->getChecksum('nonExisting', true, true);
        } catch (\Exception $e) {
            static::assertEquals($e->getCode(), 404);
        }
    }

    /**
     * test for getting the , true, true for a collection
     */
    public function testGetRevision()
    {
        $collectionHandler = $this->collectionHandler;
        $documentHandler   = $this->documentHandler;

        $collection = new Collection();
        $collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');

        $collection->setId($collectionHandler->create($collection));
        $revision = $collectionHandler->getRevision($collection->getName());
        static::assertArrayHasKey('revision', $revision);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->save($collection->getId(), $document);

        $revision2 = $collectionHandler->getRevision($collection->getName());

        static::assertNotEquals($revision2['revision'], $revision['revision']);

        $collectionHandler->drop($collection);
    }

    /**
     *
     * test for getting the revision for a non existing collection
     */
    public function testGetRevisionWithException()
    {
        $collectionHandler = $this->collectionHandler;
        try {
            $collectionHandler->getRevision('nonExisting');
        } catch (\Exception $e) {
            static::assertEquals($e->getCode(), 404);
        }
    }


    /**
     * test for creation, rename, and delete of a collection
     */
    public function testCreateRenameAndDeleteCollection()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;


        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);

        $response = $collectionHandler->add($collection);

        static::assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($name);

        $collectionHandler->rename(
            $resultingCollection,
            'ArangoDB_PHP_TestSuite_TestCollection_01_renamed'
        );

        $resultingCollectionRenamed = $collectionHandler->get('ArangoDB_PHP_TestSuite_TestCollection_01_renamed');
        $newName                    = $resultingCollectionRenamed->getName();

        static::assertEquals(
            $newName, 'ArangoDB_PHP_TestSuite_TestCollection_01_renamed', 'Collection was not renamed!'
        );
        $response = $collectionHandler->delete($resultingCollectionRenamed);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, rename, and delete of a collection with wrong encoding
     *
     * We expect an exception here:
     *
     * @expectedException \triagens\ArangoDb\ClientException
     *
     */
    public function testCreateRenameAndDeleteCollectionWithWrongEncoding()
    {
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;


        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);

        $response = $collectionHandler->add($collection);

        static::assertTrue(is_numeric($response), 'Adding collection did not return an id!');

        $resultingCollection = $collectionHandler->get($name);

        // inject wrong encoding
        $isoValue = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'ArangoDB_PHP_TestSuite_TestCollection_01_renamedü');

        $collectionHandler->rename($resultingCollection, $isoValue);


        $response = $collectionHandler->delete($resultingCollection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, get, and delete of a collection with waitForSync set to true
     */
    public function testCreateGetAndDeleteCollectionWithWaitForSyncTrueAndJournalSizeSet()
    {
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $collection->setWaitForSync(true);
        $collection->setJournalSize(1024 * 1024 * 2);
        $resultingWaitForSyncAttribute = $collection->getWaitForSync();
        $resultingJournalSizeAttribute = $collection->getJournalSize();


        static::assertTrue($resultingWaitForSyncAttribute, 'WaitForSync should be true!');
        static::assertEquals($resultingJournalSizeAttribute, 1024 * 1024 * 2, 'JournalSize should be 2MB!');

        $name = 'ArangoDB_PHP_TestSuite_TestCollection_01';
        $collection->setName($name);

        $collectionHandler->add($collection);

        // here we check the collectionHandler->getProperties function
        $properties = $collectionHandler->getProperties($collection->getName());
        static::assertObjectHasAttribute(
            '_waitForSync',
            $properties,
            'waiForSync field should exist, empty or with an id'
        );
        static::assertObjectHasAttribute(
            '_journalSize',
            $properties,
            'journalSize field should exist, empty or with an id'
        );

        // here we check the collectionHandler->unload() function
        // First fill it a bit to make sure it's loaded...
        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->add($collection->getName(), $document);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->add($collection->getName(), $document);

        $arrayOfDocuments = $collectionHandler->getAllIds($collection->getName());

        static::assertTrue(
            is_array($arrayOfDocuments) && (count($arrayOfDocuments) === 2),
            'Should return an array of 2 document ids!'
        );

        //now check
        $unloadResult = $collectionHandler->unload($collection->getName());
        $unloadResult = $unloadResult->getJson();
        static::assertArrayHasKey('status', $unloadResult, 'status field should exist');
        static::assertTrue(
            $unloadResult['status'] === 4 || $unloadResult['status'] === 2,
            'Collection status should be 4 (in the process of being unloaded) or 2 (unloaded). Found: ' . $unloadResult['status'] . '!'
        );


        // here we check the collectionHandler->load() function
        $loadResult = $collectionHandler->load($collection->getName());
        $loadResult = $loadResult->getJson();
        static::assertArrayHasKey('status', $loadResult, 'status field should exist');
        static::assertEquals(
            $loadResult['status'], 3, 'Collection status should be 3(loaded). Found: ' . $unloadResult['status'] . '!'
        );


        $resultingWaitForSyncAttribute = $collection->getWaitForSync();
        $resultingJournalSizeAttribute = $collection->getJournalSize();
        static::assertTrue($resultingWaitForSyncAttribute, 'Server waitForSync should return true!');
        static::assertEquals($resultingJournalSizeAttribute, 1024 * 1024 * 2, 'JournalSize should be 2MB!');

        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, get, and delete of a collection given its settings through createFromArray() and waitForSync set to true
     */
    public function testCreateGetAndDeleteCollectionThroughCreateFromArrayWithWaitForSyncTrue()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $response   = $collectionHandler->add($collection);

        $collectionHandler->get($response);

        $resultingAttribute = $collection->getWaitForSync();
        static::assertTrue($resultingAttribute, 'Server waitForSync should return true!');

        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation of documents, and removal by keys
     */
    public function testRemoveByKeys()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => false)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $keys   = array($documentId, $documentId2, $documentId3);
        $result = $collectionHandler->removeByKeys($collection->getId(), $keys);
        static::assertEquals(array('removed' => 3, 'ignored' => 0), $result);
    }


    /**
     * test for removal by keys with unknown collection
     * @expectedException \triagens\ArangoDb\ServerException
     */
    public function testRemoveByKeysCollectionNotFound()
    {
        $collectionHandler = $this->collectionHandler;

        $keys   = array('foo');
        $result = $collectionHandler->removeByKeys('ThisDoesNotExist', $keys);
    }


    /**
     * test for creation of documents, and removal by keys
     */
    public function testRemoveByKeysNotFound()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => false)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $keys   = array('foo', 'bar', 'baz');
        $result = $collectionHandler->removeByKeys($collection->getId(), $keys);
        static::assertEquals(array('removed' => 0, 'ignored' => 3), $result);
    }


    /**
     * test for creation of documents, and removal by example, using an empty example
     */
    public function testCreateDocumentsAndRemoveByExampleEmptyExample()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01')
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $result = $collectionHandler->removeByExample($collection->getId(), array());
        static::assertEquals(3, $result);
    }


    /**
     * test for update by example, using an empty example
     */
    public function testCreateDocumentsAndUpdateByExampleEmptyExample()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01')
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $result = $collectionHandler->updateByExample($collection->getId(), array(), array('foo' => 'bar'));
        static::assertEquals(3, $result);
    }


    /**
     * test for update by example, using an empty update example
     */
    public function testCreateDocumentsAndUpdateByExampleEmptyUpdateExample()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01')
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $result = $collectionHandler->updateByExample($collection->getId(), array(), array());
        static::assertEquals(3, $result);
    }


    /**
     * test for replace by example, using an empty example
     */
    public function testCreateDocumentsAndReplaceByExampleEmptyExample()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01')
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $result = $collectionHandler->replaceByExample($collection->getId(), array(), array('foo' => 'bar'));
        static::assertEquals(3, $result);
    }

    /**
     * test for replace by example, using an empty example
     */
    public function testCreateDocumentsAndReplaceByExampleEmptyReplaceExample()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01')
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $result = $collectionHandler->replaceByExample($collection->getId(), array(), array());
        static::assertEquals(3, $result);
    }


    /**
     * test for query by example, using an empty example
     */
    public function testCreateDocumentsAndQueryByExampleEmptyExample()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01')
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $cursor = $collectionHandler->byExample($collection->getId(), array());
        static::assertEquals(
            $cursor->getCount(), 3, 'should return 3.'
        );
    }


    /**
     * test for creation of documents, and removal by example
     */
    public function testCreateDocumentsWithCreateFromArrayAndRemoveByExample()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $result          = $collectionHandler->removeByExample($collection->getId(), $exampleDocument);
        static::assertSame($result, 2);
    }


    /**
     * test for creation of documents, and update and replace by example and finally removal by example
     */
    public function testCreateDocumentsWithCreateFromArrayUpdateReplaceAndRemoveByExample()
    {
        $this->collectionHandler = new CollectionHandler($this->connection);
        $documentHandler         = $this->documentHandler;
        $collectionHandler       = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $updateDocument  = Document::createFromArray(array('someNewAttribute' => 'someNewValue'));

        $result = $collectionHandler->updateByExample($collection->getId(), $exampleDocument, $updateDocument);
        static::assertSame($result, 2);

        $exampleDocument = Document::createFromArray(array('someAttribute' => 'someValue2'));
        $replaceDocument = Document::createFromArray(
            array(
                'someAttribute' => 'someValue2replaced',
                'someOtherAttribute' => 'someOtherValue2replaced'
            )
        );
        $result          = $collectionHandler->replaceByExample(
            $collection->getId(),
            $exampleDocument,
            $replaceDocument
        );
        static::assertSame($result, 1);

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $result          = $collectionHandler->removeByExample($collection->getId(), $exampleDocument);
        static::assertSame($result, 2);
    }


    /**
     * test for creation of documents, and update and replace by example and finally removal by example
     */
    public function testCreateDocumentsFromArrayUpdateReplaceAndRemoveByExample()
    {
        $this->collectionHandler = new CollectionHandler($this->connection);
        $documentHandler         = $this->documentHandler;
        $collectionHandler       = $this->collectionHandler;


        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $collectionHandler->add($collection);
        $document = array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue');

        $documentId = $documentHandler->save($collection->getId(), $document);
        static::assertTrue(is_numeric($documentId), 'Did not return an id!');


        $document2 = array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2');


        $documentId2 = $documentHandler->save($collection->getId(), $document2);
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');


        $document3 = array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue');

        $documentId3 = $documentHandler->save($collection->getId(), $document3);
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');


        $exampleDocument = array('someOtherAttribute' => 'someOtherValue');
        $updateDocument  = array('someNewAttribute' => 'someNewValue');

        $result = $collectionHandler->updateByExample($collection->getId(), $exampleDocument, $updateDocument);
        static::assertSame($result, 2);


        $exampleDocument = array('someAttribute' => 'someValue2');
        $replaceDocument =
            array('someAttribute' => 'someValue2replaced', 'someOtherAttribute' => 'someOtherValue2replaced');

        $result = $collectionHandler->replaceByExample(
            $collection->getId(),
            $exampleDocument,
            $replaceDocument
        );
        static::assertSame($result, 1);


        $exampleDocument = array('someOtherAttribute' => 'someOtherValue');
        $result          = $collectionHandler->removeByExample($collection->getId(), $exampleDocument);
        static::assertSame($result, 2);
    }


    /**
     * test for creation of documents, and update and replace by example and finally removal by example
     */
    public function testCreateDocumentsWithCreateFromArrayUpdateReplaceAndRemoveByExampleWithLimits()
    {
        $this->collectionHandler = new CollectionHandler($this->connection);
        $documentHandler         = $this->documentHandler;
        $collectionHandler       = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $updateDocument  = Document::createFromArray(array('someNewAttribute' => 'someNewValue'));

        $result = $collectionHandler->updateByExample(
            $collection->getId(),
            $exampleDocument,
            $updateDocument,
            array('limit' => 1)
        );
        static::assertSame($result, 1);

        $exampleDocument = Document::createFromArray(array('someAttribute' => 'someValue2'));
        $replaceDocument = Document::createFromArray(
            array(
                'someAttribute' => 'someValue2replaced',
                'someOtherAttribute' => 'someOtherValue2replaced'
            )
        );
        $result          = $collectionHandler->replaceByExample(
            $collection->getId(),
            $exampleDocument,
            $replaceDocument,
            array('limit' => 2)
        );
        static::assertSame($result, 1);

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $result          = $collectionHandler->removeByExample(
            $collection->getId(),
            $exampleDocument,
            array('limit' => 1)
        );
        static::assertSame($result, 1);
    }


    /**
     * test for creation of documents, and update and replace by example and finally removal by example
     */
    public function testCreateDocumentsWithCreateFromArrayUpdateReplaceAndRemoveByExampleWithWaitForSync()
    {
        $this->collectionHandler = new CollectionHandler($this->connection);
        $documentHandler         = $this->documentHandler;
        $collectionHandler       = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $updateDocument  = Document::createFromArray(array('someNewAttribute' => 'someNewValue'));

        $result = $collectionHandler->updateByExample(
            $collection->getId(),
            $exampleDocument,
            $updateDocument,
            array('waitForSync' => true)
        );
        static::assertSame($result, 2);

        $exampleDocument = Document::createFromArray(array('someAttribute' => 'someValue2'));
        $replaceDocument = Document::createFromArray(
            array(
                'someAttribute' => 'someValue2replaced',
                'someOtherAttribute' => 'someOtherValue2replaced'
            )
        );
        $result          = $collectionHandler->replaceByExample(
            $collection->getId(),
            $exampleDocument,
            $replaceDocument,
            array('waitForSync' => true)
        );
        static::assertSame($result, 1);

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $result          = $collectionHandler->removeByExample(
            $collection->getId(),
            $exampleDocument,
            array('waitForSync' => true)
        );
        static::assertSame($result, 2);
    }


    /**
     * test for creation of documents, and update and replace by example and finally removal by example
     */
    public function testCreateDocumentsWithCreateFromArrayUpdateReplaceAndRemoveByExampleWithKeepNull()
    {
        $this->collectionHandler = new CollectionHandler($this->connection);
        $documentHandler         = $this->documentHandler;
        $collectionHandler       = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');


        $exampleDocument = Document::createFromArray(array('someAttribute' => 'someValue2'));
        $updateDocument  = Document::createFromArray(
            array('someNewAttribute' => 'someNewValue', 'someOtherAttribute' => null)
        );

        $result = $collectionHandler->updateByExample(
            $collection->getId(),
            $exampleDocument,
            $updateDocument,
            array('keepNull' => false)
        );
        static::assertSame($result, 1);


        $exampleDocument = Document::createFromArray(array('someNewAttribute' => 'someNewValue'));
        $cursor          = $collectionHandler->byExample($collection->getId(), $exampleDocument);
        static::assertEquals(
            $cursor->getCount(), 1, 'should return 1.'
        );

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $result          = $collectionHandler->removeByExample(
            $collection->getId(),
            $exampleDocument,
            array('waitForSync' => true)
        );
        static::assertSame($result, 2);
    }


    /**
     * test for creation of documents, and removal by example
     */
    public function testCreateDocumentsWithCreateFromArrayAndRemoveByExampleWithLimit()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $exampleDocument = Document::createFromArray(array('someOtherAttribute' => 'someOtherValue'));
        $result          = $collectionHandler->removeByExample(
            $collection->getId(),
            $exampleDocument,
            array('limit' => 1)
        );
        static::assertSame($result, 1);
    }


    /**
     * test for import of documents, Headers-Values Style
     */
    public function testImportFromFileUsingHeadersAndValues()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collectionHandler = $this->collectionHandler;
        $result            = $collectionHandler->importFromFile(
            'importCollection_01_arango_unittests',
            __DIR__ . '/files_for_tests/import_file_header_values.txt',
            $options = array('createCollection' => true)
        );

        static::assertTrue($result['error'] === false && $result['created'] === 2);

        $statement = new Statement(
            $this->connection, array(
                                 'query' => '',
                                 'count' => true,
                                 'batchSize' => 1,
                                 'sanitize' => true,
                             )
        );
        $query     = 'FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        static::assertTrue(
            $resultingDocument[0]->getKey() === 'test1' && $resultingDocument[0]->firstName === 'Joe',
            'Document returned did not contain expected data.'
        );

        static::assertTrue(
            $resultingDocument[1]->getKey() === 'test2' && $resultingDocument[1]->firstName === 'Jane',
            'Document returned did not contain expected data.'
        );

        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));
    }


    /**
     * test for import of documents, Line by Line Documents Style
     */
    public function testImportFromFileUsingDocumentsLineByLine()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collectionHandler = $this->collectionHandler;
        $result            = $collectionHandler->importFromFile(
            'importCollection_01_arango_unittests',
            __DIR__ . '/files_for_tests/import_file_line_by_line.txt',
            $options = array('createCollection' => true, 'type' => 'documents')
        );
        static::assertTrue($result['error'] === false && $result['created'] === 2);

        $statement = new Statement(
            $this->connection, array(
                                 'query' => '',
                                 'count' => true,
                                 'batchSize' => 2,
                                 'sanitize' => true,
                             )
        );
        $query     = 'FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        static::assertTrue(
            $resultingDocument[0]->getKey() === 'test1' && $resultingDocument[0]->firstName === 'Joe',
            'Document returned did not contain expected data.'
        );

        static::assertTrue(
            $resultingDocument[1]->getKey() === 'test2' && $resultingDocument[1]->firstName === 'Jane',
            'Document returned did not contain expected data.'
        );

        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));
    }


    /**
     * test for import of documents, Line by Line result-set Style
     */
    public function testImportFromFileUsingResultSet()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collectionHandler = $this->collectionHandler;
        $result            = $collectionHandler->importFromFile(
            'importCollection_01_arango_unittests',
            __DIR__ . '/files_for_tests/import_file_resultset.txt',
            $options = array('createCollection' => true, 'type' => 'array')
        );
        static::assertTrue($result['error'] === false && $result['created'] === 2);

        $statement = new Statement(
            $this->connection, array(
                                 'query' => '',
                                 'count' => true,
                                 'batchSize' => 3,
                                 'sanitize' => true,
                             )
        );
        $query     = 'FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        static::assertEquals(
            $cursor->getCount(), 2, 'should return 2.'
        );

        static::assertTrue(
            $resultingDocument[0]->getKey() === 'test1' && $resultingDocument[0]->firstName === 'Joe',
            'Document returned did not contain expected data.'
        );

        static::assertTrue(
            $resultingDocument[1]->getKey() === 'test2' && $resultingDocument[1]->firstName === 'Jane',
            'Document returned did not contain expected data.'
        );

        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));
    }


    /**
     * test for import of documents by giving an array of documents
     */
    public function testImportFromArrayOfDocuments()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collectionHandler = $this->collectionHandler;

        $document1 = Document::createFromArray(
            array(
                'firstName' => 'Joe',
                'lastName' => 'Public',
                'age' => 42,
                'gender' => 'male',
                '_key' => 'test1'
            )
        );
        $document2 = Document::createFromArray(
            array(
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'age' => 31,
                'gender' => 'female',
                '_key' => 'test2'
            )
        );

        $data   = array($document1, $document2);
        $result = $collectionHandler->import(
            'importCollection_01_arango_unittests',
            $data,
            $options = array('createCollection' => true)
        );

        static::assertTrue($result['error'] === false && $result['created'] === 2);

        $statement = new Statement(
            $this->connection, array(
                                 'query' => '',
                                 'count' => true,
                                 'batchSize' => 4,
                                 'sanitize' => true,
                             )
        );
        $query     = 'FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        static::assertTrue(
            $resultingDocument[0]->getKey() === 'test1' && $resultingDocument[0]->firstName === 'Joe',
            'Document returned did not contain expected data.'
        );

        static::assertTrue(
            $resultingDocument[1]->getKey() === 'test2' && $resultingDocument[1]->firstName === 'Jane',
            'Document returned did not contain expected data.'
        );

        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));
    }


    /**
     * test for import of documents by giving an array of documents
     */
    public function testImportFromStringWithValuesAndHeaders()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collectionHandler = $this->collectionHandler;

        $data = '[ "firstName", "lastName", "age", "gender", "_key"]
               [ "Joe", "Public", 42, "male", "test1" ]
               [ "Jane", "Doe", 31, "female", "test2" ]';

        $result = $collectionHandler->import(
            'importCollection_01_arango_unittests',
            $data,
            $options = array('createCollection' => true)
        );

        static::assertTrue($result['error'] === false && $result['created'] === 2);

        $statement = new Statement(
            $this->connection, array(
                                 'query' => '',
                                 'count' => true,
                                 'batchSize' => 5,
                                 'sanitize' => true,
                             )
        );
        $query     = 'FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        static::assertTrue(
            $resultingDocument[0]->getKey() === 'test1' && $resultingDocument[0]->firstName === 'Joe',
            'Document returned did not contain expected data.'
        );

        static::assertTrue(
            $resultingDocument[1]->getKey() === 'test2' && $resultingDocument[1]->firstName === 'Jane',
            'Document returned did not contain expected data.'
        );

        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));
    }


    /**
     * test for import of documents by giving an array of documents
     */
    public function testImportFromStringUsingDocumentsLineByLine()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collectionHandler = $this->collectionHandler;

        $data = '{ "firstName" : "Joe", "lastName" : "Public", "age" : 42, "gender" : "male", "_key" : "test1"}
               { "firstName" : "Jane", "lastName" : "Doe", "age" : 31, "gender" : "female", "_key" : "test2"}';

        $result = $collectionHandler->import(
            'importCollection_01_arango_unittests',
            $data,
            $options = array('createCollection' => true, 'type' => 'documents')
        );

        static::assertTrue($result['error'] === false && $result['created'] === 2);

        $statement = new Statement(
            $this->connection, array(
                                 'query' => '',
                                 'count' => true,
                                 'batchSize' => 100,
                                 'sanitize' => true,
                             )
        );
        $query     = 'FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        static::assertTrue(
            $resultingDocument[0]->getKey() === 'test1' && $resultingDocument[0]->firstName === 'Joe',
            'Document returned did not contain expected data.'
        );

        static::assertTrue(
            $resultingDocument[1]->getKey() === 'test2' && $resultingDocument[1]->firstName === 'Jane',
            'Document returned did not contain expected data.'
        );

        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));
    }


    /**
     * test for import of documents by giving an array of documents
     */
    public function testImportFromStringUsingDocumentsUsingResultset()
    {
        if (isCluster($this->connection)) {
            // don't execute this test in a cluster
            return;
        }

        $collectionHandler = $this->collectionHandler;

        $data = '[{ "firstName" : "Joe", "lastName" : "Public", "age" : 42, "gender" : "male", "_key" : "test1"},
{ "firstName" : "Jane", "lastName" : "Doe", "age" : 31, "gender" : "female", "_key" : "test2"}]';

        $result = $collectionHandler->import(
            'importCollection_01_arango_unittests',
            $data,
            $options = array('createCollection' => true, 'type' => 'array')
        );

        static::assertTrue($result['error'] === false && $result['created'] === 2);

        $statement = new Statement(
            $this->connection, array(
                                 'query' => '',
                                 'count' => true,
                                 'batchSize' => 1000,
                                 'sanitize' => true,
                             )
        );
        $query     = 'FOR u IN `importCollection_01_arango_unittests` SORT u._id ASC RETURN u';

        $statement->setQuery($query);

        $cursor = $statement->execute();

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        static::assertTrue(
            $resultingDocument[0]->getKey() === 'test1' && $resultingDocument[0]->firstName === 'Joe',
            'Document returned did not contain expected data.'
        );

        static::assertTrue(
            $resultingDocument[1]->getKey() === 'test2' && $resultingDocument[1]->firstName === 'Jane',
            'Document returned did not contain expected data.'
        );

        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));
    }


    /**
     * test for creation, getAllIds, and delete of a collection given its settings through createFromArray()
     */
    public function testCreateGetAllIdsAndDeleteCollectionThroughCreateFromArray()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->add($collection->getId(), $document);

        $arrayOfDocuments = $collectionHandler->getAllIds($collection->getId());

        static::assertTrue(
            is_array($arrayOfDocuments) && (count($arrayOfDocuments) === 2),
            'Should return an array of 2 document ids!'
        );

        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, all, and delete of a collection
     */
    public function testCreateAndAllAndDeleteCollection()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->add($collection->getId(), $document);

        $cursor = $collectionHandler->all($collection->getId());

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));

        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, all with hiddenAttributes, and delete of a collection
     */
    public function testCreateAndIssueAllWithHiddenAttributesAndDeleteCollection()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->add($collection->getId(), $document);

        $cursor = $collectionHandler->all(
            $collection->getId(), [
                                    '_ignoreHiddenAttributes' => false,
                                    '_hiddenAttributes' => ['someOtherAttribute']
                                ]
        );

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
            $doc                     = $resultingDocument[$key]->getAll();
            static::assertArrayNotHasKey('someOtherAttribute', $doc);
        }

        $cursor = $collectionHandler->all(
            $collection->getId(), [
                                    '_ignoreHiddenAttributes' => true,
                                    '_hiddenAttributes' => ['someOtherAttribute']
                                ]
        );

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
            $doc                     = $resultingDocument[$key]->getAll();
            static::assertArrayHasKey('someOtherAttribute', $doc);
        }


        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }

    /**
     * test for creation, all with hiddenAttributes but different Doc->GetAll options, and delete of a collection
     */
    public function testCreateAndIssueAllWithHiddenAttributesButDifferentDocGetAllOptionsAndDeleteCollection()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->add($collection->getId(), $document);

        $cursor = $collectionHandler->all(
            $collection->getId(), [
                                    '_ignoreHiddenAttributes' => false,
                                    '_hiddenAttributes' => ['someOtherAttribute']
                                ]
        );

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
            $doc                     = $resultingDocument[$key]->getAll();
            static::assertArrayNotHasKey('someOtherAttribute', $doc);
        }

        $cursor = $collectionHandler->all(
            $collection->getId(), [
                                    '_ignoreHiddenAttributes' => false,
                                    '_hiddenAttributes' => ['someOtherAttribute']
                                ]
        );

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
            $doc                     = $resultingDocument[$key]->getAll(
                [
                    '_ignoreHiddenAttributes' => true
                ]
            );
            static::assertArrayHasKey('someOtherAttribute', $doc);
        }

        $cursor = $collectionHandler->all(
            $collection->getId(), [
                                    '_ignoreHiddenAttributes' => false,
                                    '_hiddenAttributes' => ['someOtherAttribute']
                                ]
        );

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
            $doc                     = $resultingDocument[$key]->getAll(
                [
                    '_hiddenAttributes' => []
                ]
            );
            static::assertArrayHasKey('someOtherAttribute', $doc);
        }

        $cursor = $collectionHandler->all(
            $collection->getId(), [
                                    '_ignoreHiddenAttributes' => true,
                                    '_hiddenAttributes' => ['someOtherAttribute']
                                ]
        );

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
            $doc                     = $resultingDocument[$key]->getAll();
            static::assertArrayHasKey('someOtherAttribute', $doc);
        }

        $cursor = $collectionHandler->all(
            $collection->getId(), [
                                    '_ignoreHiddenAttributes' => true,
                                    '_hiddenAttributes' => []
                                ]
        );

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
            $doc                     = $resultingDocument[$key]->getAll(
                [
                    '_ignoreHiddenAttributes' => false,
                    '_hiddenAttributes' => ['someOtherAttribute']
                ]
            );
            static::assertArrayNotHasKey('someOtherAttribute', $doc);
        }

        $cursor = $collectionHandler->all(
            $collection->getId(), [
                                    '_ignoreHiddenAttributes' => true
                                ]
        );

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
            $doc                     = $resultingDocument[$key]->getAll(
                [
                    '_ignoreHiddenAttributes' => false,
                    '_hiddenAttributes' => ['someOtherAttribute']
                ]
            );

            static::assertArrayNotHasKey('someOtherAttribute', $doc);
        }


        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, all with limit, and delete of a collection
     */
    public function testCreateAndAllWithLimitAndDeleteCollection()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->add($collection->getId(), $document);

        $cursor = $collectionHandler->all($collection->getId(), array('limit' => 1));

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        // 2 Documents limited to 1, the result should be 1
        static::assertCount(1, $resultingDocument, 'Should be 1, was: ' . count($resultingDocument));

        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation, all with skip, and delete of a collection
     */
    public function testCreateAndAllWithSkipAndDeleteCollection()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        for ($i = 0; $i < 3; $i++) {
            $document = Document::createFromArray(
                array('someAttribute' => 'someValue ' . $i, 'someOtherAttribute' => 'someValue ' . $i)
            );
            $documentHandler->add($collection->getId(), $document);
        }

        $cursor = $collectionHandler->all($collection->getId(), array('skip' => 1));

        $resultingDocument = null;

        foreach ($cursor as $key => $value) {
            $resultingDocument[$key] = $value;
        }

        // With 3 Documents and skipping 1, the result should be 2
        static::assertCount(2, $resultingDocument, 'Should be 2, was: ' . count($resultingDocument));

        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creating, filling with documents and truncating the collection.
     */
    public function testCreateFillAndTruncateCollection()
    {
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $documentHandler = $this->documentHandler;

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->add($collection->getId(), $document);

        $arrayOfDocuments = $collectionHandler->getAllIds($collection->getId());

        static::assertTrue(
            is_array($arrayOfDocuments) && (count($arrayOfDocuments) === 2),
            'Should return an array of 2 document ids!'
        );

        //truncate, given the collection object
        $collectionHandler->truncate($collection);


        $document = Document::createFromArray(
            array('someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentHandler->add($collection->getId(), $document);

        $document = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentHandler->add($collection->getId(), $document);

        $arrayOfDocuments = $collectionHandler->getAllIds($collection->getId());

        static::assertTrue(
            is_array($arrayOfDocuments) && (count($arrayOfDocuments) === 2),
            'Should return an array of 2 document ids!'
        );

        //truncate, given the collection id
        $collectionHandler->truncate($collection->getId());


        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test to set some attributes and get all attributes of the collection through getAll()
     */
    public function testGetAll()
    {
        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $result     = $collection->getAll();

        static::assertArrayHasKey('id', $result, 'Id field should exist, empty or with an id');
        static::assertEquals(
            $result['name'], 'ArangoDB_PHP_TestSuite_TestCollection_01', 'name should return ArangoDB_PHP_TestSuite_TestCollection_01!'
        );
        static::assertTrue($result['waitForSync'], 'waitForSync should return true!');
    }


    /**
     * test for creation of a skip-list indexed collection and querying by range (first level and nested), with closed, skip and limit options
     */

    public function testCreateSkipListIndexedCollectionAddDocumentsAndQueryRange()
    {
        // set up collections, indexes and test-documents
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $indexRes       = $collectionHandler->index($collection->getId(), 'skiplist', array('index'));
        $nestedIndexRes = $collectionHandler->index($collection->getId(), 'skiplist', array('nested.index'));
        static::assertArrayHasKey(
            'isNewlyCreated',
            $indexRes,
            'index creation result should have the isNewlyCreated key !'
        );
        static::assertArrayHasKey(
            'isNewlyCreated',
            $nestedIndexRes,
            'index creation result should have the isNewlyCreated key !'
        );


        $documentHandler = $this->documentHandler;

        $document1 = Document::createFromArray(
            array(
                'index' => 2,
                'someOtherAttribute' => 'someValue2',
                'nested' => array(
                    'index' => 3,
                    'someNestedAttribute3' => 'someNestedValue3'
                )
            )
        );
        $documentHandler->add($collection->getId(), $document1);
        $document2 = Document::createFromArray(
            array(
                'index' => 1,
                'someOtherAttribute' => 'someValue1',
                'nested' => array(
                    'index' => 2,
                    'someNestedAttribute3' => 'someNestedValue2'
                )
            )
        );
        $documentHandler->add($collection->getId(), $document2);

        $document3 = Document::createFromArray(
            array(
                'index' => 3,
                'someOtherAttribute' => 'someValue3',
                'nested' => array(
                    'index' => 1,
                    'someNestedAttribute3' => 'someNestedValue1'
                )
            )
        );
        $documentHandler->add($collection->getId(), $document3);


        // first level attribute range test
        $rangeResult = $collectionHandler->range($collection->getId(), 'index', 1, 2, array('closed' => false));
        $resultArray = $rangeResult->getAll();
        static::assertSame($resultArray[0]->index, 1, 'This value should be 1 !');
        static::assertArrayNotHasKey(1, $resultArray, 'Should not have a second key !');


        $rangeResult = $collectionHandler->range($collection->getId(), 'index', 2, 3, array('closed' => true));
        $resultArray = $rangeResult->getAll();
        static::assertSame($resultArray[0]->index, 2, 'This value should be 2 !');
        static::assertSame($resultArray[1]->index, 3, 'This value should be 3 !');


        $rangeResult = $collectionHandler->range(
            $collection->getId(),
            'index',
            2,
            3,
            array('closed' => true, 'limit' => 1)
        );
        $resultArray = $rangeResult->getAll();
        static::assertSame($resultArray[0]->index, 2, 'This value should be 2 !');
        static::assertArrayNotHasKey(1, $resultArray, 'Should not have a second key !');


        $rangeResult = $collectionHandler->range(
            $collection->getId(),
            'index',
            2,
            3,
            array('closed' => true, 'skip' => 1)
        );
        $resultArray = $rangeResult->getAll();
        static::assertSame($resultArray[0]->index, 3, 'This value should be 3 !');
        static::assertArrayNotHasKey(1, $resultArray, 'Should not have a second key !');


        // nested attribute range test
        $rangeResult = $collectionHandler->range($collection->getId(), 'nested.index', 1, 2, array('closed' => false));
        $resultArray = $rangeResult->getAll();
        static::assertSame($resultArray[0]->nested['index'], 1, 'This value should be 1 !');
        static::assertArrayNotHasKey(1, $resultArray, 'Should not have a second key !');


        $rangeResult = $collectionHandler->range($collection->getId(), 'nested.index', 2, 3, array('closed' => true));
        $resultArray = $rangeResult->getAll();
        static::assertSame($resultArray[0]->nested['index'], 2, 'This value should be 2 !');
        static::assertSame($resultArray[1]->nested['index'], 3, 'This value should be 3 !');


        $rangeResult = $collectionHandler->range(
            $collection->getId(),
            'nested.index',
            2,
            3,
            array('closed' => true, 'limit' => 1)
        );
        $resultArray = $rangeResult->getAll();
        static::assertSame($resultArray[0]->nested['index'], 2, 'This value should be 2 !');
        static::assertArrayNotHasKey(1, $resultArray, 'Should not have a second key !');


        $rangeResult = $collectionHandler->range(
            $collection->getId(),
            'nested.index',
            2,
            3,
            array('closed' => true, 'skip' => 1)
        );
        $resultArray = $rangeResult->getAll();
        static::assertSame($resultArray[0]->nested['index'], 3, 'This value should be 3 !');
        static::assertArrayNotHasKey(1, $resultArray, 'Should not have a second key !');


        // Clean up...
        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation of a geo indexed collection and querying by near, with distance, skip and limit options
     */
    public function testCreateGeoIndexedCollectionAddDocumentsAndQueryNear()
    {
        // set up collections, indexes and test-documents
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $indexRes = $collectionHandler->index($collection->getId(), 'geo', array('loc'));
        static::assertArrayHasKey(
            'isNewlyCreated',
            $indexRes,
            'index creation result should have the isNewlyCreated key !'
        );


        $documentHandler = $this->documentHandler;

        $document1 = Document::createFromArray(array('loc' => array(0, 0), 'someOtherAttribute' => '0 0'));
        $documentHandler->add($collection->getId(), $document1);
        $document2 = Document::createFromArray(array('loc' => array(1, 1), 'someOtherAttribute' => '1 1'));
        $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(array('loc' => array(+30, -30), 'someOtherAttribute' => '30 -30'));
        $documentId3 = $documentHandler->add($collection->getId(), $document3);
        $documentHandler->getById($collection->getId(), $documentId3);


        $rangeResult = $collectionHandler->near($collection->getId(), 0, 0);
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 0 && $resultArray[0]->loc[1] === 0,
            'This value should be 0 0!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertTrue(
            $resultArray[1]->loc[0] === 1 && $resultArray[1]->loc[1] === 1,
            'This value should be 1 1!, is :' . $resultArray[1]->loc[0] . ' ' . $resultArray[1]->loc[1]
        );


        $rangeResult = $collectionHandler->near($collection->getId(), 0, 0, array('distance' => 'distance'));
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 0 && $resultArray[0]->loc[1] === 0,
            'This value should be 0 0 !, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertTrue(
            $resultArray[1]->loc[0] === 1 && $resultArray[1]->loc[1] === 1,
            'This value should be 1 1!, is :' . $resultArray[1]->loc[0] . ' ' . $resultArray[1]->loc[1]
        );
        static::assertTrue(
            $resultArray[2]->loc[0] === 30 && $resultArray[2]->loc[1] === -30,
            'This value should be 30 30!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertSame(
            $resultArray[0]->distance, 0, 'This value should be 0 ! It is :' . $resultArray[0]->distance
        );


        $rangeResult = $collectionHandler->near($collection->getId(), 0, 0, array('limit' => 1));
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 0 && $resultArray[0]->loc[1] === 0,
            'This value should be 0 0!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertArrayNotHasKey(1, $resultArray, 'Should not have a second key !');


        $rangeResult = $collectionHandler->near($collection->getId(), 0, 0, array('skip' => 1));
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 1 && $resultArray[0]->loc[1] === 1,
            'This value should be 1 1!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertTrue(
            $resultArray[1]->loc[0] === 30 && $resultArray[1]->loc[1] === -30,
            'This value should be 30 30!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertArrayNotHasKey(2, $resultArray, 'Should not have a third key !');


        $rangeResult = $collectionHandler->near($collection->getId(), +30, -30);
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 30 && $resultArray[0]->loc[1] === -30,
            'This value should be 30 30!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertTrue(
            $resultArray[1]->loc[0] === 1 && $resultArray[1]->loc[1] === 1,
            'This value should be 1 1!, is :' . $resultArray[1]->loc[0] . ' ' . $resultArray[1]->loc[1]
        );
        static::assertTrue(
            $resultArray[2]->loc[0] === 0 && $resultArray[2]->loc[1] === 0,
            'This value should be 0 0!, is :' . $resultArray[1]->loc[0] . ' ' . $resultArray[1]->loc[1]
        );


        // Clean up...
        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation of a geo indexed collection and querying by within, with distance, skip and limit options
     */
    public function testCreateGeoIndexedCollectionAddDocumentsAndQueryWithin()
    {
        // set up collections, indexes and test-documents
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $indexRes = $collectionHandler->index($collection->getId(), 'geo', array('loc'));
        static::assertArrayHasKey(
            'isNewlyCreated',
            $indexRes,
            'index creation result should have the isNewlyCreated key !'
        );


        $documentHandler = $this->documentHandler;

        $document1 = Document::createFromArray(array('loc' => array(0, 0), 'someOtherAttribute' => '0 0'));
        $documentHandler->add($collection->getId(), $document1);
        $document2 = Document::createFromArray(array('loc' => array(1, 1), 'someOtherAttribute' => '1 1'));
        $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(array('loc' => array(+30, -30), 'someOtherAttribute' => '30 -30'));
        $documentId3 = $documentHandler->add($collection->getId(), $document3);
        $documentHandler->getById($collection->getId(), $documentId3);


        $rangeResult = $collectionHandler->within($collection->getId(), 0, 0, 0);
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 0 && $resultArray[0]->loc[1] === 0,
            'This value should be 0 0!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );


        $rangeResult = $collectionHandler->within(
            $collection->getId(),
            0,
            0,
            200 * 1000,
            array('distance' => 'distance')
        );
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 0 && $resultArray[0]->loc[1] === 0,
            'This value should be 0 0 !, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertTrue(
            $resultArray[1]->loc[0] === 1 && $resultArray[1]->loc[1] === 1,
            'This value should be 1 1!, is :' . $resultArray[1]->loc[0] . ' ' . $resultArray[1]->loc[1]
        );
        static::assertArrayNotHasKey(2, $resultArray, 'Should not have a third key !');
        static::assertSame(
            $resultArray[0]->distance, 0, 'This value should be 0 ! It is :' . $resultArray[0]->distance
        );


        $rangeResult = $collectionHandler->within($collection->getId(), 0, 0, 200 * 1000, array('limit' => 1));
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 0 && $resultArray[0]->loc[1] === 0,
            'This value should be 0 0!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertArrayNotHasKey(1, $resultArray, 'Should not have a second key !');


        $rangeResult = $collectionHandler->within($collection->getId(), 0, 0, 20000 * 1000, array('skip' => 1));
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 1 && $resultArray[0]->loc[1] === 1,
            'This value should be 1 1!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertTrue(
            $resultArray[1]->loc[0] === 30 && $resultArray[1]->loc[1] === -30,
            'This value should be 30 30!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertArrayNotHasKey(2, $resultArray, 'Should not have a third key !');


        $rangeResult = $collectionHandler->within($collection->getId(), +30, -30, 20000 * 1000);
        $resultArray = $rangeResult->getAll();
        static::assertTrue(
            $resultArray[0]->loc[0] === 30 && $resultArray[0]->loc[1] === -30,
            'This value should be 30 30!, is :' . $resultArray[0]->loc[0] . ' ' . $resultArray[0]->loc[1]
        );
        static::assertTrue(
            $resultArray[1]->loc[0] === 1 && $resultArray[1]->loc[1] === 1,
            'This value should be 1 1!, is :' . $resultArray[1]->loc[0] . ' ' . $resultArray[1]->loc[1]
        );
        static::assertTrue(
            $resultArray[2]->loc[0] === 0 && $resultArray[2]->loc[1] === 0,
            'This value should be 0 0!, is :' . $resultArray[1]->loc[0] . ' ' . $resultArray[1]->loc[1]
        );


        // Clean up...
        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * test for creation of a fulltext indexed collection and querying by within, with distance, skip and limit options
     */
    public function testCreateFulltextIndexedCollectionAddDocumentsAndQuery()
    {
        // set up collections and index
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $indexRes = $collectionHandler->index($collection->getName(), 'fulltext', array('name'));
        static::assertArrayHasKey(
            'isNewlyCreated',
            $indexRes,
            'index creation result should have the isNewlyCreated key !'
        );

        // Check if the index is returned in the indexes of the collection
        $indexes = $collectionHandler->getIndexes($collection->getName());
        static::assertSame($indexes['indexes'][1]['fields'][0], 'name', 'The index should be on field "name"!');

        // Drop the index
        $collectionHandler->dropIndex($indexes['indexes'][1]['id']);
        $indexes = $collectionHandler->getIndexes($collection->getName());

        // Check if the index is not in the indexes of the collection anymore
        static::assertArrayNotHasKey(1, $indexes['indexes'], 'There should not be an index on field "name"!');

        // Clean up...
        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * Test if we can create a full text index with options, on a collection
     */
    public function testCreateFulltextIndexedCollectionWithOptions()
    {
        // set up collections and index
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01'));
        $collectionHandler->add($collection);

        $indexRes = $collectionHandler->index(
            $collection->getName(),
            'fulltext',
            array('name'),
            false,
            array('minLength' => 10)
        );

        static::assertArrayHasKey(
            'isNewlyCreated',
            $indexRes,
            'index creation result should have the isNewlyCreated key !'
        );

        static::assertArrayHasKey('minLength', $indexRes, 'index creation result should have a minLength key!');

        static::assertEquals(
            10,
            $indexRes['minLength'],
            'index created does not have the same minLength as the one sent!'
        );

        // Check if the index is returned in the indexes of the collection
        $indexes = $collectionHandler->getIndexes($collection->getName());
        static::assertSame($indexes['indexes'][1]['fields'][0], 'name', 'The index should be on field "name"!');

        // Drop the index
        $collectionHandler->dropIndex($indexes['indexes'][1]['id']);
        $indexes = $collectionHandler->getIndexes($collection->getName());

        // Check if the index is not in the indexes of the collection anymore
        static::assertArrayNotHasKey(1, $indexes['indexes'], 'There should not be an index on field "name"!');

        // Clean up...
        $response = $collectionHandler->delete($collection);
        static::assertTrue($response, 'Delete should return true!');
    }


    /**
     * Test getting a random document from the collection
     */
    public function testAnyDocumentInCollection()
    {
        // set up collections and documents
        $collectionHandler = $this->collectionHandler;
        $documentHandler   = $this->documentHandler;

        $collection = Collection::createFromArray(array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_Any'));
        $collectionHandler->add($collection);

        $document1 = new Document();
        $document1->set('message', 'message1');

        $documentHandler->save($collection->getId(), $document1);

        $document2 = new Document();
        $document2->set('message', 'message2');

        $documentHandler->save($collection->getId(), $document2);

        $document3 = new Document();
        $document3->set('message', 'message3');

        $documentHandler->save($collection->getId(), $document3);

        //Now, let's try to query any document
        $document = $collectionHandler->any($collection->getName());
        static::assertContains(
            $document->get('message'),
            array('message1', 'message2', 'message3'),
            'A document that was not part of the collection was retrieved!'
        );

        //Let's try another random document
        $document = $collectionHandler->any($collection->getName());
        static::assertContains(
            $document->get('message'),
            array('message1', 'message2', 'message3'),
            'A document that was not part of the collection was retrieved!'
        );

        $collectionHandler->delete($collection->getName());
    }


    /**
     * Test getting a random document from a collection that does not exist
     */
    public function testAnyDocumentInNonExistentCollection()
    {
        $collectionHandler = $this->collectionHandler;

        //To be safe, we need to make sure the collection definitely doesn't exist,
        //so, if it exists, delete it.
        try {
            $collectionHandler->drop('collection_that_does-not_exist');
        } catch (Exception $e) {
            //Ignore the exception.
        }

        try {
            //Let's try to get a random document
            $collectionHandler->any('collection_that_does_not_exist');
        } catch (ServerException $e) {
            static::assertInstanceOf(
                '\triagens\ArangoDb\ServerException',
                $e,
                'Exception thrown was not a ServerException!'
            );
            static::assertEquals(404, $e->getCode(), 'Error code was not a 404!');
        }
    }


    /**
     * Test getting a random document from an empty collection
     */
    public function testAnyDocumentInAnEmptyCollection()
    {

        $collectionHandler = $this->collectionHandler;

        try {
            $collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_Any_Empty');
        } catch (Exception $e) {
            //Ignore
        }

        $collectionHandler->create('ArangoDB_PHP_TestSuite_TestCollection_Any_Empty');

        $any = $collectionHandler->any('ArangoDB_PHP_TestSuite_TestCollection_Any_Empty');

        static::assertNull($any, 'any() on an empty collection should return null.');

        $collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_Any_Empty');
    }


    /**
     * test for fulltext queries
     */
    public function testFulltextQuery()
    {
        $this->collectionHandler = new CollectionHandler($this->connection);
        $documentHandler         = $this->documentHandler;
        $collectionHandler       = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => true)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);
        // First we test without a fulltext index and expect a 400
        try {
            $result = $collectionHandler->fulltext(
                $collection->getName(),
                'someOtherAttribute',
                'someOtherValue'
            );
        } catch (Exception $e) {

        }
        static::assertSame($e->getCode(), 400);

        // Now we create an index
        $fulltextIndexId = $collectionHandler->createFulltextIndex($collection->getId(), array('someOtherAttribute'));
        $fulltextIndexId = $fulltextIndexId['id'];
        $cursor          = $collectionHandler->fulltext(
            $collection->getName(),
            'someOtherAttribute',
            'someOtherValue',
            array('index' => $fulltextIndexId)
        );

        $m = $cursor->getMetadata();
        static::assertEquals($m['count'], 2);
        static::assertEquals($m['hasMore'], false);

        // Now we pass some options
        $cursor = $collectionHandler->fulltext(
            $collection->getName(),
            'someOtherAttribute',
            'someOtherValue',
            array('index' => $fulltextIndexId, 'skip' => 1,)
        );

        $m = $cursor->getMetadata();
        static::assertEquals($m['count'], 1);
        static::assertEquals($m['hasMore'], false);

        $cursor = $collectionHandler->fulltext(
            $collection->getName(),
            'someOtherAttribute',
            'someOtherValue',
            array('batchSize' => 1)
        );

        $m = $cursor->getMetadata();
        static::assertEquals($m['count'], 2);
        static::assertCount(1, $m['result']);
        static::assertEquals($m['hasMore'], true);

    }


    /**
     * test bulk document lookups
     */
    public function testLookupByKeys()
    {
        $documentHandler   = $this->documentHandler;
        $collectionHandler = $this->collectionHandler;

        $collection = Collection::createFromArray(
            array('name' => 'ArangoDB_PHP_TestSuite_TestCollection_01', 'waitForSync' => false)
        );
        $collectionHandler->add($collection);
        $document    = Document::createFromArray(
            array('someAttribute' => 'someValue1', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId  = $documentHandler->add($collection->getId(), $document);
        $document2   = Document::createFromArray(
            array('someAttribute' => 'someValue2', 'someOtherAttribute' => 'someOtherValue2')
        );
        $documentId2 = $documentHandler->add($collection->getId(), $document2);
        $document3   = Document::createFromArray(
            array('someAttribute' => 'someValue3', 'someOtherAttribute' => 'someOtherValue')
        );
        $documentId3 = $documentHandler->add($collection->getId(), $document3);

        static::assertTrue(is_numeric($documentId), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId2), 'Did not return an id!');
        static::assertTrue(is_numeric($documentId3), 'Did not return an id!');

        $keys   = array($documentId, $documentId2, $documentId3);
        $result = $collectionHandler->lookupByKeys($collection->getId(), $keys);
        static::assertCount(3, $result);

        $document = $result[0];
        static::assertInstanceOf(
            '\triagens\ArangoDb\Document',
            $document,
            'Object was not a Document!'
        );

        static::assertEquals($documentId, $document->getId());

        static::assertEquals('someValue1', $document->someAttribute);
        static::assertEquals('someOtherValue', $document->someOtherAttribute);

        $document = $result[1];
        static::assertInstanceOf(
            '\triagens\ArangoDb\Document',
            $document,
            'Object was not a Document!'
        );

        static::assertEquals($documentId2, $document->getId());

        static::assertEquals('someValue2', $document->someAttribute);
        static::assertEquals('someOtherValue2', $document->someOtherAttribute);

        $document = $result[2];
        static::assertInstanceOf(
            '\triagens\ArangoDb\Document',
            $document,
            'Object was not a Document!'
        );

        static::assertEquals($documentId3, $document->getId());

        static::assertEquals('someValue3', $document->someAttribute);
        static::assertEquals('someOtherValue', $document->someOtherAttribute);
    }

    /**
     * test for lookup by keys with unknown collection
     * @expectedException \triagens\ArangoDb\ServerException
     */
    public function testLookupByCollectionNotFound()
    {
        $collectionHandler = $this->collectionHandler;

        $keys   = array('foo');
        $result = $collectionHandler->lookupByKeys('ThisDoesNotExist', $keys);
    }

    /**
     * Test tear-down
     */
    public function tearDown()
    {
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_02');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        try {
            $this->collectionHandler->drop('importCollection_01_arango_unittests');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        try {
            $this->collectionHandler->drop('_ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_Any');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->collectionHandler, $this->collection, $this->connection);
    }
}
