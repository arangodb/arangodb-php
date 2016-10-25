<?php
/**
 * ArangoDB PHP client testsuite
 * File: QueryTest.php
 *
 * @package triagens\ArangoDb
 * @author  Jan Steemann
 */

namespace triagens\ArangoDb;

/**
 * Class QueryTest
 *
 * @property Connection   $connection
 * @property QueryHandler $queryHandler
 *
 * @package triagens\ArangoDb
 */
class QueryTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection   = getConnection();
        $this->queryHandler = new QueryHandler($this->connection);

        $this->queryHandler->clearSlow();
    }

    /**
     * Test current query
     */
    public function testCurrentAndKill()
    {
        $query   = 'RETURN SLEEP(30)';
        $command = 'require("internal").db._query("' . $query . '");';

        // executes the command on the server
        $this->connection->post('/_admin/execute', $command, ['X-Arango-Async' => 'true']);

        // sleep a bit because we do not know when the server will start executing the query
        sleep(3);

        $found = 0;
        foreach ($this->queryHandler->getCurrent() as $q) {
            if ($q['query'] === $query) {
                ++$found;
                $id = $q['id'];
            }
        }
        static::assertEquals(1, $found);

        // now kill the query
        $result = $this->queryHandler->kill($id);
        static::assertTrue($result);
    }

    /**
     * Test slow query - empty
     */
    public function testGetSlowEmpty()
    {
        static::assertEquals([], $this->queryHandler->getSlow());
    }

    /**
     * Test slow query - should contain one query
     */
    public function testGetSlow()
    {
        $query = 'RETURN SLEEP(10)';

        $statement = new Statement($this->connection, ['query' => $query]);
        $statement->execute();

        $found = 0;
        foreach ($this->queryHandler->getSlow() as $q) {
            if ($q['query'] === $query) {
                ++$found;

                static::assertTrue($q['runTime'] >= 10);
            }
        }
        static::assertEquals(1, $found);

        // clear slow log and check that it is empty afterwards
        $this->queryHandler->clearSlow();

        $found = 0;
        foreach ($this->queryHandler->getSlow() as $q) {
            if ($q['query'] === $query) {
                ++$found;
            }
        }
        static::assertEquals(0, $found);
    }


    /**
     * Test getting correct Timeout Exception
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testTimeoutException()
    {
        $query = 'RETURN SLEEP(13)';

        $statement = new Statement($this->connection, ['query' => $query]);

        try {
            $statement->execute();
        } catch (ClientException $exception) {
            static::assertEquals($exception->getCode(), 408);
            throw $exception;
        }
    }

    public function tearDown()
    {
        unset($this->queryHandler, $this->connection);
    }
}
