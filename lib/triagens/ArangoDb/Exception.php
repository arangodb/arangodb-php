<?php

/**
 * ArangoDB PHP client: exception base class
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Exception base class used to throw Arango specific exceptions
 *
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class Exception extends
    \Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        if (is_string($message) && self::$enableLogging) {
            @error_log(get_class($this) . ': ' . $message);
            @error_log('Stack trace:');
            foreach (explode(PHP_EOL, $this->getTraceAsString()) as $i => $line) { 
                @error_log('   ' . $line);
            }
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Turn on exception logging
     */
    public static function enableLogging ()
    {
        self::$enableLogging = true;
    }
 
    /**
     * Turn off exception logging
     */
    public static function disableLogging ()
    {
        self::$enableLogging = false;
    } 

    private static $enableLogging = false;
}
