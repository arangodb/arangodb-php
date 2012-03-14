<?php

namespace triagens;

include "autoload.php";

$connection = new AvocadoConnection(array("port" => 9000, "host" => "localhost", "timeout" => 3));

try {
  $statement = new AvocadoStatement($connection, array("query" => "SELECT f FROM fux f WHERE f.lala == @hans@ LIMIT 5", "count" => true, "maxResults" => 5, "bindVars" => array("hans" => "1"), "sanitize" => true));

  $cursor = $statement->execute();
  var_dump($cursor->getAll());
}
catch (Exception $e) {
  var_dump($e);
}
