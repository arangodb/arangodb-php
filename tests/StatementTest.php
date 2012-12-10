<?php
/**
 * ArangoDB PHP client testsuite
 * File: statementtest.php
 *
 * @package ArangoDbPhpClient
 * @author Frank Mayer
 */

namespace triagens\ArangoDb;

class StatementTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);
        $this->collection = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection);
    }

    /**
     * This is just a test to really test connectivity with the server before moving on to further tests.
     */
    public function testExecuteStatement()
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
        $statement->setQuery('FOR a IN `ArangoDB_PHP_TestSuite_TestCollection_01` RETURN a');
        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue($result->someAttribute === 'someValue', 'Expected value someValue, found :'.$result->someAttribute);
    }

    
    /**
     * Test if the explain function works
     */
    public function testExplainStatement()
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
        $statement->setQuery('FOR a IN `ArangoDB_PHP_TestSuite_TestCollection_01` RETURN a');
        $result = $statement->explain();

        $this->assertArrayHasKey('plan', $result, "result-array does not contain plan !");    
    }


    /**
     * Test if the validate function works
     */
    public function testValidateStatement()
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
        $statement->setQuery('FOR a IN `ArangoDB_PHP_TestSuite_TestCollection_01` RETURN a');
        $result = $statement->validate();
        $this->assertArrayHasKey('bindVars', $result, "result-array does not contain plan !");    
    }

    
    public function tearDown()
    {
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
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
