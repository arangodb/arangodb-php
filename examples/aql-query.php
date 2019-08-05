<?php

namespace ArangoDBClient;

require __DIR__ . '/init.php';

/* set up some example statements */
$statements = [
    'FOR u IN users RETURN u'                                       => null,
    'FOR u IN users FILTER u.id == @id RETURN u'                    => ['id' => 6],
    'FOR u IN users FILTER u.id == @id && u.name != @name RETURN u' => ['id' => 1, 'name' => 'fox'],
];


try {
    $connection = new Connection($connectionOptions);
    $collectionHandler = new CollectionHandler($connection);
    $documentHandler = new DocumentHandler($connection);

    // set up a document collection "testCollection"
    $collection = new Collection('users');
    try {
        $collectionHandler->create($collection);
    } catch (\Exception $e) {
        // collection may already exist - ignore this error for now
        //
        // make sure it is empty
        $collectionHandler->truncate($collection);
    }

    $docs = [
        Document::createFromArray(['name' => 'foo', 'id' => 1]),
        Document::createFromArray(['name' => 'bar', 'id' => 2]),
        Document::createFromArray(['name' => 'baz', 'id' => 3]),
        Document::createFromArray(['name' => 'fox', 'id' => 4]),
        Document::createFromArray(['name' => 'qaa', 'id' => 5]),
        Document::createFromArray(['name' => 'qux', 'id' => 6]),
        Document::createFromArray(['name' => 'quu', 'id' => 7]),
    ];
    foreach ($docs as $doc) {
        $documentHandler->save($collection, $doc);
    }

    foreach ($statements as $query => $bindVars) {
        $statement = new Statement($connection, [
                'query'     => $query,
                'count'     => true,
                'batchSize' => 1000,
                'bindVars'  => $bindVars,
                'sanitize'  => true,
            ]
        );

        echo 'RUNNING STATEMENT ' . $statement . PHP_EOL;

        $cursor = $statement->execute();
        foreach ($cursor->getAll() as $doc) {
            echo '- RETURN VALUE: ' . json_encode($doc) . PHP_EOL;
        }

        echo PHP_EOL;
    }
} catch (ConnectException $e) {
    print $e . PHP_EOL;
} catch (ServerException $e) {
    print $e . PHP_EOL;
} catch (ClientException $e) {
    print $e . PHP_EOL;
}
