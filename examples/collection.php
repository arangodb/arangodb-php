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
  ConnectionOptions::OPTION_PORT            => 9000,
  ConnectionOptions::OPTION_HOST            => "localhost",
  ConnectionOptions::OPTION_TIMEOUT         => 3,
  ConnectionOptions::OPTION_TRACE           => $traceFunc,
  ConnectionOptions::OPTION_CREATE          => false,
  ConnectionOptions::OPTION_UPDATE_POLICY   => "last",
);

try {
  $connection = new Connection($connectionOptions);
  $handler = new CollectionHandler($connection);
  
  // get an existing collection
  $result = $handler->get("peng");
  var_dump($result);
  
  // get number of documents from an existing collection
  $result = $handler->getCount("peng");
  var_dump($result);
  
  // get figures for an existing collection
  $result = $handler->getFigures("peng");
  var_dump($result);
  
  // create a new collection
  $col = new Collection();
  $col->setName("hihi2");
  $result = $handler->add($col);
  var_dump($result);
  
  // get an existing collection
  $result = $handler->get("hihi2");
  var_dump($result);

  // delete the collection
  $result = $handler->delete("hihi2");
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
