<?php

namespace triagens\ArangoDb;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';

/* set up some example statements */
$statements = array(
    "for u in users return u"                                       => array(),
    "for u in users return u"                                       => null,
    "for u in users filter u.id == @id return u"                    => array("id" => 6),
    "for u in users filter u.id == @id && u.name != @name return u" => array("id" => 1, "name" => "fox"),
);


try {
    $connection = new Connection($connectionOptions);

    foreach ($statements as $query => $bindVars) {
        $statement = new Statement($connection, array(
                                                     "query"     => $query,
                                                     "count"     => true,
                                                     "batchSize" => 1000,
                                                     "bindVars"  => $bindVars,
                                                     "sanitize"  => true,
                                                ));

        print $statement . "\n\n";

        $cursor = $statement->execute();
        var_dump($cursor->getAll());
    }
} catch (ConnectException $e) {
    print $e . PHP_EOL;
} catch (ServerException $e) {
    print $e . PHP_EOL;
} catch (ClientException $e) {
    print $e . PHP_EOL;
}
