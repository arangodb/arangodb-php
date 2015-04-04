<?php

/**
 * ArangoDB PHP client: export
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2015, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Collection export
 *
 * @package triagens\ArangoDb
 * @since   2.6
 */
class Export
{
    /**
     * The connection object
     *
     * @var Connection
     */
    private $_connection = null;

    /**
     * The collection name or collection object
     *
     * @var mixed
     */
    private $_collection = null;

    /**
     * The current batch size (number of result documents retrieved per round-trip)
     *
     * @var mixed
     */
    private $_batchSize = null;

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
     * Count option index
     */
    const ENTRY_COUNT = 'count';

    /**
     * Batch size index
     */
    const ENTRY_BATCHSIZE = 'batchSize';

    /**
     * Initialize the export
     *
     * @throws Exception
     *
     * @param Connection $connection - the connection to be used
     * @param string     $collection - the collection to export
     * @param array      $data       - export options
     */
    public function __construct(Connection $connection, $collection, array $data)
    {
        $this->_connection = $connection;
        $this->_collection = $collection;

        if (isset($data[self::ENTRY_BATCHSIZE])) {
            $this->setBatchSize($data[self::ENTRY_BATCHSIZE]);
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
     * Execute the export
     *
     * This will return the results as a Cursor. The cursor can then be used to iterate the results.
     *
     * @throws Exception
     * @return ExportCursor
     */
    public function execute()
    {
        $data = array("flush" => true);
        if ($this->_batchSize > 0) {
            $data[self::ENTRY_BATCHSIZE] = $this->_batchSize;
        }

        $collection = $this->_collection;
        if ($collection instanceof Collection) {
            $collection = $collection->getName();
        } 
        $url = UrlHelper::appendParamsUrl(Urls::URL_EXPORT, array("collection" => $collection));
        $response = $this->_connection->post($url, $this->getConnection()->json_encode_wrapper($data));
        
        return new Cursor($this->_connection, $response->getJson(), $this->getCursorOptions());
    }

    /**
     * Set the batch size for the export
     *
     * The batch size is the number of results to be transferred
     * in one server round-trip. If an export produces more documents
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
     * Get the batch size for the export
     *
     * @return int - current batch size value
     */
    public function getBatchSize()
    {
        return $this->_batchSize;
    }

    /**
     * Return an array of cursor options
     *
     * @return array - array of options
     */
    private function getCursorOptions()
    {
        $result = array(
            Cursor::ENTRY_SANITIZE => (bool) $this->_sanitize,
            Cursor::ENTRY_FLAT     => (bool) $this->_flat,
            Cursor::ENTRY_BASEURL  => Urls::URL_EXPORT
        );
        return $result;
    }
}
