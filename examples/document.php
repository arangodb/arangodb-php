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
  ConnectionOptions::OPTION_PORT            => 9000,               // port to connect to
  ConnectionOptions::OPTION_HOST            => "localhost",        // host to connect to
  ConnectionOptions::OPTION_TIMEOUT         => 3,                  // timeout in seconds
  ConnectionOptions::OPTION_TRACE           => $traceFunc,         // tracer function
  ConnectionOptions::OPTION_CREATE          => false,              // do not create unknown collections automatically
  ConnectionOptions::OPTION_UPDATE_POLICY   => UpdatePolicy::LAST, // last update wins
);

try {
  $connection = new Connection($connectionOptions);
  $handler = new DocumentHandler($connection);

  // get documents by example
  $result = $handler->getByExample("users", array("name"=>"John","age"=>19));
  var_dump($result);

  // get the ids of all documents in the collection
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
