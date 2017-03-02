<?php

namespace ArangoDBClient;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/* set up a trace function that will be called for each communication with the server */
$traceFunc = function ($type, $data) {
    print 'TRACE FOR ' . $type . PHP_EOL;
    var_dump($data);
};

/* set up connection options */
$connectionOptions = [
    ConnectionOptions::OPTION_DATABASE => '_system',               // database name

    // normal unencrypted connection via TCP/IP
    ConnectionOptions::OPTION_ENDPOINT => 'tcp://localhost:8529',  // endpoint to connect to

    // // connection via SSL
    // ConnectionOptions::OPTION_ENDPOINT        => 'ssl://localhost:8529',  // SSL endpoint to connect to
    // ConnectionOptions::OPTION_VERIFY_CERT     => false,                   // SSL certificate validation
    // ConnectionOptions::OPTION_ALLOW_SELF_SIGNED => true,                  // allow self-signed certificates
    // ConnectionOptions::OPTION_CIPHERS         => 'DEFAULT',               // https://www.openssl.org/docs/manmaster/apps/ciphers.html

    // // connection via UNIX domain socket
    // ConnectionOptions::OPTION_ENDPOINT        => 'unix:///tmp/arangodb.sock',  // UNIX domain socket

    ConnectionOptions::OPTION_CONNECTION  => 'Keep-Alive',            // can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
    ConnectionOptions::OPTION_AUTH_TYPE   => 'Basic',                 // use basic authorization

    // authentication parameters (note: must also start server with option `--server.disable-authentication false`)
    ConnectionOptions::OPTION_AUTH_USER   => 'root',                  // user for basic authorization
    ConnectionOptions::OPTION_AUTH_PASSWD => '',                      // password for basic authorization

    ConnectionOptions::OPTION_TIMEOUT       => 30,                      // timeout in seconds
    ConnectionOptions::OPTION_TRACE         => $traceFunc,              // tracer function, can be used for debugging
    ConnectionOptions::OPTION_CREATE        => false,                   // do not create unknown collections automatically
    ConnectionOptions::OPTION_UPDATE_POLICY => UpdatePolicy::LAST,      // last update wins
];
