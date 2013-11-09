<?php

/**
 * ArangoDB PHP client: statement
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Container for a read-only ("select") statement
 *
 * A statement is an AQL query that can be issued to the
 * server. Optional bind parameters can be used when issuing the
 * statement to separate the statement from the values.
 * Executing a statement will result in a cursor being created.
 *
 * There is an important distinction between two different types of statements:
 * <ul>
 * <li> statements that produce a list of documents as their result AND<br />
 * <li> statements that do not produce documents
 * </ul>
 *
 * For example, a statement such as "FOR e IN example RETURN e" will produce
 * a list of documents as its result. The result can be treated as a list of
 * documents, and the document can be updated and sent back to the server by
 * the client.<br />
 * <br />
 * However, the query "RETURN 1 + 1" will not produce a list of documents as
 * its result, but a list with a single scalar value (the number 2).
 * "2" is not a valid document so creating a document from it will fail.<br />
 * <br />
 * To turn the results of this query into a document, the following needs to
 * be done:
 * <ul>
 * <li> modify the query to "RETURN { value: 1 + 1 }". The result will then be a
 *   a list of documents with a "value" attribute<br />
 * <li> use the "_flat" option for the statement to indicate that you don't want
 *   to treat the statement result as a list of documents, but as a flat list
 * </ul>
 *
 * <br />
 *
 * @package triagens\ArangoDb
 * @since   0.2
 */
class Statement
{
    /**
     * The connection object
     *
     * @var Connection
     */
    private $_connection = null;

    /**
     * The bind variables and values used for the statement
     *
     * @var BindVars
     */
    private $_bindVars;

    /**
     * The current batch size (number of result documents retrieved per round-trip)
     *
     * @var mixed
     */
    private $_batchSize = null;

    /**
     * The count flag (should server return total number of results)
     *
     * @var bool
     */
    private $_doCount = false;

    /**
     * The count flag (should server return total number of results ignoring the limit)
     * Be careful! This option also prevents ArangoDB from using some server side optimizations!
     *
     * @var bool
     */
    private $_fullCount = false;

    /**
     * The query string
     *
     * @var string
     */
    private $_query = null;

    /**
     * "flat" flag (if set, the query results will be treated as a simple array, not documents)
     *
     * @var bool
     */
    private $_flat = false;

    /**
     * Sanitation flag (if set, the _id and _rev attributes will be removed from the results)
     *
     * @var bool
     */
    private $_sanitize = false;

    /**
     * Query string index
     */
    const ENTRY_QUERY = 'query';

    /**
     * Count option index
     */
    const ENTRY_COUNT = 'count';

    /**
     * Batch size index
     */
    const ENTRY_BATCHSIZE = 'batchSize';

    /**
     * Bind variables index
     */
    const ENTRY_BINDVARS = 'bindVars';

    /**
     * Full count option index
     */
    const FULL_COUNT = 'fullCount';

    /**
     * Initialise the statement
     *
     * The $data property can be used to specify the query text and further
     * options for the query.
     *
     * An important consideration when creating a statement is whether the
     * statement will produce a list of documents as its result or any other
     * non-document value. When a statement is created, by default it is
     * assumed that the statement will produce documents. If this is not the
     * case, executing a statement that returns non-documents will fail.
     *
     * To explicitly mark the statement as returning non-documents, the '_flat'
     * option should be specified in $data.
     *
     * @throws Exception
     *
     * @param Connection $connection - the connection to be used
     * @param array      $data       - statement initialization data
     */
    public function __construct(Connection $connection, array $data)
    {
        $this->_connection = $connection;
        $this->_bindVars   = new BindVars();

        $this->setQuery(@$data[self::ENTRY_QUERY]);

        if (isset($data[self::ENTRY_COUNT])) {
            $this->setCount($data[self::ENTRY_COUNT]);
        }

        if (isset($data[self::ENTRY_BATCHSIZE])) {
            $this->setBatchSize($data[self::ENTRY_BATCHSIZE]);
        }

        if (isset($data[self::ENTRY_BINDVARS])) {
            $this->_bindVars->set($data[self::ENTRY_BINDVARS]);
        }

        if (isset($data[self::FULL_COUNT])) {
            $this->_fullCount = (bool) $data[Cursor::FULL_COUNT];
        }

        if (isset($data[Cursor::ENTRY_SANITIZE])) {
            $this->_sanitize = (bool) $data[Cursor::ENTRY_SANITIZE];
        }

        if (isset($data[Cursor::ENTRY_FLAT])) {
            $this->_flat = (bool) $data[Cursor::ENTRY_FLAT];
        }
    }

    /**
     * Return the connection object
     *
     * @return Connection - the connection object
     */
    protected function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Execute the statement
     *
     * This will post the query to the server and return the results as
     * a Cursor. The cursor can then be used to iterate the results.
     *
     * @throws Exception
     * @return Cursor
     */
    public function execute()
    {
        $data     = $this->buildData();
        $response = $this->_connection->post(Urls::URL_CURSOR, $this->getConnection()->json_encode_wrapper($data));

        return new Cursor($this->_connection, $response->getJson(), $this->getCursorOptions());
    }


    /**
     * Explain the statement's execution plan
     *
     * This will post the query to the server and return the execution plan as an array.
     *
     * @throws Exception
     * @return Array
     */
    public function explain()
    {
        $data     = $this->buildData();
        $response = $this->_connection->post(Urls::URL_EXPLAIN, $this->getConnection()->json_encode_wrapper($data));

        return $response->getJson();
    }


    /**
     * Validates the statement
     *
     * This will post the query to the server for validation and return the validation result as an array.
     *
     * @throws Exception
     * @return Array
     */
    public function validate()
    {
        $data     = $this->buildData();
        $response = $this->_connection->post(Urls::URL_QUERY, $this->getConnection()->json_encode_wrapper($data));

        return $response->getJson();
    }


    /**
     * Invoke the statement
     *
     * This will simply call execute(). Arguments are ignored.
     *
     * @throws Exception
     *
     * @param mixed $args - arguments for invocation, will be ignored
     *
     * @return Cursor - the result cursor
     */
    public function __invoke($args)
    {
        return $this->execute();
    }

    /**
     * Return a string representation of the statement
     *
     * @return string - the current query string
     */
    public function __toString()
    {
        return $this->_query;
    }

    /**
     * Bind a parameter to the statement
     *
     * This method can either be called with a string $key and a
     * separate value in $value, or with an array of all bind
     * bind parameters in $key, with $value being NULL.
     *
     * Allowed value types for bind parameters are string, int,
     * double, bool and array. Arrays must not contain any other
     * than these types.
     *
     * @throws Exception
     *
     * @param mixed $key   - name of bind variable OR an array of all bind variables
     * @param mixed $value - value for bind variable
     *
     * @return void
     */
    public function bind($key, $value = null)
    {
        $this->_bindVars->set($key, $value);
    }

    /**
     * Get all bind parameters as an array
     *
     * @return array - array of bind variables/values
     */
    public function getBindVars()
    {
        return $this->_bindVars->getAll();
    }

    /**
     * Set the query string
     *
     * @throws ClientException
     *
     * @param string $query - query string
     *
     * @return void
     */
    public function setQuery($query)
    {
        if (!is_string($query)) {
            throw new ClientException('Query should be a string');
        }

        $this->_query = $query;
    }

    /**
     * Get the query string
     *
     * @return string - current query string value
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Set the count option for the statement
     *
     * @param bool $value - value for count option
     *
     * @return void
     */
    public function setCount($value)
    {
        $this->_doCount = (bool) $value;
    }

    /**
     * Get the count option value of the statement
     *
     * @return bool - current value of count option
     */
    public function getCount()
    {
        return $this->_doCount;
    }

    /**
     * Set the full count option for the statement
     *
     * @param bool $value - value for full count option
     *
     * @return void
     */
    public function setFullCount($value)
    {
        $this->_fullCount = (bool) $value;
    }

    /**
     * Get the full count option value of the statement
     *
     * @return bool - current value of full count option
     */
    public function getFullCount()
    {
        return $this->_fullCount;
    }

    /**
     * Set the batch size for the statement
     *
     * The batch size is the number of results to be transferred
     * in one server round-trip. If a query produces more results
     * than the batch size, it creates a server-side cursor that
     * provides the additional results.
     *
     * The server-side cursor can be accessed by the client with subsequent HTTP requests.
     *
     * @throws ClientException
     *
     * @param int $value - batch size value
     *
     * @return void
     */
    public function setBatchSize($value)
    {
        if (!is_int($value) || (int) $value <= 0) {
            throw new ClientException('Batch size should be a positive integer');
        }

        $this->_batchSize = (int) $value;
    }

    /**
     * Get the batch size for the statement
     *
     * @return int - current batch size value
     */
    public function getBatchSize()
    {
        return $this->_batchSize;
    }

    /**
     * Build an array of data to be posted to the server when issuing the statement
     *
     * @return array - array of data to be sent to server
     */
    private function buildData()
    {
        $data = array(
            self::ENTRY_QUERY => $this->_query,
            self::ENTRY_COUNT => $this->_doCount,
            'options'         => array(
                self::FULL_COUNT => $this->_fullCount
            )
        );

        if ($this->_bindVars->getCount() > 0) {
            $data[self::ENTRY_BINDVARS] = $this->_bindVars->getAll();
        }

        if ($this->_batchSize > 0) {
            $data[self::ENTRY_BATCHSIZE] = $this->_batchSize;
        }

        return $data;
    }

    /**
     * Return an array of cursor options
     *
     * @return array - array of options
     */
    private function getCursorOptions()
    {
        return array(
            Cursor::ENTRY_SANITIZE => (bool) $this->_sanitize,
            Cursor::ENTRY_FLAT     => (bool) $this->_flat
        );
    }
}
