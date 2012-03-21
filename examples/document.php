<?php

namespace triagens\Avocado;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/* set up a trace function that will be called for each communication with the server */
$traceFunc = function($type, $data) {
  print "TRACE FOR ". $type . PHP_EOL;
  var_dump($data);
};

/* set up connection options */
$connectionOptions = array(
  "port" => 9000,
  "host" => "localhost",
  "timeout" => 3,
  "trace" => $traceFunc,
);

try {
  $connection = new Connection($connectionOptions);

  // get the ids of all documents in the collection
  $handler = new DocumentHandler($connection);
  $result = $handler->getAllIds("fux");
  var_dump($result);

  // create a new document
  $document = new Document();
  $document->set("name", "fux");
  $document->level = 1;
  $document->vists = array(1, 2, 3);

  $id = $handler->add("fux", $document);
  var_dump("CREATED A NEW DOCUMENT WITH ID: ", $id);

  // get this document from the server
  $result = $handler->get("fux", $id);
  var_dump($result);

  // update this document
  $document->nonsense = "hihi";
  unset($document->name);
  $result = $handler->update("fux", $document);
  var_dump($result);
  
  // get the updated document back
  $result = $handler->get("fux", $id);
  var_dump($result);

  // delete the document
  $result = $handler->delete("fux", $id);
  var_dump($result);

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
