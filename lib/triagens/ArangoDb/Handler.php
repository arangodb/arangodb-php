<?php

/**
 * ArangoDB PHP client: base handler
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * A base class for REST-based handlers
 *
 * <br>
 *
 * @package triagens\ArangoDb
 * @since   0.2
 */
abstract class Handler
{
    /**
     * Connection object
     *
     * @param Connection
     */
    private $_connection;


    /**
     * Construct a new handler
     *
     * @param Connection $connection - connection to be used
     *
     * @return Handler
     */
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
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
     * Return a connection option
     * This is a convenience function that calls json_encode_wrapper on the connection
     *
     * @param $optionName - The option to return a value for
     *
     * @return mixed - the option's value
     */
    protected function getConnectionOption($optionName)
    {
        return $this->getConnection()->getOption($optionName);
    }


    /**
     * Return an array of cursor options
     *
     * @param mixed $options - $options might be a boolean sanitize value, or an array of options, with or without a '_sanitize' key.
     *
     * @return array - array of options
     */
    protected function getCursorOptions($options)
    {
        $sanitize = false;

        if (is_bool($options)) {
            $sanitize = $options;
        }
        if (is_array($options)) {
            if (array_key_exists('_sanitize', $options)) {
                $sanitize = $options['_sanitize'];
            } else {
                // keeping the non-underscored version for backwards-compatibility
                if (array_key_exists('sanitize', $options)) {
                    $sanitize = $options['sanitize'];
                }
            }
        }


        return array(
            Cursor::ENTRY_SANITIZE => $sanitize,
        );
    }

    /**
     * Return a json encoded string for the array passed.
     * This is a convenience function that calls json_encode_wrapper on the connection
     *
     * @param array $body - The body to encode into json
     *
     * @return string - json string of the body that was passed
     */
    protected function json_encode_wrapper($body)
    {
        return $this->getConnection()->json_encode_wrapper($body);
    }


    //todo: (@frankmayer) check if refactoring a bit more if it makes sense...
    /**
     * Helper function that validates and includes an old single method parameter setting into the parameters array given.
     * This is only for keeping backwards-compatibility where methods had for example a parameter which was called 'policy' and
     * which was later changed to being an array of options, so more than one options can be passed easily.
     * This is only for options that are to be sent to the ArangoDB server.
     *
     * @param array $options   - The options array that may hold the policy to include in the parameters. If it's not an array, then the value is the policy value.
     * @param array $params    - The parameters into which the options will be included.
     * @param mixed $parameter - the old single parameter key to use.
     *
     * @return array $params - array of parameters for use in a url
     */
    protected function validateAndIncludeOldSingleParameterInParams($options, $params, $parameter)
    {
        $value = null;

        if (!is_array($options)) {
            $value = $options;
        } else {
            $value = array_key_exists($parameter, $options) ? $options[$parameter] : $value;
        }

        if ($value === null) {
            $value = $this->getConnection()->getOption($parameter);
        }

        if ($parameter === ConnectionOptions::OPTION_UPDATE_POLICY) {
            UpdatePolicy::validate($value);
        }

        if (is_bool($value)) {
            $value = UrlHelper::getBoolString($value);
        }

        $params[$parameter] = $value;

        return $params;
    }


    //todo: (@frankmayer) check if refactoring a bit more if it makes sense...
    /**
     * Helper function that runs through the options given and includes them into the parameters array given.
     * Only options that are set in $includeArray will be included.
     * This is only for options that are to be sent to the ArangoDB server in form of url parameters (like 'waitForSync', 'keepNull', etc...) .
     *
     * @param array $options      - The options array that holds the options to include in the parameters
     * @param array $params       - The parameters into which the options will be included.
     * @param array $includeArray - The array that defines which options are allowed to be included, and what their default value is. for example: 'waitForSync'=>true
     *
     * @return array $params - array of parameters for use in a url
     */
    protected function includeOptionsInParams($options, $params, $includeArray = array())
    {
        #$value = null;
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                if (array_key_exists($key, $includeArray)) {
                    $params[$key] = $value;
                    if ($value === null) {
                        $params[$key] = $includeArray[$key];
                    }
                }
            }
        }

        return $params;
    }


    //todo: (@frankmayer) check if refactoring a bit more if it makes sense...
    /**
     * Helper function that runs through the options given and includes them into the parameters array given.
     * Only options that are set in $includeArray will be included.
     * This is only for options that are to be sent to the ArangoDB server in a json body(like 'limit', 'skip', etc...) .
     *
     * @param array $options      - The options array that holds the options to include in the parameters
     * @param array $body         - The array into which the options will be included.
     * @param array $includeArray - The array that defines which options are allowed to be included, and what their default value is. for example: 'waitForSync'=>true
     *
     * @return array $params - array of parameters for use in a url
     */
    protected function includeOptionsInBody($options, $body, $includeArray = array())
    {
        #$value = null;
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                if (array_key_exists($key, $includeArray)) {
                    $body[$key] = $value;
                    if ($value === null) {
                        if ($includeArray[$key] !== null) {
                            $body[$key] = $includeArray[$key];
                        }
                    }
                }
            }
        }

        return $body;
    }
}
