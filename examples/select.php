<?php

namespace triagens\Avocado;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/* set up some example statements */
$statements = array(
  "select f from fux f" => array(),
  "select u from users u" => NULL,
  "select u from users u where u.id == 1 +@id@" => array("id" => 6),
  "select u from users u where u.id == 2+ @id@" => array("id" => 6),
  "select u from users u where u.id == 3+ @id@" => array("id" => 6),
  "select u from users u where u.id == 4+ @id@" => array("id" => 6),
  "select u from users u where u.id == 5+ @id@" => array("id" => 6),
  "select u from users u where u.id == @id@" => array("id" => 6),
  "select u from users u where u.id == @id@ && u.name != @name@" => array("id" => 6, "name" => "fux"),
);

/* set up a trace function that will be called for each communication with the server */
$traceFunc = function($type, $data) {
  print "TRACE FOR ". $type . PHP_EOL;
  var_dump($data);
};

/* set up connection options */
$connectionOptions = array(
  ConnectionOptions::OPTION_PORT            => 9000,
  ConnectionOptions::OPTION_HOST            => "localhost",
  ConnectionOptions::OPTION_TIMEOUT         => 3,
  ConnectionOptions::OPTION_TRACE           => $traceFunc,
);

try {
  $connection = new Connection($connectionOptions);

  foreach ($statements as $query => $bindVars) {
    $statement = new Statement($connection, array(
      "query" => $query, 
      "count" => true, 
      "batchSize" => 5, 
      "bindVars" => $bindVars, 
      "sanitize" => true,
    ));

    print $statement."\n\n";

    $cursor = $statement->execute();
    var_dump($cursor->getAll());
  }
}
catch (ConnectException $e) {
  var_dump($e->getMessage());
}
catch (ServerException $e) {
  var_dump($e->getMessage(), $e->getServerCode(), $e->getServerMessage());
}
catch (ClientException $e) {
  var_dump($e->getMessage());
}
