<?php

namespace triagens\ArangoDb;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';


try {
    $connection        = new Connection($connectionOptions);
    $collectionHandler = new CollectionHandler($connection);
    $documentHandler   = new DocumentHandler($connection);
    $edgeHandler       = new EdgeHandler($connection);

    // set up two document collections
    $collection        = new Collection("employees");
    try {
        $collectionHandler->add($collection);
    }
    catch (\Exception $e) {
        // collection may already exist - ignore this error for now
    }
    
    $collection        = new Collection("departments");
    try {
        $collectionHandler->add($collection);
    }
    catch (\Exception $e) {
        // collection may already exist - ignore this error for now
    }
    
    // set up an edge collection to link the two previous collections
    $collection        = new Collection("worksFor");
    $collection->setType(3);
    
    try {
        $collectionHandler->add($collection);
    }
    catch (\Exception $e) {
        // collection may already exist - ignore this error for now
    }
    
    // create a new department
    $marketing = Document::createFromArray(array("name" => "Marketing"));
    $documentHandler->save("departments", $marketing);
    
    // create another department
    $finance = Document::createFromArray(array("name" => "Finance"));
    $documentHandler->save("departments", $finance);

    // create a new employee
    $john = Document::createFromArray(array("name" => "John"));
    $documentHandler->save("employees", $john);

    // create another employee
    $jane = Document::createFromArray(array("name" => "Jane"));
    $documentHandler->save("employees", $jane);

    // now insert a link between Marketing and Jane
    $worksFor = Edge::createFromArray(array("startDate" => "2009-06-23", "endDate" => "2014-11-12"));
    $edgeHandler->saveEdge("worksFor", $marketing->getHandle(), $jane->getHandle(), $worksFor);
    
    // now insert a link between Finance and Jane
    $worksFor = Edge::createFromArray(array("startDate" => "2014-11-12"));
    $edgeHandler->saveEdge("worksFor", $finance->getHandle(), $jane->getHandle(), $worksFor);
    
    // now insert a link between Finance and John
    $worksFor = Edge::createFromArray(array("startDate" => "2012-04-01"));
    $edgeHandler->saveEdge("worksFor", $finance->getHandle(), $john->getHandle(), $worksFor);


} catch (ConnectException $e) {
    print $e . PHP_EOL;
} catch (ServerException $e) {
    print $e . PHP_EOL;
} catch (ClientException $e) {
    print $e . PHP_EOL;
}
