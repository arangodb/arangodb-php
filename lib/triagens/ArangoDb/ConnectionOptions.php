<?php

/**
 * ArangoDB PHP client: connection options
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Simple container class for connection options.
 *
 * This class also provides the default values for the connection
 * options and will perform a simple validation of them.<br>
 * It provides array access to its members.<br>
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class ConnectionOptions implements
    \ArrayAccess
{
    /**
     * The current options
     *
     * @var array
     */
    private $_values = array();

    /**
     * The connection endpoint object
     *
     * @var Endpoint
     */
    private $_endpoint = null;

    /**
     * Endpoint string index constant
     */
    const OPTION_ENDPOINT = 'endpoint';

    /**
     * Host name string index constant (deprecated, use endpoint instead)
     */
    const OPTION_HOST = 'host';

    /**
     * Port number index constant (deprecated, use endpoint instead)
     */
    const OPTION_PORT = 'port';

    /**
     * Timeout value index constant
     */
    const OPTION_TIMEOUT = 'timeout';

    /**
     * Trace function index constant
     */
    const OPTION_TRACE = 'trace';

    /**
     * Enhanced trace
     */
    const OPTION_ENHANCED_TRACE = 'enhancedTrace';

    /**
     * "Create collections if they don't exist" index constant
     */
    const OPTION_CREATE = 'createCollection';

    /**
     * Update revision constant
     */
    const OPTION_REVISION = 'rev';

    /**
     * Update policy index constant
     */
    const OPTION_UPDATE_POLICY = 'policy';

    /**
     * Update keepNull constant
     */
    const OPTION_UPDATE_KEEPNULL = 'keepNull';

    /**
     * Replace policy index constant
     */
    const OPTION_REPLACE_POLICY = 'policy';

    /**
     * Delete policy index constant
     */
    const OPTION_DELETE_POLICY = 'policy';

    /**
     * Wait for sync index constant
     */
    const OPTION_WAIT_SYNC = 'waitForSync';

    /**
     * Limit index constant
     */
    const OPTION_LIMIT = 'limit';

    /**
     * Skip index constant
     */
    const OPTION_SKIP = 'skip';

    /**
     * Batch size index constant
     */
    const OPTION_BATCHSIZE = 'batchSize';

    /**
     * Wait for sync index constant
     */
    const OPTION_JOURNAL_SIZE = 'journalSize';

    /**
     * Wait for sync index constant
     */
    const OPTION_IS_SYSTEM = 'isSystem';

    /**
     * Wait for sync index constant
     */
    const OPTION_IS_VOLATILE = 'isVolatile';

    /**
     * Authentication user name
     */
    const OPTION_AUTH_USER = 'AuthUser';

    /**
     * Authentication password
     */
    const OPTION_AUTH_PASSWD = 'AuthPasswd';

    /**
     * Authentication type
     */
    const OPTION_AUTH_TYPE = 'AuthType';

    /**
     * Connection
     */
    const OPTION_CONNECTION = 'Connection';

    /**
     * Reconnect flag
     */
    const OPTION_RECONNECT = 'Reconnect';

    /**
     * Batch flag
     */
    const OPTION_BATCH = 'Batch';

    /**
     * Batchpart flag
     */
    const OPTION_BATCHPART = 'BatchPart';

    /**
     * Database flag
     */
    const OPTION_DATABASE = 'database';

    /**
     * UTF-8 CHeck Flag
     */
    const OPTION_CHECK_UTF8_CONFORM = 'CheckUtf8Conform';

    /**
     * Set defaults, use options provided by client and validate them
     *
     *
     * @param array $options - initial options
     *
     * @return \triagens\ArangoDb\ConnectionOptions
     */
    public function __construct(array $options)
    {
        $this->_values = array_merge(self::getDefaults(), $options);
        $this->validate();
    }

    /**
     * Get all options
     *
     * @return array - all options as an array
     */
    public function getAll()
    {
        return $this->_values;
    }

    /**
     * Set and validate a specific option, necessary for ArrayAccess
     *
     * @throws Exception
     *
     * @param string $offset - name of option
     * @param mixed  $value  - value for option
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_values[$offset] = $value;
        $this->validate();
    }

    /**
     * Check whether an option exists, necessary for ArrayAccess
     *
     * @param string $offset -name of option
     *
     * @return bool - true if option exists, false otherwise
     */
    public function offsetExists($offset)
    {
        return isset($this->_values[$offset]);
    }

    /**
     * Remove an option and validate, necessary for ArrayAccess
     *
     * @throws Exception
     *
     * @param string $offset - name of option
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_values[$offset]);
        $this->validate();
    }

    /**
     * Get a specific option, necessary for ArrayAccess
     *
     * @throws ClientException
     *
     * @param string $offset - name of option
     *
     * @return mixed - value of option, will throw if option is not set
     */
    public function offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->_values)) {
            throw new ClientException('Invalid option ' . $offset);
        }

        return $this->_values[$offset];
    }

    /**
     * Get the endpoint object for the connection
     *
     * @throws ClientException
     * @return Endpoint - endpoint object
     */
    public function getEndpoint()
    {
        if ($this->_endpoint === null) {
            // will also validate the endpoint
            $this->_endpoint = new Endpoint($this->_values[self::OPTION_ENDPOINT]);
        }

        return $this->_endpoint;
    }

    /**
     * Get the default values for the options
     *
     * @return array - array of default connection options
     */
    private static function getDefaults()
    {
        return array(
            self::OPTION_ENDPOINT           => null,
            self::OPTION_HOST               => null,
            self::OPTION_PORT               => DefaultValues::DEFAULT_PORT,
            self::OPTION_TIMEOUT            => DefaultValues::DEFAULT_TIMEOUT,
            self::OPTION_CREATE             => DefaultValues::DEFAULT_CREATE,
            self::OPTION_UPDATE_POLICY      => DefaultValues::DEFAULT_UPDATE_POLICY,
            self::OPTION_REPLACE_POLICY     => DefaultValues::DEFAULT_REPLACE_POLICY,
            self::OPTION_DELETE_POLICY      => DefaultValues::DEFAULT_DELETE_POLICY,
            self::OPTION_REVISION           => null,
            self::OPTION_WAIT_SYNC          => DefaultValues::DEFAULT_WAIT_SYNC,
            self::OPTION_BATCHSIZE          => null,
            self::OPTION_JOURNAL_SIZE       => DefaultValues::DEFAULT_JOURNAL_SIZE,
            self::OPTION_IS_SYSTEM          => false,
            self::OPTION_IS_VOLATILE        => DefaultValues::DEFAULT_IS_VOLATILE,
            self::OPTION_CONNECTION         => DefaultValues::DEFAULT_CONNECTION,
            self::OPTION_TRACE              => null,
            self::OPTION_ENHANCED_TRACE     => false,
            self::OPTION_AUTH_USER          => null,
            self::OPTION_AUTH_PASSWD        => null,
            self::OPTION_AUTH_TYPE          => null,
            self::OPTION_RECONNECT          => false,
            self::OPTION_BATCH              => false,
            self::OPTION_BATCHPART          => false,
            self::OPTION_DATABASE           => '_system',
            self::OPTION_CHECK_UTF8_CONFORM => DefaultValues::DEFAULT_CHECK_UTF8_CONFORM,
        );
    }

    /**
     * Return the supported authorization types
     *
     * @return array - array with supported authorization types
     */
    private static function getSupportedAuthTypes()
    {
        return array('Basic');
    }

    /**
     * Return the supported connection types
     *
     * @return array - array with supported connection types
     */
    private static function getSupportedConnectionTypes()
    {
        return array('Close', 'Keep-Alive');
    }

    /**
     * Validate the options
     *
     * @throws ClientException
     * @return void - will throw if an invalid option value is found
     */
    private function validate()
    {
        if (isset($this->_values[self::OPTION_HOST]) && !is_string($this->_values[self::OPTION_HOST])) {
            throw new ClientException('host should be a string');
        }

        if (isset($this->_values[self::OPTION_PORT]) && !is_int($this->_values[self::OPTION_PORT])) {
            throw new ClientException('port should be an integer');
        }

        // can use either endpoint or host/port
        if (isset($this->_values[self::OPTION_HOST]) && isset($this->_values[self::OPTION_ENDPOINT])) {
            throw new ClientException('must not specify both host and endpoint');
        } else {
            if (isset($this->_values[self::OPTION_HOST]) && !isset($this->_values[self::OPTION_ENDPOINT])) {
                // upgrade host/port to an endpoint
                $this->_values[self::OPTION_ENDPOINT] = 'tcp://' . $this->_values[self::OPTION_HOST] . ':' . $this->_values[self::OPTION_PORT];
            }
        }

        assert(isset($this->_values[self::OPTION_ENDPOINT]));
        // set up a new endpoint, this will also validate it
        $this->getEndpoint();
        if (Endpoint::getType($this->_values[self::OPTION_ENDPOINT]) === Endpoint::TYPE_UNIX) {
            // must set port to 0 for UNIX sockets
            $this->_values[self::OPTION_PORT] = 0;
        }

        if (isset($this->_values[self::OPTION_AUTH_TYPE]) && !in_array(
                $this->_values[self::OPTION_AUTH_TYPE],
                self::getSupportedAuthTypes()
            )
        ) {
            throw new ClientException('unsupported authorization method');
        }

        if (isset($this->_values[self::OPTION_CONNECTION]) && !in_array(
                $this->_values[self::OPTION_CONNECTION],
                self::getSupportedConnectionTypes()
            )
        ) {
            throw new ClientException(sprintf(
                                          "unsupported connection value '%s'",
                                          $this->_values[self::OPTION_CONNECTION]
                                      ));
        }

        UpdatePolicy::validate($this->_values[self::OPTION_UPDATE_POLICY]);
        UpdatePolicy::validate($this->_values[self::OPTION_REPLACE_POLICY]);
        UpdatePolicy::validate($this->_values[self::OPTION_DELETE_POLICY]);
    }
}
