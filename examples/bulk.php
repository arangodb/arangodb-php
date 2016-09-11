<?php

namespace triagens\ArangoDb;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';

try {
    $connection = new Connection($connectionOptions);
    $handler    = new CollectionHandler($connection);
    
    if ($handler->has('example')) {
        $handler->delete('example');
    }
    
    $col = new Collection();
    $col->setName('example');
    $result = $handler->add($col);

    // create a statement to insert 100 example documents
    $statement = new Statement($connection, array(
        'query' => 'FOR i IN 1..100 INSERT { _key: CONCAT("example", i) } IN example'
    ));
    $statement->execute();
    
    // print number of documents
    var_dump($handler->getCount('example'));

    // later on, we can assemble a list of document keys
    $keys = array();
    for ($i = 1; $i <= 100; ++$i) {
      $keys[] = 'example' . $i;
    }
    // and fetch all the documents at once by their keys
    $documents = $handler->lookupByKeys('example', $keys);

    var_dump($documents);

    // we can also bulk-remove them:
    $result = $handler->removeByKeys('example', $keys);

    var_dump($result);
    
    // print number of documents after bulk removal
    var_dump($handler->getCount('example'));

} catch (ConnectException $e) {
    print $e . PHP_EOL;
} catch (ServerException $e) {
    print $e . PHP_EOL;
} catch (ClientException $e) {
    print $e . PHP_EOL;
}
