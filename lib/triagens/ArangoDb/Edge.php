<?php

/**
 * ArangoDB PHP client: single document
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Value object representing a single collection-based edge document
 *
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     1.0
 */
class Edge extends
    Document
{
    /**
     * The edge's from (might be NULL for new documents)
     *
     * @var mixed
     */
    protected $_from;

    /**
     * The edge's to (might be NULL for new documents)
     *
     * @var mixed
     */
    protected $_to;

    /**
     * Document _from index
     */

    const ENTRY_FROM = '_from';

    /**
     * Revision _to index
     */
    const ENTRY_TO = '_to';


    /**
     * Clone a document
     *
     * Returns the clone
     *
     * @magic
     *
     * @return void
     */
    public function __clone()
    {
        $this->_id  = null;
        $this->_rev = null;
        // do not change the _changed flag here
    }


    /**
     * Set a document attribute
     *
     * The key (attribute name) must be a string.
     *
     * This will validate the value of the attribute and might throw an
     * exception if the value is invalid.
     *
     * @throws ClientException
     *
     * @param string $key - attribute name
     * @param mixed $value - value for attribute
     *
     * @return void
     */
    public function set($key, $value)
    {
        if ($this->_doValidate) {
            // validate the value passed
            ValueValidator::validate($value);
        }

        if ($key[0] === '_') {
            if ($key === self::ENTRY_ID) {
                $this->setInternalId($value);
                return;
            }

            if ($key === self::ENTRY_KEY) {
                $this->setInternalKey($value);
                return;
            }

            if ($key === self::ENTRY_REV) {
                $this->setRevision($value);
                return;
            }

            if ($key === self::ENTRY_FROM) {
                $this->setFrom($value);
                return;
            }

            if ($key === self::ENTRY_TO) {
                $this->setTo($value);
                return;
            }
        }

        if (!$this->_changed) {
            if (!isset($this->_values[$key]) || $this->_values[$key] !== $value) {
                // set changed flag
                $this->_changed = true;
            }
        }

        // and store the value
        $this->_values[$key] = $value;
    }


    /**
     * Set the 'from' vertex document-handler
     *
     * @param mixed $from - from vertex
     *
     * @return Edge - edge object
     */
    public function setFrom($from)
    {
        $this->_from = $from;

        return $this;
    }

    /**
     * Get the 'from' vertex document-handler (if already known)
     *
     * @return mixed - document-handler
     */
    public function getFrom()
    {
        return $this->_from;
    }

    /**
     * Set the 'to' vertex document-handler
     *
     * @param mixed $to - to vertex
     *
     * @return Edge - edge object
     */
    public function setTo($to)
    {
        $this->_to = $to;

        return $this;
    }

    /**
     * Get the 'to' vertex document-handler (if already known)
     *
     * @return mixed - document-handler
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * Get all document attributes for insertion/update
     *
     * @return mixed - associative array of all document attributes/values
     */
    public function getAllForInsertUpdate()
    {
        $data          = parent::getAllForInsertUpdate();
        $data['_from'] = $this->_from;
        $data['_to']   = $this->_to;
        return $data;
    }

}
