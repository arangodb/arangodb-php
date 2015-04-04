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
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        $this->collection = new Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection');
        $this->collectionHandler->add($this->collection);

        $this->documentHandler = new DocumentHandler($this->connection);
    }


    /**
     * Test export empty collection
     */
    public function testExportEmpty()
    {
        $connection      = $this->connection;

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        $this->assertEquals(1, $cursor->getFetches());
        $this->assertNull($cursor->getId());

        // we're not expecting any results 
        $this->assertEquals(0, count($cursor->getAll()));
        $this->assertEquals(1, $cursor->getFetches());

        // shouldn't have warnings
        $this->assertEquals(0, count($cursor->getWarnings()));
    }


    /**
     * Test export some documents
     */
    public function testExportDocuments()
    {
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

        $all = $cursor->getAll();
        $this->assertEquals(100, count($all));
    }

    /**
     * Test export some documents w/ multiple fetches
     */
    public function testExportDocumentsTwoFetches()
    {
        $connection      = $this->connection;
        $statement = new Statement($connection, array(
            "query" => "FOR i IN 1..1001 INSERT { _key: CONCAT('test', i), value: i } IN " . $this->collection->getName()
        ));
        $statement->execute();

        $export = new Export($connection, $this->collection, array());
        $cursor = $export->execute();

        $this->assertNotNull($cursor->getId());
        $this->assertEquals(1, $cursor->getFetches());

        // the next call will issue another HTTP fetch command
        $this->assertEquals(1001, $cursor->getCount());
        $this->assertEquals(2, $cursor->getFetches());

        $all = $cursor->getAll();
        $this->assertEquals(1001, count($all));
    }

    /**
     * Test export some documents w/ multiple fetches
     */
    public function testExportDocumentsMultipleFetches()
    {
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
        $this->assertEquals(5, $cursor->getFetches());

        $all = $cursor->getAll();
        $this->assertEquals(5000, count($all));
        
        $this->assertEquals(5, $cursor->getFetches());
    }

    /**
     * Test export some documents
     */
    public function testExportDocumentsWithSmallBatchSize()
    {
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
        $this->assertEquals(50, $cursor->getFetches());
        
        $all = $cursor->getAll();
        $this->assertEquals(5000, count($all));
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
