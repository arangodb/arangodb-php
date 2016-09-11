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
 * @property Connection        $connection
 * @property Collection        $collection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler   $documentHandler
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

        $adminHandler = new AdminHandler($this->connection);
        $version = preg_replace("/-[a-z0-9]+$/", "", $adminHandler->getServerVersion());
        $this->hasExportApi = (version_compare($version, '2.6.0') >= 0);
    }

    /**
     * Test export empty collection
     */
    public function testExportEmpty()
    {
        if (! $this->hasExportApi) {
            return;
        }
        $connection      = $this->connection;

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNull($cursor->getId());

        // we're not expecting any results 
        $this->assertEquals(0, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());
        
        $this->assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export some documents
     */
    public function testExportDocuments()
    {
        if (! $this->hasExportApi) {
            return;
        }
        $connection      = $this->connection;
        for ($i = 0; $i < 100; ++$i) {
            $this->documentHandler->save($this->collection, array("value" => $i));
        }

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNull($cursor->getId());

        $this->assertEquals(100, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = $cursor->getNextBatch();
        $this->assertEquals(100, count($all));
        
        $this->assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export some documents w/ multiple fetches
     */
    public function testExportDocumentsTwoFetches()
    {
        if (! $this->hasExportApi) {
            return;
        }
        $connection      = $this->connection;
        $statement = new Statement($connection, array(
            "query" => "FOR i IN 1..1001 INSERT { _key: CONCAT('test', i), value: i } IN " . $this->collection->getName()
        ));
        $statement->execute();

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        $this->assertNotNull($cursor->getId());
        $this->assertEquals(1, $cursor->getFetches());

        $this->assertEquals(1001, $cursor->getCount());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(2, $cursor->getFetches());
        $this->assertEquals(1001, count($all));

        $this->assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export some documents w/ multiple fetches
     */
    public function testExportDocumentsMultipleFetches()
    {
        if (! $this->hasExportApi) {
            return;
        }
        $connection      = $this->connection;
        $statement = new Statement($connection, array(
            "query" => "FOR i IN 1..5000 INSERT { _key: CONCAT('test', i), value: i } IN " . $this->collection->getName()
        ));
        $statement->execute();

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNotNull($cursor->getId());

        $this->assertEquals(5000, $cursor->getCount());
        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(5, $cursor->getFetches());
        $this->assertEquals(5000, count($all));

        $this->assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export some documents
     */
    public function testExportDocumentsWithSmallBatchSize()
    {
        if (! $this->hasExportApi) {
            return;
        }
        $connection      = $this->connection;
        $statement = new Statement($connection, array(
            "query" => "FOR i IN 1..5000 INSERT { _key: CONCAT('test', i), value: i } IN " . $this->collection->getName()
        ));
        $statement->execute();

        $export = new Export($connection, $this->collection, array("batchSize" => 100));
        $cursor = $export->execute();
        
        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNotNull($cursor->getId());

        $this->assertEquals(5000, $cursor->getCount());
        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(50, $cursor->getFetches());
        $this->assertEquals(5000, count($all));

        $this->assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export as Document object
     */
    public function testExportDocumentObjects()
    {
        if (! $this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 100; ++$i) {
            $this->documentHandler->save($this->collection, array("value" => $i));
        }

        $export = new Export($this->connection, $this->collection, array("_flat" => false));
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNull($cursor->getId());

        $this->assertEquals(100, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = $cursor->getNextBatch();
        $this->assertEquals(100, count($all));

        foreach ($all as $doc) {
            $this->assertTrue($doc instanceof Document);
        }
        
        $this->assertFalse($cursor->getNextBatch());
    }

    /**
     * Test export as Edge object
     */
    public function testExportEdgeObjects()
    {
        if (! $this->hasExportApi) {
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
            $edgeHandler->saveEdge($edgeCollection, $vertexCollection . "/1", $vertexCollection . "/2", array("value" => $i));
        }

        $export = new Export($this->connection, $edgeCollection, array("_flat" => false));
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNull($cursor->getId());

        $this->assertEquals(100, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = $cursor->getNextBatch();
        $this->assertEquals(100, count($all));

        foreach ($all as $doc) {
            $this->assertTrue($doc instanceof Document);
            $this->assertTrue($doc instanceof Edge);
        }
        
        $this->assertFalse($cursor->getNextBatch());

        $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestEdge');
    }

    /**
     * Test export as flat array
     */
    public function testExportFlat()
    {
        if (! $this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array("value" => $i));
        }

        $export = new Export($this->connection, $this->collection, array("batchSize" => 50, "_flat" => true));
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNotNull($cursor->getId());

        $this->assertEquals(200, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(200, count($all));

        foreach ($all as $doc) {
            $this->assertFalse($doc instanceof Document);
            $this->assertTrue(is_array($doc));
        }
        
        $this->assertFalse($cursor->getNextBatch());
    }
    
    /**
     * Test export with limit
     */
    public function testExportLimit()
    {
        if (! $this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array("value" => $i));
        }

        $export = new Export($this->connection, $this->collection, array("batchSize" => 50, "_flat" => true, "limit" => 107));
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNotNull($cursor->getId());

        $this->assertEquals(107, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(107, count($all));

        foreach ($all as $doc) {
            $this->assertFalse($doc instanceof Document);
            $this->assertTrue(is_array($doc));
        }
        
        $this->assertFalse($cursor->getNextBatch());
    }
    
    /**
     * Test export with include restriction
     */
    public function testExportRestrictInclude()
    {
        if (! $this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array("value1" => $i, "value2" => "test" . $i));
        }

        $export = new Export($this->connection, $this->collection, array(
            "batchSize" => 50, 
            "_flat" => true, 
            "restrict" => array("type" => "include", "fields" => array("_key", "value2"))
        ));
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNotNull($cursor->getId());

        $this->assertEquals(200, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(200, count($all));

        foreach ($all as $doc) {
            $this->assertTrue(is_array($doc));
            $this->assertEquals(2, count($doc));
            $this->assertFalse(isset($doc["_id"]));
            $this->assertTrue(isset($doc["_key"]));
            $this->assertFalse(isset($doc["_rev"]));
            $this->assertFalse(isset($doc["value1"]));
            $this->assertTrue(isset($doc["value2"]));
        }
        
        $this->assertFalse($cursor->getNextBatch());
    }
    
    /**
     * Test export with include restriction
     */
    public function testExportRestrictIncludeNonExisting()
    {
        if (! $this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array("value1" => $i, "value2" => "test" . $i));
        }

        $export = new Export($this->connection, $this->collection, array(
            "batchSize" => 50, 
            "_flat" => true, 
            "restrict" => array("type" => "include", "fields" => array("foobar", "baz"))
        ));
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNotNull($cursor->getId());

        $this->assertEquals(200, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(200, count($all));

        foreach ($all as $doc) {
            $this->assertTrue(is_array($doc));
            $this->assertEquals(array(), $doc);
        }
        
        $this->assertFalse($cursor->getNextBatch());
    }
    
    /**
     * Test export with exclude restriction
     */
    public function testExportRestrictExclude()
    {
        if (! $this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array("value1" => $i, "value2" => "test" . $i));
        }

        $export = new Export($this->connection, $this->collection, array(
            "batchSize" => 50, 
            "_flat" => true, 
            "restrict" => array("type" => "exclude", "fields" => array("_key", "value2"))
        ));
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNotNull($cursor->getId());

        $this->assertEquals(200, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(200, count($all));

        foreach ($all as $doc) {
            $this->assertTrue(is_array($doc));
            $this->assertEquals(3, count($doc));
            $this->assertFalse(isset($doc["_key"]));
            $this->assertTrue(isset($doc["_rev"]));
            $this->assertTrue(isset($doc["_id"]));
            $this->assertTrue(isset($doc["value1"]));
            $this->assertFalse(isset($doc["value2"]));
        }
        
        $this->assertFalse($cursor->getNextBatch());
    }
    
    /**
     * Test export with non-existing fields restriction
     */
    public function testExportRestrictExcludeNonExisting()
    {
        if (! $this->hasExportApi) {
            return;
        }
        for ($i = 0; $i < 200; ++$i) {
            $this->documentHandler->save($this->collection, array("value1" => $i, "value2" => "test" . $i));
        }

        $export = new Export($this->connection, $this->collection, array(
            "batchSize" => 50, 
            "_flat" => true, 
            "restrict" => array("type" => "include", "fields" => array("_id", "foobar", "baz"))
        ));
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNotNull($cursor->getId());

        $this->assertEquals(200, $cursor->getCount());
        $this->assertEquals(1, $cursor->getFetches());

        $all = array();
        while ($more = $cursor->getNextBatch()) {
          $all = array_merge($all, $more);
        }
        $this->assertEquals(200, count($all));

        foreach ($all as $doc) {
            $this->assertTrue(is_array($doc));
            $this->assertEquals(1, count($doc));
            $this->assertTrue(isset($doc["_id"]));
            $this->assertFalse(isset($doc["foobar"]));
        }
        
        $this->assertFalse($cursor->getNextBatch());
    }
           
    /**
     * Test export with invalid restriction definition
     *
     * @expectedException \triagens\ArangoDb\ClientException 
     */
    public function testExportRestrictInvalidType()
    {
        if (! $this->hasExportApi) {
            throw new ClientException('Invalid restrictions type definition');
        }

        $export = new Export($this->connection, $this->collection, array(
            "restrict" => array("type" => "foo", "fields" => array("_key"))
        ));
        $cursor = $export->execute();
    }
    
    /**
     * Test export with invalid restriction definition
     *
     * @expectedException \triagens\ArangoDb\ClientException 
     */
    public function testExportRestrictMissingType()
    {
        if (! $this->hasExportApi) {
            throw new ClientException('Invalid restrictions type definition');
        }

        $export = new Export($this->connection, $this->collection, array(
            "restrict" => array("fields" => array("_key"))
        ));
        $cursor = $export->execute();
    }
    
    /**
     * Test export with invalid restriction definition
     *
     * @expectedException \triagens\ArangoDb\ClientException 
     */
    public function testExportRestrictInvalidFields()
    {
        if (! $this->hasExportApi) {
            throw new ClientException('Invalid restrictions fields definition');
        }

        $export = new Export($this->connection, $this->collection, array(
            "restrict" => array("type" => "include", "fields" => "foo")
        ));
        $cursor = $export->execute();
    }
    
    /**
     * Test export with invalid restriction definition
     *
     * @expectedException \triagens\ArangoDb\ClientException 
     */
    public function testExportRestrictMissingFields()
    {
        if (! $this->hasExportApi) {
            throw new ClientException('Invalid restrictions fields definition');
        }

        $export = new Export($this->connection, $this->collection, array(
            "restrict" => array("type" => "include")
        ));
        $cursor = $export->execute();
    }

    public function tearDown()
    {
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->documentHandler);
        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }

}
