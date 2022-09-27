<?php

/**
 * ArangoDB PHP client: exception base class
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 * Exception base class used to throw Arango specific exceptions
 *
 * <br>
 *
 * @package   ArangoDBClient
 * @since     0.2
 */
class Exception extends \Exception
{
    /**
     * Exception constructor.
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        if (self::$enableLogging) {
            @error_log(get_class($this) . ': ' . $message);
            @error_log('Stack trace:');
            foreach (explode(PHP_EOL, $this->getTraceAsString()) as $i => $line) {
                @error_log('   ' . $line);
            }
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the current exception logging status
     *
     * @return boolean - current exception logging status
     */
    public static function getLogging()
    {
        return self::$enableLogging;
    }
    
    
    /**
     * Set the current exception logging status
     * @param bool  $enable - whether or not to enable logging
     */
    public static function setLogging($enable)
    {
        self::$enableLogging = $enable;
    }

    /**
     * Turn on exception logging
     */
    public static function enableLogging()
    {
        self::setLogging(true);
    }

    /**
     * Turn off exception logging
     */
    public static function disableLogging()
    {
        self::setLogging(false);
    }

    private static $enableLogging = false;
}

class_alias(Exception::class, '\triagens\ArangoDb\Exception');
