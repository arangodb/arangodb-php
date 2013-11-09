<?php

/**
 * ArangoDB PHP client: result set cursor
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Provides access to the results of a read-only statement
 *
 * The cursor might not contain all results in the beginning.<br>
 *
 * If the result set is too big to be transferred in one go, the
 * cursor might issue additional HTTP requests to fetch the
 * remaining results from the server.<br>
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class Cursor implements
    \Iterator
{
    /**
     * The connection object
     *
     * @var Connection
     */
    private $_connection;

    /**
     * Cursor options
     *
     * @var array
     */
    private $_options;

    /**
     * The result set
     *
     * @var array
     */
    private $_result;

    /**
     * "has more" indicator - if true, the server has more results
     *
     * @var bool
     */
    private $_hasMore;

    /**
     * cursor id - might be NULL if cursor does not have an id
     *
     * @var mixed
     */
    private $_id;

    /**
     * current position in result set iteration (zero-based)
     *
     * @var int
     */
    private $_position;

    /**
     * total length of result set (in number of documents)
     *
     * @var int
     */
    private $_length;

    /**
     * full count of the result set (ignoring the outermost LIMIT)
     *
     * @var int
     */
    private $_fullCount;

    /**
     * result entry for cursor id
     */
    const ENTRY_ID = 'id';

    /**
     * result entry for "hasMore" flag
     */
    const ENTRY_HASMORE = 'hasMore';

    /**
     * result entry for result documents
     */
    const ENTRY_RESULT = 'result';

    /**
     * result entry for the full count (ignoring the outermost LIMIT)
     */
    const FULL_COUNT = 'fullCount';

    /**
     * sanitize option entry
     */
    const ENTRY_SANITIZE = '_sanitize';

    /**
     * "flat" option entry (will treat the results as a simple array, not documents)
     */
    const ENTRY_FLAT = '_flat';

    /**
     * Initialise the cursor with the first results and some metadata
     *
     * @param Connection $connection - connection to be used
     * @param array      $data       - initial result data as returned by the server
     * @param array      $options    - cursor options
     *
     * @return Cursor
     */
    public function __construct(Connection $connection, array $data, array $options)
    {
        $this->_connection = $connection;
        $this->data        = $data;
        $this->_id         = null;
        if (isset($data[self::ENTRY_ID])) {
            $this->_id = $data[self::ENTRY_ID];
        }

        if (isset($data['extra'][self::FULL_COUNT])) {
            $this->_fullCount = $data['extra'][self::FULL_COUNT];
        }

        // attribute must be there
        assert(isset($data[self::ENTRY_HASMORE]));
        $this->_hasMore = (bool) $data[self::ENTRY_HASMORE];

        $options['isNew'] = false;
        $this->_options   = $options;
        $this->_result    = array();
        $this->add((array) $data[self::ENTRY_RESULT]);
        $this->updateLength();

        $this->rewind();
    }


    /**
     * Explicitly delete the cursor
     *
     * This might issue an HTTP DELETE request to inform the server about
     * the deletion.
     *
     * @throws Exception
     * @return bool - true if the server acknowledged the deletion request, false otherwise
     */
    public function delete()
    {
        if ($this->_id) {
            try {
                $this->_connection->delete(Urls::URL_CURSOR . '/' . $this->_id);

                return true;
            } catch (Exception $e) {
            }
        }

        return false;
    }


    /**
     * Get the total number of results in the cursor
     *
     * This might issue additional HTTP requests to fetch any outstanding
     * results from the server
     *
     * @throws Exception
     * @return int - total number of results
     */
    public function getCount()
    {
        while ($this->_hasMore) {
            $this->fetchOutstanding();
        }

        return $this->_length;
    }

    /**
     * Get the full count of the cursor (ignoring the outermost LIMIT)
     *
     * @return int - total number of results
     */
    public function getFullCount()
    {
        return $this->_fullCount;
    }


    /**
     * Get all results as an array
     *
     * This might issue additional HTTP requests to fetch any outstanding
     * results from the server
     *
     * @throws Exception
     * @return array - an array of all results
     */
    public function getAll()
    {
        while ($this->_hasMore) {
            $this->fetchOutstanding();
        }

        return $this->_result;
    }


    /**
     * Rewind the cursor, necessary for Iterator
     *
     * @return void
     */
    public function rewind()
    {
        $this->_position = 0;
    }


    /**
     * Return the current result row, necessary for Iterator
     *
     * @return array - the current result row as an assoc array
     */
    public function current()
    {
        return $this->_result[$this->_position];
    }


    /**
     * Return the index of the current result row, necessary for Iterator
     *
     * @return int - the current result row index
     */
    public function key()
    {
        return $this->_position;
    }


    /**
     * Advance the cursor, necessary for Iterator
     *
     * @return void
     */
    public function next()
    {
        ++$this->_position;
    }


    /**
     * Check if cursor can be advanced further, necessary for Iterator
     *
     * This might issue additional HTTP requests to fetch any outstanding
     * results from the server
     *
     * @throws Exception
     * @return bool - true if the cursor can be advanced further, false if cursor is at end
     */
    public function valid()
    {
        if ($this->_position <= $this->_length - 1) {
            // we have more results than the current position is
            return true;
        }

        if (!$this->_hasMore || !$this->_id) {
            // we don't have more results, but the cursor is exhausted
            return false;
        }

        // need to fetch additional results from the server
        $this->fetchOutstanding();

        return ($this->_position <= $this->_length - 1);
    }


    /**
     * Create an array of results from the input array
     *
     * @param array $data - incoming result
     *
     * @return void
     */
    private function add(array $data)
    {
        foreach ($this->sanitize($data) as $row) {

            if ((isset($this->_options[self::ENTRY_FLAT]) && $this->_options[self::ENTRY_FLAT]) || !is_array($row)) {
                $this->addFlatFromArray($row);
            } else {
                if (!isset($this->_options['objectType'])) {
                    $this->addDocumentsFromArray($row);
                } else {

                    switch ($this->_options['objectType']) {
                        case 'edge' :
                            $this->addEdgesFromArray($row);

                            break;
                        case 'vertex' :
                            $this->addVerticesFromArray($row);

                            break;
                        default :
                            $this->addDocumentsFromArray($row);

                            break;
                    }
                }
            }
        }
    }


    /**
     * Create an array of results from the input array
     *
     * @param array $data - array of incoming results
     *
     * @return void
     */
    private function addFlatFromArray($data)
    {
        $this->_result[] = $data;
    }


    /**
     * Create an array of documents from the input array
     *
     * @param array $data - array of incoming "document" arrays
     *
     * @return void
     */
    private function addDocumentsFromArray(array $data)
    {
        $this->_result[] = Document::createFromArray($data, $this->_options);
    }


    /**
     * Create an array of Edges from the input array
     *
     * @param array $data - array of incoming "edge" arrays
     *
     * @return void
     */
    private function addEdgesFromArray(array $data)
    {
        $this->_result[] = Edge::createFromArray($data, $this->_options);
    }


    /**
     * Create an array of Vertex from the input array
     *
     * @param array $data - array of incoming "vertex" arrays
     *
     * @return void
     */
    private function addVerticesFromArray(array $data)
    {
        $this->_result[] = Vertex::createFromArray($data, $this->_options);
    }


    /**
     * Sanitize the result set rows
     *
     * This will remove the _id and _rev attributes from the results if the
     * "sanitize" option is set
     *
     * @param array $rows - array of rows to be sanitized
     *
     * @return array - sanitized rows
     */
    private function sanitize(array $rows)
    {
        if (isset($this->_options[self::ENTRY_SANITIZE]) and $this->_options[self::ENTRY_SANITIZE]) {
            foreach ($rows as $key => $value) {

                if (is_array($value) && isset($value[Document::ENTRY_ID])) {
                    unset($rows[$key][Document::ENTRY_ID]);
                }

                if (is_array($value) && isset($value[Document::ENTRY_REV])) {
                    unset($rows[$key][Document::ENTRY_REV]);
                }
            }
        }

        return $rows;
    }


    /**
     * Fetch outstanding results from the server
     *
     * @throws Exception
     * @return void
     */
    private function fetchOutstanding()
    {
        // continuation
        $response = $this->_connection->put(Urls::URL_CURSOR . "/" . $this->_id, '');
        $data     = $response->getJson();

        $this->_hasMore = (bool) $data[self::ENTRY_HASMORE];
        $this->add($data[self::ENTRY_RESULT]);

        if (!$this->_hasMore) {
            // we have fetch the complete result set and can unset the id now
            $this->_id = null;
        }

        $this->updateLength();
    }


    /**
     * Set the length of the (fetched) result set
     *
     * @return void
     */
    private function updateLength()
    {
        $this->_length = count($this->_result);
    }


    /**
     * Get MetaData of the current cursor
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->data;
    }
}
