<?php

/**
 * ArangoDB PHP client: single document
 *
 * @package   ArangoDBClient
 * @author    Florian Bartels
 * @copyright Copyright 2014, triagens GmbH, Cologne, Germany
 *
 * @since     2.2
 */

namespace ArangoDBClient;

/**
 * Value object representing an edge Definition.
 * An edge definition contains a collection called 'relation' to store the edges and
 * multiple vertices collection defined in 'fromCollections' and 'toCollections'.
 *
 * <br>
 *
 * @package   ArangoDBClient
 * @since     2.2
 */
class EdgeDefinition
{
    /**
     * The name of the edge collection for this relation.
     *
     * @var string name of the edge collection
     */
    protected $_relation;

    /**
     * An array containing the names of the vertices collections holding the start vertices.
     *
     * @var array names of the start vertices collection
     */
    protected $_fromCollections = [];

    /**
     * An array containing the names of the vertices collections holding the end vertices.
     *
     * @var array names of the end vertices collection
     */
    protected $_toCollections = [];
    
    /**
     * An array containing satellite collections in Hybrid SmartGraphs
     *
     * @var array satellite collections
     */
    protected $_satellites = [];

    /**
     * Constructs an new edge definition
     *
     * @param string       $relation        - name of the relation (the underlying edge collection).
     * @param array|string $fromCollections - a list of collections providing the edges start vertices or a string holding a single collection name.
     * @param array|string $toCollections   - a list of collections providing the edges end vertices or a string holding a single collection name.
     * @param array|string $satellites      - a list of satellite collections (SmartGraph only).
     *
     * @since     2.2
     *
     */
    public function __construct($relation = null, $fromCollections = [], $toCollections = [], $satellites = [])
    {
        $this->_relation = $relation;

        $this->_fromCollections = (array) $fromCollections;
        $this->_toCollections   = (array) $toCollections;
        $this->_satellites      = (array) $satellites;
    }

    /**
     * Set the relation of the edge definition
     *
     * @param string $relation - the name of the relation.
     *
     * @since     2.2
     */
    public function setRelation($relation)
    {
        $this->_relation = $relation;
    }

    /**
     * Get the relation of the edge definition.
     *
     * @return string
     * @since     2.2
     */
    public function getRelation()
    {
        return $this->_relation;
    }


    /**
     * Get the 'to' collections of the graph.
     *
     * @return array
     * @since     2.2
     */
    public function getToCollections()
    {
        return $this->_toCollections;
    }

    /**
     * Get the 'from' collections of the graph.
     *
     * @return array
     * @since     2.2
     */
    public function getFromCollections()
    {
        return $this->_fromCollections;
    }
    
    /**
     * Get the 'satellites' collections of the graph.
     *
     * @return array
     * @since     3.9
     */
    public function getSatellites()
    {
        return $this->_satellites;
    }

    /**
     * Add a 'to' collections of the graph.
     *
     * @param string $toCollection - the name of the added collection.
     *
     * @since     2.2
     */
    public function addToCollection($toCollection)
    {
        $this->_toCollections[] = $toCollection;
    }

    /**
     * Add a 'from' collections of the graph.
     *
     * @param string $fromCollection - the name of the added collection.
     *
     * @since     2.2
     */
    public function addFromCollection($fromCollection)
    {
        $this->_fromCollections[] = $fromCollection;
    }
    
    /**
     * Add a 'satellite' collection of the graph.
     *
     * @param string $toCollection - the name of the added collection.
     *
     * @since 3.9
     */
    public function addSatelliteCollection($collection)
    {
        $this->_satellites[] = $collection;
    }

    /**
     * Resets the 'to' collections of the graph.
     *
     * @since     2.2
     */
    public function clearToCollection()
    {
        $this->_toCollections = [];
    }

    /**
     * Resets the 'from' collections of the graph.
     *
     * @since     2.2
     */
    public function clearFromCollection()
    {
        return $this->_fromCollections = [];
    }
    
    /**
     * Resets the 'satellites' collections of the graph.
     *
     * @since    3.9
     */
    public function clearSatellites()
    {
        return $this->_satellites = [];
    }

    /**
     * Transforms an edge definition to an array.
     *
     * @return array
     * @since     2.2
     */
    public function transformToArray()
    {
        $transformedEd               = [];
        $transformedEd['collection'] = $this->getRelation();
        $transformedEd['from']       = $this->getFromCollections();
        $transformedEd['to']         = $this->getToCollections();
        $transformedEd['satellites'] = $this->getSatellites();

        return $transformedEd;
    }


    /**
     * Constructs an undirected relation. This relation is an edge definition where the edges can start and end
     * in any vertex from the collection list.
     *
     * @param string $relation          - name of the relation (the underlying edge collection).
     * @param array  $vertexCollections - a list of collections providing the edges start and end vertices.
     * @param array  $satellites        - a list of satellite collections (for Hybrid SmartGraphs).
     *
     * @return EdgeDefinition
     * @since     2.2
     */
    public static function createUndirectedRelation($relation, $vertexCollections, array $satellites = [])
    {
        return new EdgeDefinition($relation, $vertexCollections, $vertexCollections, $satellites);
    }


    /**
     * Constructs a directed relation. This relation is an edge definition where the edges can start only in the
     * vertices defined in 'fromCollections' and end in vertices defined in 'toCollections'.
     *
     * @param string       $relation        - name of the relation (the underlying edge collection).
     * @param array|string $fromCollections - a list of collections providing the edges start vertices or a string holding a single collection name.
     * @param array|string $toCollections   - a list of collections providing the edges end vertices or a string holding a single collection name.
     * @param array|string $satellites      - a list of satellite collections (for Hybrid SmartGraphs).
     *
     * @return EdgeDefinition
     * @since     2.2
     */
    public static function createDirectedRelation($relation, $fromCollections, $toCollections, array $satellites = [])
    {
        return new EdgeDefinition($relation, $fromCollections, $toCollections, $satellites);
    }

}

class_alias(EdgeDefinition::class, '\triagens\ArangoDb\EdgeDefinition');
