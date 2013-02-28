<?php
/**
 * ArangoDB PHP client testsuite
 * File: statementtest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

class StatementTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new \triagens\ArangoDb\CollectionHandler($this->connection);

        // clean up first
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
        
        $this->collection        = new \triagens\ArangoDb\Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01');
        $this->collectionHandler->add($this->collection);
    }


    /**
     * This is just a test to really test connectivity with the server before moving on to further tests.
     */
    public function testExecuteStatement()
    {
        $connection        = $this->connection;
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $document          = new \triagens\ArangoDb\Document();
        $documentHandler   = new \triagens\ArangoDb\DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->add($collection->getId(), $document);

        $statement = new \triagens\ArangoDb\Statement($connection, array(
                                                                        "query"     => '',
                                                                        "count"     => true,
                                                                        "batchSize" => 1000,
                                                                        "_sanitize" => true,
                                                                   ));
        $statement->setQuery('FOR a IN `ArangoDB_PHP_TestSuite_TestCollection_01` RETURN a');
        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue(
            $result->someAttribute === 'someValue',
            'Expected value someValue, found :' . $result->someAttribute
        );
    }


    /**
     * This is just a test to really test connectivity with the server before moving on to further tests.
     * We expect an exception here:
     *
     * @expectedException triagens\ArangoDb\ClientException
     */
    public function testExecuteStatementWithWrongEncoding()
    {
        $connection        = $this->connection;
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $document          = new \triagens\ArangoDb\Document();
        $documentHandler   = new \triagens\ArangoDb\DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->add($collection->getId(), $document);

        $statement = new \triagens\ArangoDb\Statement($connection, array(
                                                                        "query"     => '',
                                                                        "count"     => true,
                                                                        "batchSize" => 1000,
                                                                        "_sanitize" => true,
                                                                   ));
        // inject wrong encoding       
        $isoValue = iconv(
            "UTF-8",
            "ISO-8859-1//TRANSLIT",
            "'FOR ü IN `ArangoDB_PHP_TestSuite_TestCollection_01` RETURN ü"
        );

        $statement->setQuery($isoValue);
        $cursor = $statement->execute();

        $result = $cursor->current();

        $this->assertTrue(
            $result->someAttribute === 'someValue',
            'Expected value someValue, found :' . $result->someAttribute
        );
    }


    /**
     * Test if the explain function works
     */
    public function testExplainStatement()
    {
        $connection        = $this->connection;
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $document          = new \triagens\ArangoDb\Document();
        $documentHandler   = new \triagens\ArangoDb\DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->add($collection->getId(), $document);

        $statement = new \triagens\ArangoDb\Statement($connection, array(
                                                                        "query"     => '',
                                                                        "count"     => true,
                                                                        "batchSize" => 1000,
                                                                        "_sanitize" => true,
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
        $connection        = $this->connection;
        $collection        = $this->collection;
        $collectionHandler = $this->collectionHandler;
        $document          = new \triagens\ArangoDb\Document();
        $documentHandler   = new \triagens\ArangoDb\DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->add($collection->getId(), $document);

        $statement = new \triagens\ArangoDb\Statement($connection, array(
                                                                        "query"     => '',
                                                                        "count"     => true,
                                                                        "batchSize" => 1000,
                                                                        "_sanitize" => true,
                                                                   ));
        $statement->setQuery('FOR a IN `ArangoDB_PHP_TestSuite_TestCollection_01` RETURN a');
        $result = $statement->validate();
        $this->assertArrayHasKey('bindVars', $result, "result-array does not contain plan !");
    }
    
    /**
     * Execute a statement that does not produce documents
     */
    public function testExecuteStatementFlat()
    {
        $connection        = $this->connection;

        $statement = new \triagens\ArangoDb\Statement($connection, array(
                                                                        "query"     => 'RETURN UNIQUE([ 1, 1, 2 ])',
                                                                        "count"     => true,
                                                                        "_sanitize" => true,
                                                                        "_flat"     => true
                                                                   ));
        $cursor = $statement->execute();
        $this->assertEquals(
          array(array(1, 2)), 
          $cursor->getAll()
        );
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
