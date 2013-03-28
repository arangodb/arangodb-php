<?php

/**
 * ArangoDB PHP client: single document
 *
 * @package   ArangoDbPhpClient
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 *
 * @since     1.2
 */

namespace triagens\ArangoDb;

/**
 * Value object representing a graph
 *
 * @package   ArangoDbPhpClient
 *
 * @since     1.2
 */
class Graph extends
    Document
{
	
	/**
	 * Constructs an empty graph
	 *
	 * @param array $options - optional, initial $options for document
	 *
	 * @return void
	 */
	public function __construct($name = null, array $options = array())
	{

		// prevent backwards compatibility break where the first parameter is the $options array
		if(!is_array($name) && $name != null){
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
}
