<?php

namespace triagens\ArangoDb;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/* set up a trace function that will be called for each communication with the server */
$traceFunc = function($type, $data) {
  print "TRACE FOR ". $type . PHP_EOL;
  var_dump($data);
};

/* set up connection options */
$connectionOptions = array(
  ConnectionOptions::OPTION_ENDPOINT        => 'tcp://localhost:8529', // endpoint to connect to
  ConnectionOptions::OPTION_CONNECTION      => 'Close',                 // can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
  ConnectionOptions::OPTION_AUTH_TYPE       => 'Basic',                 // use basic authorization
  /*
  ConnectionOptions::OPTION_AUTH_USER       => '',                      // user for basic authorization
  ConnectionOptions::OPTION_AUTH_PASSWD     => '',                      // password for basic authorization
  ConnectionOptions::OPTION_PORT            => 8529,                    // port to connect to (deprecated, should use endpoint instead)
  ConnectionOptions::OPTION_HOST            => "localhost",             // host to connect to (deprecated, should use endpoint instead)
  */
  ConnectionOptions::OPTION_TIMEOUT         => 30,                      // timeout in seconds
  ConnectionOptions::OPTION_TRACE           => $traceFunc,              // tracer function, can be used for debugging
  ConnectionOptions::OPTION_CREATE          => false,                   // do not create unknown collections automatically
  ConnectionOptions::OPTION_UPDATE_POLICY   => UpdatePolicy::LAST,      // last update wins
);
