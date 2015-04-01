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
     * extra data (statistics) returned from the statement
     *
     * @var array
     */
    private $_extra;

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
     * result entry for extra data
     */
    const ENTRY_EXTRA = 'extra';
    
    /**
     * result entry for stats
     */
    const ENTRY_STATS = 'stats';

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
     * "objectType" option entry.
     */
    const ENTRY_TYPE = 'objectType';

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
        $this->_extra      = array();

        if (isset($data[self::ENTRY_ID])) {
            $this->_id = $data[self::ENTRY_ID];
        }
          

        if (isset($data[self::ENTRY_EXTRA])) {
            // ArangoDB 2.3+ return value struct
            $this->_extra = $data[self::ENTRY_EXTRA];
          
            if (isset($this->_extra[self::ENTRY_STATS][self::FULL_COUNT])) {
                $this->_fullCount = $this->_extra[self::ENTRY_STATS][self::FULL_COUNT];
            }
        }
        else if (isset($data[self::ENTRY_EXTRA][self::FULL_COUNT])) {
            // pre-ArangoDB 2.3 return value struct
            $this->_fullCount = $data[self::ENTRY_EXTRA][self::FULL_COUNT];
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
                        case 'path' :
                            $this->addPathsFromArray($row);
                            break;
                        case 'shortestPath' :
                            $this->addShortestPathFromArray($row);
                            break;
                        case 'distanceTo' :
                            $this->addDistanceToFromArray($row);
                            break;
                        case 'commonNeighbors' :
                            $this->addCommonNeighborsFromArray($row);
                            break;
                        case 'commonProperties' :
                            $this->addCommonPropertiesFromArray($row);
                            break;
                        case 'figure' :
                            $this->addFigureFromArray($row);
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
     * Create an array of paths from the input array
     *
     * @param array $data - array of incoming "paths" arrays
     *
     * @return void
     */
    private function addPathsFromArray(array $data)
    {	
    	$entry = array(
    		"vertices" => array(),
    		"edges" => array(),
    		"source" => Document::createFromArray($data["source"], $this->_options),
    		"destination" => Document::createFromArray($data["destination"], $this->_options),
    	);
    	foreach ($data["vertices"] as $v) {
    		$entry["vertices"][] = Document::createFromArray($v, $this->_options);
    	}
    	foreach ($data["edges"] as $v) {
    		$entry["edges"][] = Edge::createFromArray($v, $this->_options);
    	}
    	$this->_result[] = $entry;
    }
    
    /**
     * Create an array of shortest paths from the input array
     *
     * @param array $data - array of incoming "paths" arrays
     *
     * @return void
     */
    private function addShortestPathFromArray(array $data)
    {
    	$entry = array(
    			"paths" => array (),
    			"source" => $data["startVertex"],
    			"distance" => $data["distance"],
    			"destination" => Document::createFromArray($data["vertex"], $this->_options),
    	);
    	foreach ($data["paths"] as $p) {
    		$path = array (
    				"vertices" => array(),
    				"edges" => array()
    		);
    		foreach ($p["vertices"] as $v) {
	    		$path["vertices"][] = $v;
	    	}
	    	foreach ($p["edges"] as $v) {
	    		$path["edges"][] = Edge::createFromArray($v, $this->_options);
	    	}
	    	$entry["paths"][] = $path;
    	}
    	$this->_result[] = $entry;
    }
    
    
    /**
     * Create an array of distances from the input array
     *
     * @param array $data - array of incoming "paths" arrays
     *
     * @return void
     */
    private function addDistanceToFromArray(array $data)
    {
    	$entry = array(
    			"source" => $data["startVertex"],
    			"distance" => $data["distance"],
    			"destination" => Document::createFromArray($data["vertex"], $this->_options),
    	);
    	$this->_result[] = $entry;
    }
    
    /**
     * Create an array of common neighbors from the input array
     *
     * @param array $data - array of incoming "paths" arrays
     *
     * @return void
     */
    private function addCommonNeighborsFromArray(array $data)
    {	
    	$k = array_keys($data);
    	$k = $k[0];
    	$this->_result[$k] = array();
    	
     	foreach ($data[$k] as $neighbor => $neighbors) {
    		$this->_result[$k][$neighbor] = array();
    		foreach ($neighbors as $n) {
    			$this->_result[$k][$neighbor][] = Document::createFromArray($n);
    		}
     	}
    }
    
    /**
     * Create an array of common properties from the input array
     *
     * @param array $data - array of incoming "paths" arrays
     *
     * @return void
     */
    private function addCommonPropertiesFromArray(array $data)
    {	
    	$k = array_keys($data);
    	$k = $k[0];
     	$this->_result[$k] = array();
      	foreach ($data[$k] as $c) {
      		$id = $c["_id"];
      		unset($c["_id"]);
     		$this->_result[$k][$id] = $c;
      	}
    }
    
    /**
     * Create an array of figuresfrom the input array
     *
     * @param array $data - array of incoming "paths" arrays
     *
     * @return void
     */
    private function addFigureFromArray(array $data)
    {	
    	$this->_result = $data;
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
     * Get a statistical figure value from the query result
     *
     * @param string $name - name of figure to return
     *
     * @return int
     */
    private function getStatValue($name) 
    {
        if (isset($this->_extra[self::ENTRY_STATS][$name])) {
            return $this->_extra[self::ENTRY_STATS][$name];
        }
        return 0;
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
    
    /**
     * Return the extra data of the query (statistics etc.). Contents of the result array
     * depend on the type of query executed
     *
     * @return array
     */
    public function getExtra()
    {
        return $this->_extra;
    }
    
    /**
     * Return the warnings issued during query execution
     *
     * @return array
     */
    public function getWarnings()
    {
        if (isset($this->_extra['warnings'])) {
            return $this->_extra['warnings'];
        }
        return array();
    }

    /**
     * Return the number of writes executed by the query
     *
     * @return int
     */
    public function getWritesExecuted()
    {
        return $this->getStatValue('writesExecuted');
    }
    
    /**
     * Return the number of ignored write operations from the query
     *
     * @return int
     */
    public function getWritesIgnored()
    {
        return $this->getStatValue('writesIgnored');
    }

    /**
     * Return the number of documents iterated over in full scans
     *
     * @return int
     */
    public function getScannedFull()
    {
        return $this->getStatValue('scannedFull');
    }
    
    /**
     * Return the number of documents iterated over in index scans
     *
     * @return int
     */
    public function getScannedIndex()
    {
        return $this->getStatValue('scannedIndex');
    }
    
    /**
     * Return the number of documents filtered by the query
     *
     * @return int
     */
    public function getFiltered()
    {
        return $this->getStatValue('filtered');
    }

}
