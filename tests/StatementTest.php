<?php
/**
 * ArangoDB PHP client testsuite
 * File: statementtest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDB;

class StatementTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->collection = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDB-PHP-TestSuite-TestCollection-01');
        $this->collectionHandler->add($this->collection);
    }

    /**
     * This is just a test to really test connectivity with the server before moving on to further tests.
     */
    public function testCreateAndDeleteDocument()
    {
        $connection = $this->connection;
        $collection = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $document = new \triagens\ArangoDb\Document();
        $documentHandler = new \triagens\ArangoDb\DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->add($collection->getId(), $document);

        $statement = new \triagens\ArangoDb\Statement($connection, array(
            "query" => '',
            "count" => true,
            "batchSize" => 1000,
            "sanitize" => true,
        ));
        $statement->setQuery('FOR a IN `ArangoDB-PHP-TestSuite-TestCollection-01` RETURN a');
        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->someAttribute === 'someValue', 'Created document id is not numeric!');
    }

    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB-PHP-TestSuite-TestCollection-01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->documentHandler);
        unset($this->document);
        unset($this->collectionHandler);
        unset($this->collection);
        unset($this->connection);
    }
}
