<?php

/**
 * ArangoDB PHP client: base handler
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * A base class for REST-based handlers
 *
 * @package ArangoDbPhpClient
 */
abstract class Handler {
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
   * @return void
   */
  public function __construct(Connection $connection) {
    $this->_connection = $connection;
  }

  /**
   * Return the connection object
   *
   * @return Connection - the connection object
   */
  protected function getConnection() {
    return $this->_connection;
  }



    //todo: (@frankmayer) check if refactoring a bit more if it makes sense...
    /**
     * Helper function that validates and includes the policy setting into the parameters array given.
     * @param array $options - The options array that may hold the policy to include in the parameters. If it's not an array, then the value is the policy value.
     * @param array $params - The parameters into which the options will be included.
     * @param mixed $policyOption - the policyOption key to use.
     *
     * @return array $params - array of parameters for use in a url
     */
    protected function validateAndIncludePolicyInParams($options, $params, $policyOption)
    {
        $policy   = null;

        if (!is_array($options)) {
            $policy = $options;
        } else {
            $policy = array_key_exists('policy', $options) ? $options['policy'] : $policy;
        }

        if ($policy === null) {
            $policy = $this->getConnection()->getOption($policyOption);
        }

        UpdatePolicy::validate($policy);

        $params[$policyOption] = $policy;

        return $params;
    }


    /**
     * Helper function that runs through the options given and includes them into the parameters array given.
     * Only options that are set in $includeArray will be included.
     *
     * @param array $options - The options array that holds the options to include in the parameters
     * @param array $params - The parameters into which the options will be included.
     * @param array $includeArray - The array that defines which options are allowed to be included, and what their default value is. for example: 'waitForSync'=>true
     *
     * @return array $params - array of parameters for use in a url
     */
    protected function includeOptionsInParams($options, $params, $includeArray=array())
    {
        #$value = null;
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                if (array_key_exists($key, $includeArray)  ) {
                    $params[$key] = $value;
                    if ($value === NULL) {
                        $params[$key]= $includeArray[$key];
                    }
                }

            }
        }
        return $params;
    }

}
