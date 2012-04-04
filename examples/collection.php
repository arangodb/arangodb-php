<?php

namespace triagens\Avocado;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';

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
  
  // rename a collection
  // $handler->rename($col,"hihi30");
  
  // truncate an existing collection
  // $result = $handler->truncate("foo");
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
