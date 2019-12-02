<?php

/**
 * ArangoDB PHP client: Analyzer class
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @copyright Copyright 2019, ArangoDB GmbH, Cologne, Germany
 *
 * @since     3.6
 */

namespace ArangoDBClient;

/**
 * Value object representing an analyzer
 *
 * <br>
 *
 * @package   ArangoDBClient
 * @since     3.6
 */
class Analyzer
{
    /**
     * The analyzer name
     *
     * @var string - analyzer name
     */
    protected $_name;
    
    /**
     * Analyzer name index
     */
    const ENTRY_NAME = 'name';
    
    /**
     * Analyzer type index
     */
    const ENTRY_TYPE = 'type';
    
    /**
     * Analyzer properties index
     */
    const ENTRY_PROPERTIES = 'properties';
    
    /**
     * Analyzer features index
     */
    const ENTRY_FEATURES = 'features';
    
    /**
     * Constructs an analyzer
     *
     * @param string $name      - analyzer name
     * @param string $type      - analyzer type
     * @param array $properties - analyzer properties
     * @param array $features   - analyzer features
     *
     * @since     3.6
     *
     * @throws \ArangoDBClient\ClientException
     */
    public function __construct($name, $type, array $properties = [], array $features = [])
    {
        $this->_name = $name;
        $this->_type = $type;
        $this->_properties = $properties;
        $this->_features = $features;
    }

    /**
     * Return the analyzer name
     *
     * @return string - analyzer name
     */
    public function getName() 
    {
        return $this->_name;
    }
    
    /**
     * Return the analyzer type
     *
     * @return string - analyzer type
     */
    public function getType() 
    {
        return $this->_type;
    }
    
    /**
     * Return the analyzer properties
     *
     * @return array - analyzer properties
     */
    public function getProperties() 
    {
        return $this->_properties;
    }
    
    /**
     * Return the analyzer features
     *
     * @return array - analyzer features
     */
    public function getFeatures()
    {
        return $this->_features;
    }
    
    /**
     * Return the analyzer as an array
     *
     * @return array - analyzer data as an array
     */
    public function getAll() 
    {
        return [
            self::ENTRY_NAME       => $this->getName(),
            self::ENTRY_TYPE       => $this->getType(),
            self::ENTRY_PROPERTIES => $this->getProperties(),
            self::ENTRY_FEATURES   => $this->getFeatures(),
        ];
    }
}

class_alias(Analyzer::class, '\triagens\ArangoDb\Analyzer');
