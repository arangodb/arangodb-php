<?php
/**
 * ArangoDB PHP client
 * File: bootstrap_connection_keep_alive.php
 *
 * @package ArangoDBClient
 * @author  Frank Mayer
 */

namespace ArangoDBClient;

require __DIR__ . '/../autoload.php';

if (class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias(\PHPUnit\Framework\TestCase::class, 'PHPUnit_Framework_TestCase');
}

/* set up a trace function that will be called for each communication with the server */

function isCluster(Connection $connection)
{
    static $isCluster = null;

    if ($isCluster === null) {
        $adminHandler = new AdminHandler($connection);
        try {
            $role      = $adminHandler->getServerRole();
            $isCluster = ($role === 'COORDINATOR' || $role === 'DBSERVER');
        } catch (\Exception $e) {
            // maybe server version is too "old"
            $isCluster = false;
        }
    }

    return $isCluster;
}

function getConnectionOptions()
{
    $traceFunc = function ($type, $data) {
        print 'TRACE FOR ' . $type . PHP_EOL;
    };

    return [
        ConnectionOptions::OPTION_ENDPOINT           => 'tcp://localhost:8529',
        // endpoint to connect to
        ConnectionOptions::OPTION_CONNECTION         => 'Keep-Alive',
        // can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
        ConnectionOptions::OPTION_AUTH_TYPE          => 'Basic',
        // use basic authorization
        ConnectionOptions::OPTION_AUTH_USER          => 'root',
        // user for basic authorization
        ConnectionOptions::OPTION_AUTH_PASSWD        => '',
        // password for basic authorization
        ConnectionOptions::OPTION_TIMEOUT            => 12,
        // timeout in seconds
        //ConnectionOptions::OPTION_TRACE       => $traceFunc,              // tracer function, can be used for debugging
        ConnectionOptions::OPTION_CREATE             => false,
        // do not create unknown collections automatically
        ConnectionOptions::OPTION_UPDATE_POLICY      => UpdatePolicy::LAST,
        // last update wins
        ConnectionOptions::OPTION_CHECK_UTF8_CONFORM => true
        // force UTF-8 checks for data
    ];
}


function getConnection()
{
    return new Connection(getConnectionOptions());
}
