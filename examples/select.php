<?php

namespace triagens;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

$statements = array(
  "select f from fux f" => array(),
  "select u from users u" => NULL,
  "select u from users u where u.id == @id@" => array("id" => 6),
  "select u from users u where u.id == @id@ && u.name != @name@" => array("id" => 6, "name" => "fux"),
);

$traceFunc = function($type, $data) {
  print "TRACE FOR ". $type . PHP_EOL;
  var_dump($data);
};

$connectionOptions = array(
  "port" => 9000,
  "host" => "localhost",
  "timeout" => 3,
  "trace" => $traceFunc,
);

try {
  $connection = new AvocadoConnection($connectionOptions);

  foreach ($statements as $query => $bindVars) {
    $statement = new AvocadoStatement($connection, array("query" => $query, "count" => true, "batchSize" => 1, "bindVars" => $bindVars, "sanitize" => true));

    $cursor = $statement->execute();
    var_dump($cursor->getAll());
  }
}
catch (AvocadoConnectException $e) {
  var_dump($e->getMessage());
}
catch (AvocadoServerException $e) {
  var_dump($e->getMessage(), $e->getServerCode(), $e->getServerMessage());
}
catch (AvocadoClientException $e) {
  var_dump($e->getMessage());
}
