<?php

/**
 * ArangoDB PHP client: single document
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 *
 * @since     1.2
 */

namespace triagens\ArangoDb;

/**
 * Value object representing a graph
 *
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     1.2
 */
class Graph extends
    Document
{
    /**
     * The collection used for vertices
     *
     * @var string - name of the vertices collection
     */
    protected $_verticesCollection = null;

    /**
     * The collection used for edges
     *
     * @var string - name of the edges collection
     */
    protected $_edgesCollection = null;

    /**
     * Graph vertices
     */
    const ENTRY_VERTICES = 'vertices';

    /**
     * Graph edges
     */
    const ENTRY_EDGES = 'edges';

    /**
     * Constructs an empty graph
     *
     * @param array $name    - optional, initial name for graph
     * @param array $options - optional, initial $options for graph
     *
     * @return Graph
     */
    public function __construct($name = null, array $options = array())
    {

        // prevent backwards compatibility break where the first parameter is the $options array
        if (!is_array($name) && $name != null) {
            $this->set('_key', $name);
        }

        // pass the $options to the parent constructor to do the actual work
        parent::__construct($options);
    }

    /**
     * Set the vertices-collection of the graph
     *
     * @param mixed $verticesCollection - the name of the vertices-collection
     *
     * @return Graph - graph object
     * @since     1.2
     */
    public function setVerticesCollection($verticesCollection)
    {
        $this->_verticesCollection = $verticesCollection;

        return $this;
    }

    /**
     * Get the Vertices Collection of the graph
     *
     * @return string - name of the vertices collection
     * @since     1.2
     */
    public function getVerticesCollection()
    {
        return $this->_verticesCollection;
    }

    /**
     * Set the edges-collection of the graph
     *
     * @param mixed $edgesCollection - the name of the edges-collection
     *
     * @return Graph - graph object
     * @since     1.2
     */
    public function setEdgesCollection($edgesCollection)
    {
        $this->_edgesCollection = $edgesCollection;

        return $this;
    }

    /**
     * Get the Edges Collection of the graph
     *
     * @return string - name of the edges collection
     * @since     1.2
     */
    public function getEdgesCollection()
    {
        return $this->_edgesCollection;
    }

    /**
     * Set a graph attribute
     *
     * The key (attribute name) must be a string.
     * This will validate the value of the attribute and might throw an
     * exception if the value is invalid.
     *
     * @throws ClientException
     *
     * @param string $key   - attribute name
     * @param mixed  $value - value for attribute
     *
     * @return void
     */
    public function set($key, $value)
    {

        if (in_array($key, array(self::ENTRY_VERTICES, self::ENTRY_EDGES))) {

            if (!is_string($key)) {
                throw new ClientException('Invalid document attribute key');
            }

            // validate the value passed
            ValueValidator::validate($value);

            if ($key === self::ENTRY_VERTICES) {
                $this->setVerticesCollection($value);

                return;
            }

            if ($key === self::ENTRY_EDGES) {
                $this->setEdgesCollection($value);

                return;
            }
        } else {
            parent::set($key, $value);
        }
    }
}
