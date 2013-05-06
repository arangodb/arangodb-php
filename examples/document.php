<?php

namespace triagens\ArangoDb;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';


try {
    $connection = new Connection($connectionOptions);
    $handler    = new DocumentHandler($connection);

    // create a new document
    $user = new Document();
    $user->set("name", "John");
    $user->age = 19;

    $id = $handler->add("users", $user);

    // get documents by example
    $cursor = $handler->getByExample("users", array("name" => "John", "age" => 19));
    var_dump($cursor->getAll());

    // get the ids of all documents in the collection
    $result = $handler->getAllIds("users");
    var_dump($result);

    // create another new document
    $user = new Document();
    $user->set("name", "j-lo");
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
} catch (ConnectException $e) {
    print $e . PHP_EOL;
} catch (ServerException $e) {
    print $e . PHP_EOL;
} catch (ClientException $e) {
    print $e . PHP_EOL;
}
