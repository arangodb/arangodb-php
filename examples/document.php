<?php

namespace triagens;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

$traceFunc = function($type, $data) {
  print "TRACE FOR ". $type . PHP_EOL;
  var_dump($data);
};

///$traceFunc = NULL;
$connectionOptions = array(
  "port" => 9000,
  "host" => "localhost",
  "timeout" => 3,
  "trace" => $traceFunc,
);

try {
  $connection = new AvocadoConnection($connectionOptions);

  $document = new AvocadoDocument();
  $document->set("name", "fux");
  $document->level = 1;
  $document->vists = array(1, 2, 3);

  $handler = new AvocadoDocumentHandler($connection);
  $result = $handler->getAllIds("fux");

  $id = $handler->add("fux", $document);
  var_dump($id);

  $result = $handler->get("fux", $id);
  var_dump($result);
  
  $document->panzer = "hihi";
  unset($document->name);
  $result = $handler->update("fux", $id, $document);
  var_dump($result);
  
  $result = $handler->get("fux", $id);
  var_dump($result);

  $result = $handler->delete("fux", $id);
  var_dump($result);

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
