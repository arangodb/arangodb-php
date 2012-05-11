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
  ConnectionOptions::OPTION_PORT            => 8529,               // port to connect to
  ConnectionOptions::OPTION_HOST            => "localhost",        // host to connect to
  ConnectionOptions::OPTION_TIMEOUT         => 3,                  // timeout in seconds
  ConnectionOptions::OPTION_TRACE           => $traceFunc,         // tracer function
  ConnectionOptions::OPTION_CREATE          => false,              // do not create unknown collections automatically
  ConnectionOptions::OPTION_UPDATE_POLICY   => UpdatePolicy::LAST, // last update wins
);

