<?php
/**
 * ArangoDB PHP client testsuite
 * File: ExportTest.php
 *
 * @package triagens\ArangoDb
 * @author  Jan Steemann
 */

namespace triagens\ArangoDb;

/**
 * @property Connection $connection
 * @property Collection $collection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler $documentHandler
 *
 * @package triagens\ArangoDb
 */
class ExportTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);

        // clean up first
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        $this->collection = new Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection');
        $this->collectionHandler->add($this->collection);

        $this->documentHandler = new DocumentHandler($this->connection);

        $adminHandler       = new AdminHandler($this->connection);
        $version            = preg_replace("/-[a-z0-9]+$/", '', $adminHandler->getServerVersion());
        $this->hasExportApi = (version_compare($version, '2.6.0') >= 0);
    }

    /**
     * Test export empty collection
     */
    public function testExportEmpty()
    {
        if (!$this->hasExportApi) {
            return;
        }
        $connection = $this->connection;

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNull($cursor->getId());

        // we're not expecting any results 
        static::assertEquals(0, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export some documents
     */
    public function testExportDocuments()
    {
        if (!$this->hasExportApi) {
            return;
        }
        $connection = $this->connection;
        for ($i = 0; $i < 100; ++$i) {
            $this->documentHandler->save($this->collection, array('value' => $i));
        }

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNull($cursor->getId());

        static::assertEquals(100, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = $cursor->getNextBatch();
        static::assertCount(100, $all);

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export some documents w/ multiple fetches
     */
    public function testExportDocumentsTwoFetches()
    {
        if (!$this->hasExportApi) {
            return;
        }
        $connection = $this->connection;
        $statement  = new Statement(
            $connection, array(
                           'query' => "FOR i IN 1..1001 INSERT { _key: CONCAT('test', i), value: i } IN " . $this->collection->getName()
                       )
        );
        $statement->execute();

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        static::assertNotNull($cursor->getId());
        static::assertEquals(1, $cursor->getFetches());

        static::assertEquals(1001, $cursor->getCount());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertEquals(2, $cursor->getFetches());
        static::assertCount(1001, $all);

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export some documents w/ multiple fetches
     */
    public function testExportDocumentsMultipleFetches()
    {
        if (!$this->hasExportApi) {
            return;
        }
        $connection = $this->connection;
        $statement  = new Statement(
            $connection, array(
                           'query' => "FOR i IN 1..5000 INSERT { _key: CONCAT('test', i), value: i } IN " . $this->collection->getName()
                       )
        );
        $statement->execute();

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNotNull($cursor->getId());

        static::assertEquals(5000, $cursor->getCount());
        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertEquals(5, $cursor->getFetches());
        static::assertCount(5000, $all);

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export some documents
     */
    public function testExportDocumentsWithSmallBatchSize()
    {
        if (!$this->hasExportApi) {
            return;
        }
        $connection = $this->connection;
        $statement  = new Statement(
            $connection, array(
                           'query' => "FOR i IN 1..5000 INSERT { _key: CONCAT('test', i), value: i } IN " . $this->collection->getName()
                       )
        );
        $statement->execute();

        $export = new Export($connection, $this->collection, array('batchSize' => 100));
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNotNull($cursor->getId());

        static::assertEquals(5000, $cursor->getCount());
        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertEquals(50, $cursor->getFetches());
        static::assertCount(5000, $all);

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export as Document object
     */
    public function testExportDocumentObjects()
    {
        if (!$this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 100; ++$i) {
            $this->documentHandler->save($this->collection, array('value' => $i));
        }

        $export = new Export($this->connection, $this->collection, array('_flat' => false));
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNull($cursor->getId());

        static::assertEquals(100, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = $cursor->getNextBatch();
        static::assertCount(100, $all);

        foreach ($all as $doc) {
            static::assertInstanceOf(Document::class, $doc);
        }

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export as Edge object
     */
    public function testExportEdgeObjects()
    {
        if (!$this->hasExportApi) {
            return;
        }

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestEdge');
        } catch (\Exception $e) {
        }

        $edgeCollection = new Collection();
        $edgeCollection->setName('ArangoDB_PHP_TestSuite_TestEdge');
        $edgeCollection->setType(Collection::TYPE_EDGE);
        $this->collectionHandler->add($edgeCollection);

        $edgeHandler = new EdgeHandler($this->connection);

        $vertexCollection = $this->collection->getName();

        for ($i = 0; $i < 100; ++$i) {
            $edgeHandler->saveEdge($edgeCollection, $vertexCollection . '/1', $vertexCollection . '/2', array('value' => $i));
        }

        $export = new Export($this->connection, $edgeCollection, array('_flat' => false));
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNull($cursor->getId());

        static::assertEquals(100, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = $cursor->getNextBatch();
        static::assertCount(100, $all);

        foreach ($all as $doc) {
            static::assertInstanceOf(Document::class, $doc);
            static::assertInstanceOf(Edge::class, $doc);
        }

        static::assertFalse($cursor->getNextBatch());

        $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestEdge');
    }

    /**
     * Test export as flat array
     */
    public function testExportFlat()
    {
        if (!$this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array('value' => $i));
        }

        $export = new Export($this->connection, $this->collection, array('batchSize' => 50, '_flat' => true));
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNotNull($cursor->getId());

        static::assertEquals(200, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertCount(200, $all);

        foreach ($all as $doc) {
            static::assertFalse($doc instanceof Document);
            static::assertTrue(is_array($doc));
        }

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export with limit
     */
    public function testExportLimit()
    {
        if (!$this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array('value' => $i));
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'batchSize' => 50,
                                 '_flat' => true,
                                 'limit' => 107
                             )
        );
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNotNull($cursor->getId());

        static::assertEquals(107, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertCount(107, $all);

        foreach ($all as $doc) {
            static::assertFalse($doc instanceof Document);
            static::assertTrue(is_array($doc));
        }

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export with include restriction
     */
    public function testExportRestrictInclude()
    {
        if (!$this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array('value1' => $i, 'value2' => 'test' . $i));
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'batchSize' => 50,
                                 '_flat' => true,
                                 'restrict' => array('type' => 'include', 'fields' => array('_key', 'value2'))
                             )
        );
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNotNull($cursor->getId());

        static::assertEquals(200, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertCount(200, $all);

        foreach ($all as $doc) {
            static::assertTrue(is_array($doc));
            static::assertCount(2, $doc);
            static::assertFalse(isset($doc['_id']));
            static::assertTrue(isset($doc['_key']));
            static::assertFalse(isset($doc['_rev']));
            static::assertFalse(isset($doc['value1']));
            static::assertTrue(isset($doc['value2']));
        }

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export with include restriction
     */
    public function testExportRestrictIncludeNonExisting()
    {
        if (!$this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array('value1' => $i, 'value2' => 'test' . $i));
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'batchSize' => 50,
                                 '_flat' => true,
                                 'restrict' => array('type' => 'include', 'fields' => array('foobar', 'baz'))
                             )
        );
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNotNull($cursor->getId());

        static::assertEquals(200, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertCount(200, $all);

        foreach ($all as $doc) {
            static::assertTrue(is_array($doc));
            static::assertEquals(array(), $doc);
        }

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export with exclude restriction
     */
    public function testExportRestrictExclude()
    {
        if (!$this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array('value1' => $i, 'value2' => 'test' . $i));
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'batchSize' => 50,
                                 '_flat' => true,
                                 'restrict' => array('type' => 'exclude', 'fields' => array('_key', 'value2'))
                             )
        );
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNotNull($cursor->getId());

        static::assertEquals(200, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertCount(200, $all);

        foreach ($all as $doc) {
            static::assertTrue(is_array($doc));
            static::assertCount(3, $doc);
            static::assertFalse(isset($doc['_key']));
            static::assertTrue(isset($doc['_rev']));
            static::assertTrue(isset($doc['_id']));
            static::assertTrue(isset($doc['value1']));
            static::assertFalse(isset($doc['value2']));
        }

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export with non-existing fields restriction
     */
    public function testExportRestrictExcludeNonExisting()
    {
        if (!$this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array('value1' => $i, 'value2' => 'test' . $i));
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'batchSize' => 50,
                                 '_flat' => true,
                                 'restrict' => array('type' => 'include', 'fields' => array('_id', 'foobar', 'baz'))
                             )
        );
        $cursor = $export->execute();

        static::assertEquals(1, $cursor->getFetches());
        static::assertNotNull($cursor->getId());

        static::assertEquals(200, $cursor->getCount());
        static::assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
            $all = array_merge($all, $more);
        }
        static::assertCount(200, $all);

        foreach ($all as $doc) {
            static::assertTrue(is_array($doc));
            static::assertCount(1, $doc);
            static::assertTrue(isset($doc['_id']));
            static::assertFalse(isset($doc['foobar']));
        }

        static::assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export with invalid restriction definition
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testExportRestrictInvalidType()
    {
        if (!$this->hasExportApi) {
            throw new ClientException('Invalid restrictions type definition');
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'restrict' => array('type' => 'foo', 'fields' => array('_key'))
                             )
        );
        $cursor = $export->execute();
    }

    /**
     * Test export with invalid restriction definition
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testExportRestrictMissingType()
    {
        if (!$this->hasExportApi) {
            throw new ClientException('Invalid restrictions type definition');
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'restrict' => array('fields' => array('_key'))
                             )
        );
        $cursor = $export->execute();
    }

    /**
     * Test export with invalid restriction definition
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testExportRestrictInvalidFields()
    {
        if (!$this->hasExportApi) {
            throw new ClientException('Invalid restrictions fields definition');
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'restrict' => array('type' => 'include', 'fields' => 'foo')
                             )
        );
        $cursor = $export->execute();
    }

    /**
     * Test export with invalid restriction definition
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testExportRestrictMissingFields()
    {
        if (!$this->hasExportApi) {
            throw new ClientException('Invalid restrictions fields definition');
        }

        $export = new Export(
            $this->connection, $this->collection, array(
                                 'restrict' => array('type' => 'include')
                             )
        );
        $cursor = $export->execute();
    }

    public function tearDown()
    {
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->documentHandler, $this->collectionHandler, $this->collection, $this->connection);
    }

}
