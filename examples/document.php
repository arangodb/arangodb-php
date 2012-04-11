<?php

namespace triagens\Avocado;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';


try {
  $connection = new Connection($connectionOptions);
  $handler = new DocumentHandler($connection);

  // get documents by example
  $result = $handler->getByExample("users", array("name"=>"John","age"=>19));
  var_dump($result);

  // get the ids of all documents in the collection
  $result = $handler->getAllIds("users");
  var_dump($result);

  // create a new document
  $user = new Document();
  $user->set("name", "users");
  $user->level = 1;
  $user->vists = array(1, 2, 3);

  $id = $handler->add("users", $user);
  var_dump("CREATED A NEW DOCUMENT WITH ID: ", $id);

  // get this document from the server
  $userFromServer = $handler->getById("users", $id);
  var_dump($userFromServer);

  // update this document
  $userFromServer->nonsense = "hihi";
  unset($userFromServer->name);
  $result = $handler->update($userFromServer);
  var_dump($result);
  
  // get the updated document back
  $result = $handler->get("users", $id);
  var_dump($result);

  // delete the document
  $result = $handler->deleteById("users", $id);
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
