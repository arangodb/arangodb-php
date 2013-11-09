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
 * Value object representing a single collection-based document
 *
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class Document
{
    /**
     * The document id (might be NULL for new documents)
     *
     * @var string - document id
     */
    protected $_id = null;

    /**
     * The document key (might be NULL for new documents)
     *
     * @var string - document id
     */
    protected $_key = null;

    /**
     * The document revision (might be NULL for new documents)
     *
     * @var mixed
     */
    protected $_rev = null;

    /**
     * The document attributes (names/values)
     *
     * @var array
     */
    protected $_values = array();

    /**
     * Flag to indicate whether document was changed locally
     *
     * @var bool
     */
    protected $_changed;

    /**
     * Flag to indicate whether document is a new document (never been saved to the server)
     *
     * @var bool
     */
    protected $_isNew = true;

    /**
     * Flag to indicate whether document was changed locally
     *
     * @var bool
     */
    protected $_hidden = array();

    /**
     * Document id index
     */
    const ENTRY_ID = '_id';

    /**
     * Document key index
     */
    const ENTRY_KEY = '_key';

    /**
     * Revision id index
     */
    const ENTRY_REV = '_rev';

    /**
     * isNew id index
     */
    const ENTRY_ISNEW = '_isNew';

    /**
     * hidden attribute index
     */
    const ENTRY_HIDDEN = '_hidden';

    /**
     * waitForSync option index
     */
    const OPTION_WAIT_FOR_SYNC = 'waitForSync';

    /**
     * policy option index
     */
    const OPTION_POLICY = 'policy';

    /**
     * keepNull option index
     */
    const OPTION_KEEPNULL = 'keepNull';

    /**
     * Constructs an empty document
     *
     * @param array $options - optional, initial $options for document
     *
     * @return Document
     */
    public function __construct(array $options = array())
    {
        // keeping the non-underscored version for backwards-compatibility
        $this->setChanged(false);
        if (array_key_exists('hiddenAttributes', $options)) {
            $this->setHiddenAttributes($options['hiddenAttributes']);
        }
        if (array_key_exists('_hiddenAttributes', $options)) {
            $this->setHiddenAttributes($options['_hiddenAttributes']);
        }

        if (array_key_exists('_isNew', $options)) {
            $this->setIsNew($options['_isNew']);
        }
    }

    /**
     * Factory method to construct a new document using the values passed to populate it
     *
     * @throws ClientException
     *
     * @param array $values  - initial values for document
     * @param array $options - optional, initial options for document
     *
     * @return Document|Edge
     */
    public static function createFromArray(array $values, array $options = array())
    {
        $document = new static($options);
        foreach ($values as $key => $value) {
            $document->set($key, $value);
        }

        $document->setChanged(true);

        return $document;
    }

    /**
     * Clone a document
     *
     * Returns the clone
     *
     * @return void
     */
    public function __clone()
    {
        $this->_id  = null;
        $this->_key = null;
        $this->_rev = null;
        // do not change the _changed flag here
    }

    /**
     * Get a string representation of the document.
     *
     * It will not output hidden attributes.
     *
     * Returns the document as JSON-encoded string
     *
     * @return string - JSON-encoded document
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Returns the document as JSON-encoded string
     *
     * @param array $options - optional, array of options that will be passed to the getAll function
     *                       <p>Options are :
     *                       <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                       <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                       </p>
     *
     * @return string - JSON-encoded document
     */
    public function toJson($options = array())
    {
        return json_encode($this->getAll($options));
    }

    /**
     * Returns the document as a serialized string
     *
     * @param array $options - optional, array of options that will be passed to the getAll function
     *                       <p>Options are :
     *                       <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                       <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                       </p>
     *
     * @return string - PHP serialized document
     */
    public function toSerialized($options = array())
    {
        return serialize($this->getAll($options));
    }

    /**
     * Returns the attributes with the hidden ones removed
     *
     * @param array $attributes - attributes array
     *
     * @return array - attributes array
     */
    public function filterHiddenAttributes($attributes)
    {
        $hiddenAttributes = $this->getHiddenAttributes();

        if (is_array($hiddenAttributes)) {
            foreach ($hiddenAttributes as $hiddenAttributeName) {
                if (!in_array($hiddenAttributeName, $attributes)) {
                    unset ($attributes[$hiddenAttributeName]);
                }
            }
        }

        unset ($attributes['_hidden']);

        return $attributes;
    }

    /**
     * Set a document attribute
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
        if (!is_string($key)) {
            throw new ClientException('Invalid document attribute key');
        }

        // validate the value passed
        ValueValidator::validate($value);

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

        if ($key === self::ENTRY_ISNEW) {
            $this->setIsNew($value);

            return;
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
     * Set a document attribute, magic method
     *
     * This is a magic method that allows the object to be used without
     * declaring all document attributes first.
     * This function is mapped to set() internally.
     *
     * @throws ClientException
     *
     * @param string $key   - attribute name
     * @param mixed  $value - value for attribute
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Get a document attribute
     *
     * @param string $key - name of attribute
     *
     * @return mixed - value of attribute, NULL if attribute is not set
     */
    public function get($key)
    {
        if (isset($this->_values[$key])) {
            return $this->_values[$key];
        }

        return null;
    }

    /**
     * Get a document attribute, magic method
     *
     * This function is mapped to get() internally.
     *
     * @param string $key - name of attribute
     *
     * @return mixed - value of attribute, NULL if attribute is not set
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Get all document attributes
     *
     * @param mixed $options - optional, array of options for the getAll function, or the boolean value for $includeInternals
     *                       <p>Options are :
     *                       <li>'_includeInternals' - true to include the internal attributes. Defaults to false</li>
     *                       <li>'_ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false</li>
     *                       </p>
     *
     * @return array - array of all document attributes/values
     */
    public function getAll($options = array())
    {
        // This preserves compatibility for the old includeInternals parameter.
        $includeInternals       = false;
        $ignoreHiddenAttributes = false;

        if (!is_array($options)) {
            $includeInternals = $options;
        } else {
            // keeping the non-underscored version for backwards-compatibility
            $includeInternals = array_key_exists(
                'includeInternals',
                $options
            ) ? $options['includeInternals'] : $includeInternals;

            $includeInternals = array_key_exists(
                '_includeInternals',
                $options
            ) ? $options['_includeInternals'] : $includeInternals;

            // keeping the non-underscored version for backwards-compatibility
            $ignoreHiddenAttributes = array_key_exists(
                'ignoreHiddenAttributes',
                $options
            ) ? $options['ignoreHiddenAttributes'] : $ignoreHiddenAttributes;

            $ignoreHiddenAttributes = array_key_exists(
                '_ignoreHiddenAttributes',
                $options
            ) ? $options['_ignoreHiddenAttributes'] : $ignoreHiddenAttributes;
        }

        $data         = $this->_values;
        $nonInternals = array('_changed', '_values', '_hidden');

        if ($includeInternals == true) {
            foreach ($this as $key => $value) {
                if (substr($key, 0, 1) == '_' && substr($key, 0, 2) !== '__' && !in_array($key, $nonInternals)) {
                    $data[$key] = $value;
                }
            }
        }

        if ($ignoreHiddenAttributes == false) {
            $data = $this->filterHiddenAttributes($data);
        }

        if (!is_null($this->_key)) {
            $data['_key'] = $this->_key;
        }

        return $data;
    }

    /**
     * Set the hidden attributes
     *
     * @param array $attributes - array of attributes
     *
     * @return void
     */
    public function setHiddenAttributes(array $attributes)
    {
        $this->_hidden = $attributes;
    }

    /**
     * Get the hidden attributes
     *
     * @return array $attributes - array of attributes
     */
    public function getHiddenAttributes()
    {
        return $this->_hidden;
    }

    /**
     * Set the changed flag
     *
     * @param bool $value - change flag
     *
     * @return bool
     */
    public function setChanged($value)
    {
        return $this->_changed = (bool) $value;
    }

    /**
     * Get the changed flag
     *
     * @return bool - true if document was changed, false otherwise
     */
    public function getChanged()
    {
        return $this->_changed;
    }

    /**
     * Set the isNew flag
     *
     * @param bool $isNew - flags if new or existing doc
     *
     * @return void
     */
    public function setIsNew($isNew)
    {
        $this->_isNew = (bool) $isNew;
    }

    /**
     * Get the isNew flag
     *
     * @return bool $isNew - flags if new or existing doc
     */
    public function getIsNew()
    {
        return $this->_isNew;
    }

    /**
     * Set the internal document id
     *
     * This will throw if the id of an existing document gets updated to some other id
     *
     * @throws ClientException
     *
     * @param string $id - internal document id
     *
     * @return void
     */
    public function setInternalId($id)
    {
        if ($this->_id !== null && $this->_id != $id) {
            throw new ClientException('Should not update the id of an existing document');
        }


        if (!preg_match('/^\w+\/\w+$/', $id)) {
            throw new ClientException('Invalid format for document id');
        }

        $this->_id = (string) $id;
    }

    /**
     * Set the internal document key
     *
     * This will throw if the key of an existing document gets updated to some other key
     *
     * @throws ClientException
     *
     * @param string $key - internal document key
     *
     * @return void
     */
    public function setInternalKey($key)
    {
        if ($this->_key !== null && $this->_key != $key) {
            throw new ClientException('Should not update the key of an existing document');
        }

        if (!preg_match('/^\w+$/', $key)) {
            throw new ClientException('Invalid format for document key');
        }

        $this->_key = (string) $key;
    }

    /**
     * Get the internal document id (if already known)
     *
     * Document ids are generated on the server only. Document ids consist of collection id and
     * document id, in the format collectionId/documentId
     *
     * @return string - internal document id, might be NULL if document does not yet have an id
     */
    public function getInternalId()
    {
        return $this->_id;
    }

    /**
     * Get the internal document key (if already known)
     *
     * @return string - internal document key, might be NULL if document does not yet have a key
     */
    public function getInternalKey()
    {
        return $this->_key;
    }

    /**
     * Convenience function to get the document handle (if already known) - is an alias to getInternalId()
     *
     * Document handles are generated on the server only. Document handles consist of collection id and
     * document id, in the format collectionId/documentId
     *
     * @return string - internal document id, might be NULL if document does not yet have an id
     */
    public function getHandle()
    {
        return $this->getInternalId();
    }

    /**
     * Get the document id (if already known)
     *
     * Document ids are generated on the server only. Document ids are numeric but might be
     * bigger than PHP_INT_MAX. To reliably store a document id elsewhere, a PHP string should be used
     *
     * @return mixed - document id, might be NULL if document does not yet have an id
     */
    public function getId()
    {
        @list(, $documentId) = explode('/', $this->_id, 2);

        return $documentId;
    }

    /**
     * Get the document key (if already known).
     * Alias function for getInternalKey()
     *
     * @return mixed - document key, might be NULL if document does not yet have a key
     */
    public function getKey()
    {

        return $this->getInternalKey();
    }

    /**
     * Get the collection id (if already known)
     *
     * Collection ids are generated on the server only. Collection ids are numeric but might be
     * bigger than PHP_INT_MAX. To reliably store a collection id elsewhere, a PHP string should be used
     *
     * @return mixed - collection id, might be NULL if document does not yet have an id
     */
    public function getCollectionId()
    {
        @list($collectionId) = explode('/', $this->_id, 2);

        return $collectionId;
    }

    /**
     * Set the document revision
     *
     * Revision ids are generated on the server only.
     *
     * Document ids are numeric but might be bigger than PHP_INT_MAX.
     * To reliably store a document id elsewhere, a PHP string should be used
     *
     * @param mixed $rev - revision id
     *
     * @return void
     */
    public function setRevision($rev)
    {
        $this->_rev = (string) $rev;
    }

    /**
     * Get the document revision (if already known)
     *
     * @return mixed - revision id, might be NULL if document does not yet have an id
     */
    public function getRevision()
    {
        return $this->_rev;
    }
}
