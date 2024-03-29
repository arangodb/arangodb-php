# ArangoDB-PHP - A PHP client for ArangoDB

This driver for ArangoDB, called the ArangoDB-PHP client,
allows REST-based access to documents on the server. The _DocumentHandler_ class
should be used for these purposes. There is an example for REST-based documents
access in the [`examples/document.php`](examples/document.php) file.

Furthermore, the PHP client also allows to issue more AQL complex queries using
the _Statement_ class. There is an example for this kind of statements in the
[`examples/aql-query.php`](examples/aql-query.php) file.

To use the PHP client, you must include the file `autoloader.php` from the main
directory. The autoloader cares about loading additionally required classes on
the fly. The autoloader can be nested with other autoloaders.

The ArangoDB PHP client is an API that allows you to send and retrieve documents
from ArangoDB from out of your PHP application. The client library itself is
written in PHP and has no further dependencies but just plain PHP 5.6
(or higher).

The client library provides document and collection classes you can use to work
with documents and collections in an object-oriented fashion. When exchanging
document data with the server, the library internally uses the
[HTTP REST interface of ArangoDB](https://www.arangodb.com/docs/stable/http/index.html).
The library user does not have to care about this fact as all the details of the
REST interface are abstracted by the client library.

## Requirements

- PHP version 5.6 or higher

Note on PHP version support:

This driver ceases to support old PHP versions as soon as they have reached
end-of-life status. Support is removed with the next minor or patch version of
the driver to be released.

In general, it is recommended to always use the latest PHP versions in order to
take advantage of all the improvements (especially in performance).

## Important version information on ArangoDB-PHP

The ArangoDB-PHP driver version has to match with the ArangoDB version:

- ArangoDB-PHP v3.8.x is on par with the functionality of ArangoDB v3.8.x
- ArangoDB-PHP v3.9.x is on par with the functionality of ArangoDB v3.9.x

etc.

## Installation using Composer or Git

To get started, you need PHP 5.6 or higher plus an ArangoDB server running on
any host that you can access.

There are two alternative ways to get the ArangoDB PHP client:

- Using Composer
- Cloning the git repository

### Alternative 1: Using Composer

If you use [Composer](https://getcomposer.org/), you can run the following
command in a command-line to install the PHP client:

```
composer require triagens/arangodb
```

### Alternative 2: Cloning the Git repository

When preferring this alternative, you need to have a
[Git client](https://git-scm.com/downloads) installed. To clone the
ArangoDB-PHP client repository from GitHub, execute the following command in
your project directory:

```
git clone "https://github.com/arangodb/arangodb-php.git"
```

This creates an `arangodb-php` subdirectory in your current directory. It
contains all the files of the client library. It also includes a dedicated
autoloader that you can use for autoloading the client libraries class files.
To invoke this autoloader, add the following line to your PHP files that need
the library:

```php
require 'arangodb-php/autoload.php';
```

The ArangoDB-PHP client's autoloader only cares about its own class files and
does not handle any other files. That means it is fully nestable with other
autoloaders.

If you do not wish to include autoload.php to load and setup the autoloader, you
can invoke the autoloader directly:

```php
require 'arangodb-php/lib/ArangoDBClient/autoloader.php';
\ArangoDBClient\Autoloader::init();
```

## Set up the connection

In order to use ArangoDB, you need to specify the connection options. You can do
so by creating a PHP array `$connectionOptions`. Put this code into a file named
`test.php` in your current directory:

```php
// use the following line when using Composer
// require __DIR__ . '/vendor/composer/autoload.php';

// use the following line when using git
require __DIR__ . '/arangodb-php/autoload.php';

// set up some aliases for less typing later
use ArangoDBClient\Collection as ArangoCollection;
use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use ArangoDBClient\Document as ArangoDocument;
use ArangoDBClient\Exception as ArangoException;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use ArangoDBClient\Statement as ArangoStatement;
use ArangoDBClient\UpdatePolicy as ArangoUpdatePolicy;

// set up some basic connection options
$connectionOptions = [
    // database name
    ArangoConnectionOptions::OPTION_DATABASE => '_system',
    // server endpoint to connect to
    ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
    // authorization type to use (currently supported: 'Basic')
    ArangoConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
    // user for basic authorization
    ArangoConnectionOptions::OPTION_AUTH_USER => 'root',
    // password for basic authorization
    ArangoConnectionOptions::OPTION_AUTH_PASSWD => '',
    // connection persistence on server. can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
    ArangoConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
    // connect timeout in seconds
    ArangoConnectionOptions::OPTION_TIMEOUT => 3,
    // whether or not to reconnect when a keep-alive connection has timed out on server
    ArangoConnectionOptions::OPTION_RECONNECT => true,
    // optionally create new collections when inserting documents
    ArangoConnectionOptions::OPTION_CREATE => true,
    // optionally create new collections when inserting documents
    ArangoConnectionOptions::OPTION_UPDATE_POLICY => ArangoUpdatePolicy::LAST,
];

// turn on exception logging (logs to whatever PHP is configured)
ArangoException::enableLogging();

$connection = new ArangoConnection($connectionOptions);
```

This makes the client connect to ArangoDB

- running on localhost (`OPTION_HOST`)
- on the default port 8529 (`OPTION_PORT`)
- with a connection timeout of 3 seconds (`OPTION_TIMEOUT`)

When creating new documents in a collection that does not yet exist, you have
the following choices:

- **auto-generate a new collection**:
  if you prefer that, set `OPTION_CREATE` to `true`

- **fail with an error**:
  if you prefer this behavior, set `OPTION_CREATE` to `false`

When updating a document that was previously/concurrently updated by another
user, you can select between the following behaviors:

- **last update wins**:
  if you prefer this, set `OPTION_UPDATE_POLICY` to last

- **fail with a conflict error**:
  if you prefer that, set `OPTION_UPDATE_POLICY` to conflict

### Setting up Active Failover

By default, the PHP client connects to a single endpoint only, by specifying a
string value for the endpoint in the connection options, e.g.

```php
$connectionOptions = [
    ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529'
];
```

To set up multiple servers to connect to, it is also possible to specify an
array of servers instead:

```php
$connectionOptions = [
    ArangoConnectionOptions::OPTION_ENDPOINT    => [ 'tcp://localhost:8531', 'tcp://localhost:8532', 'tcp://localhost:8530' ]
];
```

Using this option requires ArangoDB 3.3 or higher and the database running in
Active Failover mode.

The driver tries to connect to the first server endpoint in the endpoints array
by default, and only try the following servers if no connection can be
established. If no connection can be made to any server, the driver throws an
exception.

As it is unknown to the driver which server from the array is the current
leader, the driver connects to the specified servers in array order by default.
However, to spare a few unnecessary connection attempts to failed servers, it is
possible to set up caching (using Memcached) for the server list. The cached
value contains the last working server first, so that as few connection attempts
as possible need to be made.

In order to use this caching, it is required to install the Memcached module for
PHP, and to set up the following relevant options in the connection options:

```php
$connectionOptions = [
    // memcached persistent id (will be passed to Memcached::__construct)
    ArangoConnectionOptions::OPTION_MEMCACHED_PERSISTENT_ID => 'arangodb-php-pool',

    // memcached servers to connect to (will be passed to Memcached::addServers)
    ArangoConnectionOptions::OPTION_MEMCACHED_SERVERS       => [ [ '127.0.0.1', 11211 ] ],

    // memcached options (will be passed to Memcached::setOptions)
    ArangoConnectionOptions::OPTION_MEMCACHED_OPTIONS       => [ ],

    // key to store the current endpoints array under
    ArangoConnectionOptions::OPTION_MEMCACHED_ENDPOINTS_KEY => 'arangodb-php-endpoints'

    // time-to-live for the endpoints array stored in memcached
    ArangoConnectionOptions::OPTION_MEMCACHED_TTL           => 600
];
```

## Create collections

> This is just to show how a collection is created. For these examples it is not
> needed to create a collection prior to inserting a document, as we set
> `ArangoConnectionOptions::OPTION_CREATE` to `true`.

So, after we get the settings, we can start with creating a collection. We
create a collection named _users_.

The below code first sets up the collection locally in a variable name `$user`,
and then pushes it to the server and returns the collection ID created by the
server:

```php
$collectionHandler = new ArangoCollectionHandler($connection);

// clean up first
if ($collectionHandler->has('users')) {
    $collectionHandler->drop('users');
}
if ($collectionHandler->has('example')) {
    $collectionHandler->drop('example');
}

// create a new collection
$userCollection = new ArangoCollection();
$userCollection->setName('users');
$id = $collectionHandler->create($userCollection);

// print the collection id created by the server
var_dump($id);
// check if the collection exists
$result = $collectionHandler->has('users');
var_dump($result);
```

## Create documents

After we created the collection, we can start with creating an initial document.
We create a user document in a collection named _users_. This collection does
not need to exist yet. The first document we insert in this collection creates
the collection on the fly. This is because we have set `OPTION_CREATE` to `true`
in `$connectionOptions`.

The below code first sets up the document locally in a variable name `$user`,
and then pushes it to the server and returns the document ID created by the
server:

```php
$handler = new ArangoDocumentHandler($connection);

// create a new document
$user = new ArangoDocument();

// use set method to set document properties
$user->set('name', 'John');
$user->set('age', 25);
$user->set('thisIsNull', null);
$user->set('obj', ['nested' => True]);

// use magic methods to set document properties
$user->likes = ['fishing', 'hiking', 'swimming'];

// send the document to the server
$id = $handler->save('users', $user);

// check if a document exists
$result = $handler->has('users', $id);
var_dump($result);

// print the document id created by the server
var_dump($id);
var_dump($user->getId());
```

Document properties can be set by using the `set()` method, or by directly
manipulating the document properties.

As you can see, sending a document to the server is achieved by calling the
`save()` method on the client library's _DocumentHandler_ class. It needs the
collection name (_users_ in this case) plus the document object to be saved.
`save()` returns the document ID as created by the server. The ID is a numeric
value that might or might not fit in a PHP integer.

## Add exception handling

The above code works but it does not check for any errors. To make it work in
the face of errors, we wrap it into some basic exception handlers:

```php
try {
    $handler = new ArangoDocumentHandler($connection);

    // create a new document
    $user = new ArangoDocument();

    // use set method to set document properties
    $user->set('name', 'John');
    $user->set('age', 25);

    // use magic methods to set document properties
    $user->likes = ['fishing', 'hiking', 'swimming'];

    // send the document to the server
    $id = $handler->save('users', $user);

    // check if a document exists
    $result = $handler->has('users', $id);
    var_dump($result);

    // print the document id created by the server
    var_dump($id);
    var_dump($user->getId());
} catch (ArangoConnectException $e) {
    print 'Connection error: ' . $e->getMessage() . PHP_EOL;
} catch (ArangoClientException $e) {
    print 'Client error: ' . $e->getMessage() . PHP_EOL;
} catch (ArangoServerException $e) {
    print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
}
```

## Retrieve documents

To retrieve a document from the server, the `get()` method of the
_DocumentHandler_ class can be used. It needs the collection name plus a
document ID. There is also the `getById()` method which is an alias for `get()`.

```php
// get the document back from the server
$userFromServer = $handler->get('users', $id);
var_dump($userFromServer);

/*
The result of the get() method is a Document object that you can use in an OO fashion:

object(ArangoDBClient\Document)##6 (4) {
    ["_id":"ArangoDBClient\Document":private]=>
    string(15) "2377907/4818344"
    ["_rev":"ArangoDBClient\Document":private]=>
    int(4818344)
    ["_values":"ArangoDBClient\Document":private]=>
    array(3) {
        ["age"]=>
        int(25)
        ["name"]=>
        string(4) "John"
        ["likes"]=>
        array(3) {
            [0]=>
            string(7) "fishing"
            [1]=>
            string(6) "hiking"
            [2]=>
            string(8) "swimming"
        }
    }
    ["_changed":"ArangoDBClient\Document":private]=>
    bool(false)
}
*/
```

Whenever the document ID is yet unknown, but you want to fetch a document from
the server by any of its other properties, you can use the
`CollectionHandler->byExample()` method. It allows you to provide an example of
the document that you are looking for. The example should either be a _Document_
object with the relevant properties set, or, a PHP array with the properties
that you are looking for:

```php
// get a document list back from the server, using a document example
$cursor = $collectionHandler->byExample('users', ['name' => 'John']);
var_dump($cursor->getAll());
```

This returns all documents from the specified collection (here: _users_) with
the properties provided in the example (here: that have an attribute `name` with
a value of `"John"`). The result is a cursor which can be iterated sequentially
or completely. We have chosen to get the complete result set above by calling
the cursor's `getAll()` method.

`CollectionHandler->byExample()` returns multiple documents if the example is
ambiguous.

## Update documents

To update an existing document, the `update()` method of the _DocumentHandler_
class can be used. In this example we want to:

- set state to `'CA'`
- change the `likes` array

```php
// update a document
$userFromServer->likes = ['fishing', 'swimming'];
$userFromServer->state = 'CA';

$result = $handler->update($userFromServer);
var_dump($result);

$userFromServer = $handler->get('users', $id);
var_dump($userFromServer);
```

To remove an attribute using the `update()` method, an option has to be passed
telling it to not keep attributes with null values. In this example we want to
remove the `age`:

```php
// update a document removing an attribute,
// The 'keepNull'=>false option will cause ArangoDB to
// remove all attributes in the document,
// that have null as their value - not only the ones defined here

$userFromServer->likes = ['fishing', 'swimming'];
$userFromServer->state = 'CA';
$userFromServer->age   = null;

$result = $handler->update($userFromServer, ['keepNull' => false]);
var_dump($result);

$userFromServer = $handler->get('users', $id);
var_dump($userFromServer);
```

The document that is updated using the previous example must have been fetched
from the server before. If you want to update a document without having fetched
it from the server before, use `updateById()`:

```php
// update a document, identified by collection and document id
$user        = new ArangoDocument();
$user->name  = 'John';
$user->likes = ['Running', 'Rowing'];

// Notice that for the example we're getting the existing 
// document id via a method call. Normally we would use the known id
$result = $handler->updateById('users', $userFromServer->getId(), $user);
var_dump($result);

$userFromServer = $handler->get('users', $id);
var_dump($userFromServer);
```

## Replace documents

To completely replace an existing document, the `replace()` method of the
_DocumentHandler_ class can be used. In this example we want to remove the
`state` attribute:

```php
// replace a document (notice that we are using the previously fetched document)
// In this example we are removing the state attribute
unset($userFromServer->state);

$result = $handler->replace($userFromServer);
var_dump($result);

$userFromServer = $handler->get('users', $id);
var_dump($userFromServer);
```

The document that is replaced using the previous example must have been fetched
from the server before. If you want to replace a document without having fetched
it from the server before, use `replaceById()`:

```php
// replace a document, identified by collection and document id
$user        = new ArangoDocument();
$user->name  = 'John';
$user->likes = ['Running', 'Rowing'];

// Notice that for the example we're getting the existing 
// document id via a method call. Normally we would use the known id
$result = $handler->replaceById('users', $userFromServer->getId(), $user);
var_dump($result);

$userFromServer = $handler->get('users', $id);
var_dump($userFromServer);
```

## Delete documents

To remove an existing document on the server, the `remove()` method of the
_DocumentHandler_ class can be used. `remove()` just needs the document to be
removed as a parameter:

```php
// remove a document on the server, using a document object
$result = $handler->remove($userFromServer);
var_dump($result);
```

Note that the document must have been fetched from the server before. If you
haven't fetched the document from the server before, use the `removeById()`
method. This requires just the collection name (here: _users_) and the
document ID.

```php
// remove a document on the server, using a collection id and document id
// In this example, we are using the id of the document we deleted in the previous example,
// so it will throw an exception here. (we are catching it though, in order to continue)

try {
    $result = $handler->removeById('users', $userFromServer->getId());
} catch (\ArangoDBClient\ServerException $e) {
    $e->getMessage();
}
```

## Drop collections

To drop an existing collection on the server, use the `drop()` method of the
_CollectionHandler_ class. `drop()` just needs the name of the collection name
to be dropped:

```php
// drop a collection on the server, using its name,
$result = $collectionHandler->drop('users');
var_dump($result);

// drop the other one we created, too
$collectionHandler->drop('example');
```

## Run AQL queries

To run an AQL query, use the _Statement_ class.

The method `Statement::execute` creates a Cursor object which can be used to
iterate over the query's result set.

```php
// create a statement to insert 1000 test users
$statement = new ArangoStatement(
    $connection, [
        'query' => 'FOR i IN 1..1000 INSERT { _key: CONCAT("test", i) } IN users'
    ]
);

// execute the statement
$cursor = $statement->execute();

// now run another query on the data, using bind parameters
$statement = new ArangoStatement(
    $connection, [
        'query' => 'FOR u IN @@collection FILTER u.name == @name RETURN u',
        'bindVars' => [
            '@collection' => 'users',
            'name' => 'John'
        ]
    ]
);

// executing the statement returns a cursor
$cursor = $statement->execute();

// easiest way to get all results returned by the cursor
var_dump($cursor->getAll());

// to get statistics for the query, use Cursor::getExtra();
var_dump($cursor->getExtra());
```

Note: by default the Statement object will create a Cursor that converts each
value into a Document object. This is normally the intended behavior for AQL
queries that return entire documents. However, an AQL query can also return
projections or any other data that cannot be converted into Document objects.

In order to suppress the conversion into Document objects, the Statement must be
given the `_flat` attribute. This allows processing the results of arbitrary AQL
queries:

```php
// run an AQL query that does not return documents but scalars
// we need to set the _flat attribute of the Statement in order for this to work
$statement = new ArangoStatement(
    $connection, [
        'query' => 'FOR i IN 1..1000 RETURN i',
        '_flat' => true
    ]
);

// executing the statement returns a cursor
$cursor = $statement->execute();

// easiest way to get all results returned by the cursor
// note that now the results won't be converted into Document objects
var_dump($cursor->getAll());
```

## Bulk document handling

The ArangoDB-PHP driver provides a mechanism to easily fetch multiple documents
from the same collection with a single request. All that needs to be provided is
an array of document keys:

```php
$exampleCollection = new ArangoCollection();
$exampleCollection->setName('example');
$id = $collectionHandler->create($exampleCollection);

// create a statement to insert 100 example documents
$statement = new ArangoStatement(
    $connection, [
        'query' => 'FOR i IN 1..100 INSERT { _key: CONCAT("example", i), value: i } IN example'
    ]
);
$statement->execute();

// later on, we can assemble a list of document keys
$keys = [];
for ($i = 1; $i <= 100; ++$i) {
    $keys[] = 'example' . $i;
}
// and fetch all the documents at once
$documents = $collectionHandler->lookupByKeys('example', $keys);
var_dump($documents);

// we can also bulk-remove them:
$result = $collectionHandler->removeByKeys('example', $keys);

var_dump($result);
```

## Custom Document class

If you want to use custom document class you can pass its name to
_DocumentHandler_ or _CollectionHandler_ using method `setDocumentClass`.
Remember that Your class must extend `\ArangoDBClient\Document`.

```php
$ch = new CollectionHandler($connection);
$ch->setDocumentClass('\AppBundle\Entity\Product');
$cursor = $ch->all('product'); 
// All returned documents will be \AppBundle\Entity\Product instances

$dh = new DocumentHandler($connection);
$dh->setDocumentClass('\AppBundle\Entity\Product');
$product = $dh->get('products', 11231234);
// Product will be \AppBundle\Entity\Product instance
```

See the [`examples/customDocumentClass.php`](examples/customDocumentClass.php)
file for more details.

## Log exceptions

The driver provides a simple logging mechanism that is turned off by default.
If it is turned on, the driver logs all its exceptions using PHP's standard
`error_log` mechanism. It calls PHP's `error_log()` function for this. It
depends on the PHP configuration if and where exceptions are logged. Please
consult your `php.ini` settings for further details.

To turn on exception logging in the driver, set a flag on the driver's
_Exception_ base class, from which all driver exceptions are subclassed:

```php
use ArangoDBClient\Exception as ArangoException;

ArangoException::enableLogging();
```

To turn logging off, call its `disableLogging` method:

```php
use ArangoDBClient\Exception as ArangoException;

ArangoException::disableLogging();
```

## Put it all together

Here is the full code that combines all the pieces outlined above:

```php
// use the following line when using Composer
// require __DIR__ . '/vendor/composer/autoload.php';

// use the following line when using git
require __DIR__ . '/autoload.php';

// set up some aliases for less typing later
use ArangoDBClient\Collection as ArangoCollection;
use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use ArangoDBClient\Document as ArangoDocument;
use ArangoDBClient\Exception as ArangoException;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use ArangoDBClient\Statement as ArangoStatement;
use ArangoDBClient\UpdatePolicy as ArangoUpdatePolicy;

// set up some basic connection options
$connectionOptions = [
    // database name
    ArangoConnectionOptions::OPTION_DATABASE => '_system',
    // server endpoint to connect to
    ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
    // authorization type to use (currently supported: 'Basic')
    ArangoConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
    // user for basic authorization
    ArangoConnectionOptions::OPTION_AUTH_USER => 'root',
    // password for basic authorization
    ArangoConnectionOptions::OPTION_AUTH_PASSWD => '',
    // connection persistence on server. can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
    ArangoConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
    // connect timeout in seconds
    ArangoConnectionOptions::OPTION_TIMEOUT => 3,
    // whether or not to reconnect when a keep-alive connection has timed out on server
    ArangoConnectionOptions::OPTION_RECONNECT => true,
    // optionally create new collections when inserting documents
    ArangoConnectionOptions::OPTION_CREATE => true,
    // optionally create new collections when inserting documents
    ArangoConnectionOptions::OPTION_UPDATE_POLICY => ArangoUpdatePolicy::LAST,
];


// turn on exception logging (logs to whatever PHP is configured)
ArangoException::enableLogging();

try {
    $connection = new ArangoConnection($connectionOptions);

    $collectionHandler = new ArangoCollectionHandler($connection);

    // clean up first
    if ($collectionHandler->has('users')) {
        $collectionHandler->drop('users');
    }
    if ($collectionHandler->has('example')) {
        $collectionHandler->drop('example');
    }

    // create a new collection
    $userCollection = new ArangoCollection();
    $userCollection->setName('users');
    $id = $collectionHandler->create($userCollection);

    // print the collection id created by the server
    var_dump($id);

    // check if the collection exists
    $result = $collectionHandler->has('users');
    var_dump($result);

    $handler = new ArangoDocumentHandler($connection);

    // create a new document
    $user = new ArangoDocument();

    // use set method to set document properties
    $user->set('name', 'John');
    $user->set('age', 25);
    $user->set('thisIsNull', null);

    // use magic methods to set document properties
    $user->likes = ['fishing', 'hiking', 'swimming'];

    // send the document to the server
    $id = $handler->save('users', $user);

    // check if a document exists
    $result = $handler->has('users', $id);
    var_dump($result);

    // print the document id created by the server
    var_dump($id);
    var_dump($user->getId());


    // get the document back from the server
    $userFromServer = $handler->get('users', $id);
    var_dump($userFromServer);

    // get a document list back from the server, using a document example
    $cursor = $collectionHandler->byExample('users', ['name' => 'John']);
    var_dump($cursor->getAll());


    // update a document
    $userFromServer->likes = ['fishing', 'swimming'];
    $userFromServer->state = 'CA';

    $result = $handler->update($userFromServer);
    var_dump($result);

    $userFromServer = $handler->get('users', $id);
    var_dump($userFromServer);


    // update a document removing an attribute,
    // The 'keepNull'=>false option will cause ArangoDB to
    // remove all attributes in the document,
    // that have null as their value - not only the ones defined here

    $userFromServer->likes = ['fishing', 'swimming'];
    $userFromServer->state = 'CA';
    $userFromServer->age   = null;

    $result = $handler->update($userFromServer, ['keepNull' => false]);
    var_dump($result);

    $userFromServer = $handler->get('users', $id);
    var_dump($userFromServer);


    // replace a document (notice that we are using the previously fetched document)
    // In this example we are removing the state attribute
    unset($userFromServer->state);

    $result = $handler->replace($userFromServer);
    var_dump($result);

    $userFromServer = $handler->get('users', $id);
    var_dump($userFromServer);


    // replace a document, identified by collection and document id
    $user                  = new ArangoDocument();
    $user->name            = 'John';
    $user->likes           = ['Running', 'Rowing'];
    $userFromServer->state = 'CA';

    // Notice that for the example we're getting the existing
    // document id via a method call. Normally we would use the known id
    $result = $handler->replaceById('users', $userFromServer->getId(), $user);
    var_dump($result);

    $userFromServer = $handler->get('users', $id);
    var_dump($userFromServer);


    // remove a document on the server
    $result = $handler->remove($userFromServer);
    var_dump($result);


    // remove a document on the server, using a collection id and document id
    // In this example, we are using the id of the document we deleted in the previous example,
    // so it will throw an exception here. (we are catching it though, in order to continue)

    try {
        $result = $handler->removeById('users', $userFromServer->getId());
    } catch (\ArangoDBClient\ServerException $e) {
        $e->getMessage();
    }



    // create a statement to insert 1000 test users
    $statement = new ArangoStatement(
        $connection, [
            'query' => 'FOR i IN 1..1000 INSERT { _key: CONCAT("test", i) } IN users'
        ]
    );

    // execute the statement
    $cursor = $statement->execute();


    // now run another query on the data, using bind parameters
    $statement = new ArangoStatement(
        $connection, [
            'query' => 'FOR u IN @@collection FILTER u.name == @name RETURN u',
            'bindVars' => [
                '@collection' => 'users',
                'name' => 'John'
            ]
        ]
    );

    // executing the statement returns a cursor
    $cursor = $statement->execute();

    // easiest way to get all results returned by the cursor
    var_dump($cursor->getAll());

    // to get statistics for the query, use Cursor::getExtra();
    var_dump($cursor->getExtra());


    $exampleCollection = new ArangoCollection();
    $exampleCollection->setName('example');
    $id = $collectionHandler->create($exampleCollection);

    // create a statement to insert 100 example documents
    $statement = new ArangoStatement(
        $connection, [
            'query' => 'FOR i IN 1..100 INSERT { _key: CONCAT("example", i), value: i } IN example'
        ]
    );
    $statement->execute();

    // later on, we can assemble a list of document keys
    $keys = [];
    for ($i = 1; $i <= 100; ++$i) {
        $keys[] = 'example' . $i;
    }
    // and fetch all the documents at once
    $documents = $collectionHandler->lookupByKeys('example', $keys);
    var_dump($documents);

    // we can also bulk-remove them:
    $result = $collectionHandler->removeByKeys('example', $keys);

    var_dump($result);


    // drop a collection on the server, using its name,
    $result = $collectionHandler->drop('users');
    var_dump($result);

    // drop the other one we created, too
    $collectionHandler->drop('example');
} catch (ArangoConnectException $e) {
    print 'Connection error: ' . $e->getMessage() . PHP_EOL;
} catch (ArangoClientException $e) {
    print 'Client error: ' . $e->getMessage() . PHP_EOL;
} catch (ArangoServerException $e) {
    print 'Server error: ' . $e->getServerCode() . ': ' . $e->getServerMessage() . ' - ' . $e->getMessage() . PHP_EOL;
}
```

## More information

- More example code, containing some code to create, delete and rename
  collections, is provided in the [`examples`](examples) subdirectory that is
  provided with the library.

- [PHPDoc documentation](http://arangodb.github.io/arangodb-php/)
  for the complete library
