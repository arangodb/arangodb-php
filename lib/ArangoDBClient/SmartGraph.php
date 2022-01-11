<?php

/**
 * ArangoDB PHP client: smart graphs
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @copyright Copyright 2014, triagens GmbH, Cologne, Germany
 *
 * @since     3.9
 */

namespace ArangoDBClient;

/**
 * Value object representing a SmartGraph
 *
 * @package   ArangoDBClient
 * @since     3.9
 */
class SmartGraph extends Graph
{
    /**
     * Constructs an empty SmartGraph
     *
     * @param array $name    - optional, initial name for graph
     * @param array $options - optional, initial options for graph
     *
     * @since     3.9
     *
     * @throws \ArangoDBClient\ClientException
     */
    public function __construct($name = null, array $options = [])
    {
        if (!isset($options[self::ENTRY_SMART_GRAPH_ATTRIBUTE])) {
          throw new ClientException('SmartGraph requires smartGraphAttribute to be set');
        }

        // pass the $options to the parent constructor to do the actual work
        parent::__construct($name, $options);

        // cannot be changed later on
        $this->_isSmart = true;
    }
}

class_alias(SmartGraph::class, '\triagens\ArangoDb\SmartGraph');
