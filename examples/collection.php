<?php

namespace triagens\ArangoDb;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';

try {
    $connection = new Connection($connectionOptions);
    $handler    = new CollectionHandler($connection);

    // create a new collection
    $col = new Collection();
    $col->setName("hihi");
    $result = $handler->add($col);
    var_dump($result);

    // get an existing collection
    $result = $handler->get("hihi");
    var_dump($result);

    // get an existing collection
    $result = $handler->get("hihi");
    var_dump($result);

    // get number of documents from an existing collection
    $result = $handler->getCount("hihi");
    var_dump($result);

    // get figures for an existing collection
    $result = $handler->getFigures("hihi");
    var_dump($result);

    // delete the collection
    $result = $handler->delete("hihi");
    var_dump($result);
    // rename a collection
    // $handler->rename($col, "hihi30");

    // truncate an existing collection
    // $result = $handler->truncate("hihi");
} catch (ConnectException $e) {
    print $e . PHP_EOL;
} catch (ServerException $e) {
    print $e . PHP_EOL;
} catch (ClientException $e) {
    print $e . PHP_EOL;
}
