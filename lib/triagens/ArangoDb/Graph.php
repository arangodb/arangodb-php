<?php

/**
 * ArangoDB PHP client: single document
 *
 * @package   ArangoDbPhpClient
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Value object representing a graph
 *
 * @package ArangoDbPhpClient
 */
class Graph extends
    Document
{

    /**
     * Set the vertices-collection of the graph
     *
     * @param mixed $verticesCollection - the name of the vertices-collection
     *
     * @return Graph - graph object
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
     */
    public function getEdgesCollection()
    {
        return $this->_edgesCollection;
    }
}
