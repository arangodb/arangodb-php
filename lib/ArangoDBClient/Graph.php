<?php

/**
 * ArangoDB PHP client: graphs
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @author    Florian Bartels
 * @copyright Copyright 2014, triagens GmbH, Cologne, Germany
 *
 * @since     1.2
 */

namespace ArangoDBClient;

/**
 * Value object representing a graph
 *
 * <br>
 *
 * @package   ArangoDBClient
 * @since     1.2
 */
class Graph extends Document
{
    /**
     * Graph edge definitions
     */
    const ENTRY_EDGE_DEFINITIONS = 'edgeDefinitions';

    /**
     * Graph edge definitions from collections
     */
    const ENTRY_FROM = 'from';

    /**
     * Graph edge definitions to collections
     */
    const ENTRY_TO = 'to';

    /**
     * Graph edge definitions collections
     */
    const ENTRY_COLLECTION = 'collection';

    /**
     * Graph orphan collections
     */
    const ENTRY_ORPHAN_COLLECTIONS = 'orphanCollections';
    
    /**
     * Smart graph flag
     */
    const ENTRY_IS_SMART = 'isSmart';
    
    /**
     * Disjoint Smart graph flag
     */
    const ENTRY_IS_DISJOINT = 'isDisjoint';
    
    /**
     * SmartGraph attribute name
     */
    const ENTRY_SMART_GRAPH_ATTRIBUTE = 'smartGraphAttribute';
    
    /**
     * SmartGraph satellites entry
     */
    const ENTRY_SATELLITES = 'satellites';
    
    /**
     * Entry for number of shards (cluster-only)
     */
    const ENTRY_NUMBER_OF_SHARDS = 'numberOfShards';
    
    /**
     * Entry for replication factor (cluster-only)
     */
    const ENTRY_REPLICATION_FACTOR = 'replicationFactor';
    

    /**
     * The list of edge definitions defining the graph.
     *
     * @var EdgeDefinition[] list of edge definitions.
     */
    protected $_edgeDefinitions = [];

    /**
     * The list of orphan collections defining the graph.
     * These collections are not used in any edge definition of the graph.
     *
     * @var array list of orphan collections.
     */
    protected $_orphanCollections = [];
    
    /**
      * The list of satellite collections (SmartGraph only)
     *
     * @var array list of satellite collections.
     */
    protected $_satellites = [];

    /**
     * SmartGraph flag
     *
     * @var bool whether or not the graph is a SmartGraph
     */
    protected $_isSmart = false;
    
    /**
     * Disjoint SmartGraph flag
     *
     * @var bool whether or not the graph is a disjoint SmartGraph
     */
    protected $_isDisjoint = false;

    /**
     * SmartGraph attribute name
     *
     * @var string smart graph attribute name
     */
    protected $_smartGraphAttribute = null;
    
    /**
     * Cluster-graph number of shards
     *
     * @var mixed number of shards (either null or a number)
     */
    protected $_numberOfShards = 1;
    
    /**
     * Cluster-graph replication factor
     *
     * @var mixed replication factor (either null, a number or "satellite")
     */
    protected $_replicationFactor = null;

    /**
     * Constructs an empty graph
     *
     * @param array $name    - optional, initial name for graph
     * @param array $options - optional, initial options for graph
     *
     * @since     1.2
     *
     * @throws \ArangoDBClient\ClientException
     */
    public function __construct($name = null, array $options = [])
    {
        // prevent backwards compatibility break where the first parameter is the $options array
        if (!is_array($name) && $name !== null) {
            $this->set('_key', $name);
        }

        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }
    }


    /**
     * Adds an edge definition to the graph.
     *
     * @param EdgeDefinition $edgeDefinition - the edge Definition.
     *
     * @return Graph
     * @since     2.2
     */
    public function addEdgeDefinition(EdgeDefinition $edgeDefinition)
    {
        $this->_edgeDefinitions[] = $edgeDefinition;

        return $this;
    }

    /**
     * Get the edge definitions of the graph.
     *
     * @return EdgeDefinition[]
     * @since     2.2
     */
    public function getEdgeDefinitions()
    {
        return $this->_edgeDefinitions;
    }


    /**
     * Adds an orphan collection to the graph.
     *
     * @param string $orphanCollection - the orphan collection.
     *
     * @return Graph
     * @since     2.2
     */
    public function addOrphanCollection($orphanCollection)
    {
        $this->_orphanCollections[] = $orphanCollection;

        return $this;
    }

    /**
     * Get the orphan collections of the graph.
     *
     * @return string[]
     * @since     2.2
     */
    public function getOrphanCollections()
    {
        return $this->_orphanCollections;
    }
    
    
    /**
     * Adds a satellite collection to the graph.
     *
     * @param string $name - name of satellite collection
     *
     * @return Graph
     */
    public function addSatellite($name)
    {
        $this->_satellites[] = $name;
        return $this;
    }

    /**
     * Get the satellite collections of the graph.
     *
     * @return string[]
     */
    public function getSatellites()
    {
      return $this->_satellites;
    }
    
    /**
     * Set the smart graph attribute
     *
     * @param string $name - smart graph attribute name
     *
     * @return Graph
     * @since 3.9
     */
    public function setSmartGraphAttribute($name)
    {
        $this->set(self::ENTRY_IS_SMART_GRAPH_ATTRIBUTE, $name);
        return $this;
    }

    /**
     * Get the smart graph attribute
     *
     * @return string
     * @since 3.9
     */
    public function getSmartGraphAttribute()
    {
        return $this->_smartGraphAttribute;
    }
    
    /**
     * Set the smartness of the graph.
     *
     * @param bool $value - smartness value
     *
     * @return Graph
     * @since 3.9
     */
    public function setIsSmart($value)
    {
        $this->set(self::ENTRY_IS_SMART, $value);
        return $this;
    }

    /**
     * Get the smartness of the graph.
     *
     * @return bool
     * @since 3.9
     */
    public function isSmart()
    {
        return $this->_isSmart;
    }
    
    /**
     * Set the disjointness of the graph.
     *
     * @param bool $value - disjointness value
     *
     * @return Graph
     * @since 3.9
     */
    public function setIsDisjoint($value)
    {
        $this->set(self::ENTRY_IS_DISJOINT, $value);
        return $this;
    }

    /**
     * Get the disjointness of the graph.
     *
     * @return bool
     * @since 3.9
     */
    public function isDisjoint()
    {
        return $this->_isDisjoint;
    }
    
    /**
     * Set the number of shards
     *
     * @param int $shards - number of shards
     *
     * @return Graph
     * @since 3.9
     */
    public function setNumberOfShards($shards)
    {
        $this->set(self::ENTRY_NUMBER_OF_SHARDS, $shards);
        return $this;
    }

    /**
     * Get the number of shards
     *
     * @return mixed
     * @since 3.9
     */
    public function getNumberOfShards()
    {
        return $this->_numberOfShards;
    }
    
    
    /**
     * Set the replication factor
     *
     * @param mixed $replicationFactor - replication factor (either a number or "satellite")
     *
     * @return Graph
     * @since 3.9
     */
    public function setReplicationFactor($value)
    {
        $this->set(self::ENTRY_REPLICATION_FACTOR, $value);
        return $this;
    }

    /**
     * Get the replication factor
     *
     * @return mixed
     * @since 3.9
     */
    public function getReplicationFactor()
    {
        return $this->_replicationFactor;
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
        if ($key === self::ENTRY_EDGE_DEFINITIONS) {
            if ($this->_doValidate) {
                ValueValidator::validate($value);
            }

            $edgeDefinitionBaseObject = new EdgeDefinition();

            foreach ($value as $ed) {
                $edgeDefinition = clone $edgeDefinitionBaseObject;

                foreach ($ed[self::ENTRY_FROM] as $from) {
                    $edgeDefinition->addFromCollection($from);
                }
                foreach ($ed[self::ENTRY_TO] as $to) {
                    $edgeDefinition->addToCollection($to);
                }
                $edgeDefinition->setRelation($ed[self::ENTRY_COLLECTION]);
                $this->addEdgeDefinition($edgeDefinition);
            }
        } else if ($key === self::ENTRY_ORPHAN_COLLECTIONS) {
            if ($this->_doValidate) {
                ValueValidator::validate($value);
            }

            foreach ($value as $o) {
                $this->addOrphanCollection($o);
            }
        } else if ($key === self::ENTRY_SATELLITES) {
            foreach ($value as $o) {
                $this->addSatellite($o);
            }
        } else if ($key === self::ENTRY_NUMBER_OF_SHARDS) {
            $this->_numberOfShards = (int) $value;
        } else if ($key === self::ENTRY_REPLICATION_FACTOR) {
            $this->_replicationFactor = $value;
        } else if ($key === self::ENTRY_IS_SMART) {
          if (!$value && ($this instanceof SmartGraph)) {
                throw new ClientException('Cannot unset isSmart attribute of a SmartGraph');
            }
            $this->_isSmart = $value;
        } else if ($key === self::ENTRY_IS_DISJOINT) {
            if (!($this instanceof SmartGraph)) {
                throw new ClientException('Cannot set isDisjoint attribute on non-SmartGraph');
            }
            $this->_isDisjoint = $value;
        } else if ($key === self::ENTRY_SMART_GRAPH_ATTRIBUTE) {
            $this->_smartGraphAttribute = $value;
        } else {
            parent::set($key, $value);
        }
    }

    /**
     * returns (or creates) the edge definition for single-vertexcollection-undirected graphs, throw an exception for any other type of graph.
     *
     * @throws ClientException
     * @return EdgeDefinition
     */
    private function getSingleUndirectedRelation()
    {
        $ed = $this->getEdgeDefinitions();
        if (count($ed) > 0) {
            $a = $ed[0];
            $b = $a->getFromCollections();
            $c = $a->getToCollections();
        }
        if (count($ed) > 1 ||
            (
                count($ed) === 1 && (
                    count($a->getFromCollections()) > 1 ||
                    count($a->getToCollections()) > 1 ||
                    $b[0] !== $c[0]

                )
            )
        ) {
            throw new ClientException('This operation only supports graphs with one undirected single collection relation');
        }
        if (count($ed) === 1) {
            $eD                     = $ed[0];
            $this->_edgeDefinitions = [];
        } else {
            $eD = new EdgeDefinition();
        }

        return $eD;
    }

}

class_alias(Graph::class, '\triagens\ArangoDb\Graph');
