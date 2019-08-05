<?php

namespace ArangoDBClient;

require __DIR__ . '/init.php';

try {
    $connection = new Connection($connectionOptions);
    $collectionHandler = new CollectionHandler($connection);

    // set up a document collection "testCollection"
    $collection = new Collection('testCollection');
    try {
        $collectionHandler->create($collection);
    } catch (\Exception $e) {
        // collection may already exist - ignore this error for now
        //
        // make sure it is empty
        $collectionHandler->truncate($collection);
    }

    $statement = new Statement($connection, [
        'query'     => 'FOR i IN 1..10000 INSERT { _key: CONCAT("test", i) } INTO @@collection',
        'bindVars'  => [ '@collection' => 'testCollection' ],
    ]);

    $statement->execute();

    echo 'COUNT AFTER AQL INSERT: ' . $collectionHandler->count($collection) . PHP_EOL;

    // next query has a potentially huge result - therefore set the "stream" flag
    $statement = new Statement($connection, [
        'query'     => 'FOR doc IN @@collection RETURN doc',
        'bindVars'  => [ '@collection' => 'testCollection' ],
        'stream'    => true
    ]);

    $cursor = $statement->execute();

    $counter = 0;
    foreach ($cursor as $document) {
        ++$counter;
        print '- DOCUMENT KEY: ' . $document->getKey() . PHP_EOL;
    }

    print 'QUERY RETURNED ' . $counter . ' DOCUMENTS' . PHP_EOL;

} catch (ConnectException $e) {
    print $e . PHP_EOL;
} catch (ServerException $e) {
    print $e . PHP_EOL;
} catch (ClientException $e) {
    print $e . PHP_EOL;
}
