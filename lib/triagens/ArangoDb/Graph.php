<?php

/**
 * ArangoDB PHP client: single document 
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Value object representing a graph
 *
 * @package ArangoDbPhpClient
 */
class Graph extends Document {

    /**
     * Set the 'from' vertex document-handler
     *
     * @param mixed $from - from vertex
     * @return Edge - edge object
     */
    public function setVerticesCollection($verticesCollection) {
        $this->_verticesCollection=$verticesCollection;
        return $this;
    }

    /**
     * Get the Vertices Collection of the graph
     *
     * @return string - name of the vertices collection
     */
    public function getVerticesCollection() {
        return $this->_verticesCollection;
    }

    /**
     * Set the 'from' vertex document-handler
     *
     * @param mixed $from - from vertex
     * @return Edge - edge object
     */
    public function setEdgesCollection($edgesCollection) {
        $this->_edgesCollection=$edgesCollection;
        return $this;
    }

    /**
     * Get the Edges Collection of the graph
     *
     * @return string - name of the edges collection
     */
    public function getEdgesCollection() {
        return $this->_edgesCollection;
    }

}
