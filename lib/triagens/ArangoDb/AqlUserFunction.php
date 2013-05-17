<?php

/**
 * ArangoDB PHP client: AqlUserFunction
 *
 * @author    Frank Mayer
 * @copyright Copyright 2013, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Class AqlUserFunction
 *
 * AqlUserFunction object
 * An AqlUserFunction is an object that is used to manage AQL User Functions.
 * It registers, unregisters and lists user functions on the server
 *
 * The object encapsulates:
 *
 * * the name of the function
 * * the actual javascript function
 *
 *
 * The object requires the connection object and can be initialized
 * with or without initial configuration.
 * Any configuration can be set and retrieved by the object's methods like this:
 *
 * $this->setName('myFunctions:myFunction');
 * $this->setCode('function (){your code};');
 *
 * or like this:
 *
 * $this->name('myFunctions:myFunction');
 * $this->code('function (){your code};');
 *
 *
 * @property string $name - The name of the user function
 * @property string $code - The code of the user function
 * @property mixed  _action
 *
 * @package triagens\ArangoDb
 */
class AqlUserFunction
{
    /**
     * The connection object
     *
     * @var Connection
     */
    private $_connection = null;

    /**
     * The transaction's attributes.
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * The transaction's action.
     *
     * @var string
     */
    protected $_action = '';

    /**
     * Collections index
     */
    const ENTRY_NAME = 'name';

    /**
     * Action index
     */
    const ENTRY_CODE = 'code';


    /**
     * Initialise the AqlUserFunction object
     *
     * The $attributesArray array can be used to specify the name and code for the user function in form of an array.
     *
     * Example:
     * array(
     *   'name' => 'myFunctions:myFunction',
     *   'code' => 'function (){}'
     * )
     *
     *
     * @param Connection $connection             - the connection to be used
     * @param array      $attributesArray        - user function initialization data
     *
     * @return \triagens\ArangoDb\AqlUserFunction
     */
    public function __construct(Connection $connection, array $attributesArray = null)
    {
        $this->_connection = $connection;
        if (is_array($attributesArray)) {
            $this->buildAttributesFromArray($attributesArray);
        }
    }


    /**
     * Registers the user function
     *
     * If no parameters ($name,$code) are passed, it will use the properties of the object.
     *
     * If $name and/or $code are passed, it will override the object's properties with the passed ones
     *
     * @param null $name
     * @param null $code
     *
     * @throws Exception throws exception if registration failed
     *
     * @return mixed true if registration was successful.
     */
    public function register($name = null, $code = null)
    {
        $attributes = $this->attributes;


        if ($name) {
            $attributes['name'] = $name;
        }

        if ($code) {
            $attributes['code'] = $code;
        }

        $response      = $this->_connection->post(
            Urls::URL_AQL_USER_FUNCTION,
            $this->getConnection()->json_encode_wrapper($attributes)
        );
        $responseArray = $response->getJson();

        return $responseArray;
    }


    /**
     * Unregister the user function
     *
     * If no parameter ($name) is passed, it will use the property of the object.
     *
     * If $name is passed, it will override the object's property with the passed one
     *
     * @param null $name
     *
     * @throws Exception throw exception if the request fails
     *
     * @return mixed true if successful without a return value or the return value if one was set in the action
     */
    public function unregister($name = null)
    {
        if (is_null($name)) {
            $name = $this->getName();
        }
        $url = UrlHelper::buildUrl(Urls::URL_AQL_USER_FUNCTION, $name);

        $response      = $this->_connection->delete($url);
        $responseArray = $response->getJson();

        return $responseArray;
    }


    /**
     * Get registered user functions
     *
     * The method can optionally be passed a $namespace parameter to narrow the results down to a specific namespace.
     *
     * @param null $namespace
     *
     * @throws Exception throw exception if the request failed
     *
     * @return mixed true if successful without a return value or the return value if one was set in the action
     */
    public function getRegisteredUserFunctions($namespace = null)
    {
        $url = UrlHelper::buildUrl(Urls::URL_AQL_USER_FUNCTION);
        if (is_null($namespace)) {
            $url = UrlHelper::appendParamsUrl($url, array('namespace' => $namespace));
        }
        $response = $this->_connection->get($url);

        $responseArray = $response->getJson();

        return $responseArray;
    }


    /**
     * Return the connection object
     *
     * @return Connection - the connection object
     */
    protected function getConnection()
    {
        return $this->_connection;
    }


    /**
     * Set name of the user function. It must have at least one namespace, but also can have sub-namespaces.
     * correct:
     * 'myNamespace:myFunction'
     * 'myRootNamespace:mySubNamespace:myFunction'
     *
     * wrong:
     * 'myFunction'
     *
     *
     * @param string $value
     */
    public function setName($value)
    {
        $this->set(self::ENTRY_NAME, (string) $value);
    }


    /**
     * Get name value
     *
     * @return string name
     */
    public function getName()
    {
        return $this->get(self::ENTRY_NAME);
    }

    /**
     * Set user function code
     *
     * @param string $value
     */
    public function setCode($value)
    {
        $this->set(self::ENTRY_CODE, (string) $value);
    }


    /**
     * Get user function code
     *
     * @return string name
     */
    public function getCode()
    {
        return $this->get(self::ENTRY_CODE);
    }


    /**
     * Set an attribute
     *
     * @param $key
     * @param $value
     *
     * @throws ClientException
     */
    public function set($key, $value)
    {
        if (!is_string($key)) {
            throw new ClientException('Invalid attribute key');
        }

        $this->attributes[$key] = $value;
    }


    /**
     * Set an attribute, magic method
     *
     * This is a magic method that allows the object to be used without
     * declaring all attributes first.
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
        switch ($key) {
            case self::ENTRY_NAME :
                $this->setName($value);
                break;
            case self::ENTRY_CODE :
                $this->setCode($value);
                break;
            default:
                $this->set($key, $value);
                break;
        }
    }

    /**
     * Get an attribute
     *
     * @param string $key - name of attribute
     *
     * @return mixed - value of attribute, NULL if attribute is not set
     */
    public function get($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return null;
    }

    /**
     * Get an attribute, magic method
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
     * Returns the action string
     *
     * @return string - the current action string
     */
    public function __toString()
    {
        return $this->_action;
    }

    /**
     * Build the object's attributes from a given array
     *
     * @param $options
     */
    public function buildAttributesFromArray($options)
    {
        if (isset($options[self::ENTRY_NAME])) {
            $this->setName($options[self::ENTRY_NAME]);
        }

        if (isset($options[self::ENTRY_CODE])) {
            $this->setCode($options[self::ENTRY_CODE]);
        }
    }
}
