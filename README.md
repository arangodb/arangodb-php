![ArangoDB-PHP Logo](http://www.arangodb.org/wp-content/uploads/2013/03/logo_arangodbphp_trans.png)

# ArangoDB-PHP - A PHP client for ArangoDB

[![Build Status](https://travis-ci.org/triAGENS/ArangoDB-PHP.png?branch=master)](https://travis-ci.org/triAGENS/ArangoDB-PHP)
**Branch: Master (v2.0.0)**

[![Build Status](https://travis-ci.org/triAGENS/ArangoDB-PHP.png?branch=devel)](https://travis-ci.org/triAGENS/ArangoDB-PHP)
**Branch: devel**



[Follow us on Twitter @arangodbphp to receive updates on the php driver](https://twitter.com/arangodbphp)
<br>
<br>
##### Table of Contents

- [Description](#description)
- [Requirements](#requirements)
- [Installing the PHP client](#installing)
 - [Using packagist/composer](#using_packagist)
 - [Cloning the git repository](#cloning_git)
- [How to use the PHP client](#howto_use)
 - [Setting up the connection options](#setting_up_connection_options)
 - [Creating a collection](#creating_collection)
 - [Creating a document](#creating_document)
 - [Adding exception handling](#adding_exception_handling)
 - [Retrieving a document](#retrieving_document)
 - [Updating a document](#updating_document)
 - [Deleting a document](#deleting_document)
 - [Dropping a collection](#dropping_collection)
 - [Putting it all together](#alltogether)

- [More information](#more_info)

<br>

Please take a look [here](https://github.com/triAGENS/ArangoDB-PHP/wiki/Important-versioning-information-on-ArangoDB-PHP#arangodb-php-client-to-arangodb-server-interoperability-matrix) for the **ArangoDB-PHP Client** / **ArangoDB Server** interoperability matrix.

**[Important versioning information on ArangoDB-PHP](https://github.com/triAGENS/ArangoDB-PHP/wiki/Important-versioning-information-on-ArangoDB-PHP)**

<br>
<a name="description"/a>
# Description

This PHP client allows REST-based access to documents on the server.
The ArangoDocumentHandler class should be used for these purposes.
There is an example for REST-based documents access in the file examples/document.php.

Furthermore, the PHP client also allows to issue more complex queries using the ArangoStatement class.
There is an example for this kind of statements in the file examples/select.php.

To use the PHP client, you must include the file autoloader.php from the main directory.
The autoloader will care about loading additionally required classes on the fly. The autoloader can be nested with other autoloaders.

The ArangoDB PHP client is an API that allows you to send and retrieve documents from ArangoDB from out of your PHP application. The client library itself is written in PHP and has no further dependencies but just plain PHP 5.3 (or higher).

The client library provides document and collection classes you can use to work with documents and collections in an OO fashion. When exchanging document data with the server, the library internally will use the [HTTP REST interface of ArangoDB](https://github.com/triAGENS/ArangoDB/wiki/OTWP). The library user does not have to care about this fact as all the details of the REST interface are abstracted by the client library.

<br>



<a name="requirements"/a>
# Requirements

* ArangoDB database server version 1.4 or higher (detailed info [here](https://github.com/triAGENS/ArangoDB-PHP/wiki/Important-versioning-information-on-ArangoDB-PHP#arangodb-php-client-to-arangodb-server-interoperability-matrix))

* PHP version 5.3 or higher (Travis-tested with 5.4, 5.5, 5.6 and hhvm)

<br>



<a name="installing"/a>
## Installing the PHP client

To get started you need PHP 5.3 or higher plus an ArangoDB server running on any host that you can access.

There are two alternative ways to get the ArangoDB PHP client:

 * Using packagist/composer
 * Cloning the git repository

<a name="using_packagist"/a>
## Alternative 1: Using packagist/composer

When using [packagist](http://packagist.org/), the procedure is as follows:

Get the composer.phar file from [getcomposer.org](http://getcomposer.org):

    curl -s http://getcomposer.org/installer | php

This will put the composer.phar file into the current directory. Next, create a new directory for your project, e.g. arangophp, and move into it:

    mkdir arangophp && cd arangophp

Then, use composer's init command to define the initial dependencies for your project:

    php ../composer.phar init

This will fire up composer's interactive config generator. It will ask you several questions, and the below example shows how you can answer them. Most questions have reasonable default settings and you can should use the defaults whenever you're unsure.
When asked for a package name, type ## triagens/Arango. This is the package name for the ArangoDB PHP client. When being asked for a package number (package ##), you can either use dev-master (latest version) or one of the designated tagged versions.

    Welcome to the Composer config generator

This command will guide you through creating your composer.json config.

    Package name (/) [jsteemann/arangophp]:
    Description []: An example application using ArangoDB PHP client
    Author [jsteemann]:

    Define your dependencies.

    Would you like to define your dependencies interactively [yes]? yes
    Search for a package []: triagens/Arango

    Found 3 packages matching triagens/Arango

    [0] triagens/ArangoDb dev-master
    [1] triagens/ArangoDb V0.1.1
    [2] triagens/ArangoDb V0.0.1

    Enter package ## to add, or a couple if it is not listed []: 0
    Search for a package []:

    {
        "name": "jsteemann/arangophp",
        "description": "An example application using ArangoDB PHP client",
        "require": {
            "triagens/arangodb": "dev-master"
        },
        "authors": [
        {
            "name": "jsteemann",
            "email": "j.steemann@triagens.de"
        }
        ]
    }

    Do you confirm generation [yes]? yes
    Would you like the vendor directory added to your .gitignore [yes]?

The above has created a file composer.json in your current directory, which contains information about your project plus the project dependencies. The ArangoDB PHP client is the only dependency for now, and it can be installed by running the following command:

    php ../composer.phar update
    Updating dependencies
    - Package triagens/arangodb (dev-master)
    Cloning e4e9107aec3d1e0c914e40436f77fed0e5df2485

    Writing lock file
    Generating autoload files


Running this command has created a subdirectory vendor in the current directory. The vendor directory contains a subdirectory triagens that contains the ArangoDB PHP client library files. The vendor directory also contains a subdirectory .composer that contains auto-generated autoloader files for all dependencies you defined in your composer.json file (the file auto-generated by running the previous init command).

You need to include the generated autoloader file in your project when using the ArangoDB PHP classes. You can do so by adding the following line to any PHP file that will use them:

```php
require 'vendor/.composer/autoload.php';
```

<a name="cloning_git"/a>
## Alternative 2: Cloning the git repository

When preferring this alternative, you need to have a git client installed. To clone the ArangoDB PHP client repository from github, execute the following command in your project directory:

    git clone "https://github.com/triAGENS/ArangoDB-PHP.git"


This will create a subdirectory ArangoDB-PHP in your current directory. It contains all the files of the client library. It also includes a dedicated autoloader that you can use for autoloading the client libraries class files.
To invoke this autoloader, add the following line to your PHP files that will use the library:

```php
require 'ArangoDB-PHP/autoload.php';
```


The ArangoDB PHP client's autoloader will only care about its own class files and will not handle any other files. That means it is fully nestable with other autoloaders.

<a name="invoke_autoloader_directly"/a>
## Alternative 3: Invoking the autoloader directly

If you do not wish to include autoload.php to load and setup the autoloader, you can invoke the autoloader directly:

```php
require 'ArangoDB-PHP/lib/triagens/ArangoDb/autoloader.php';
\triagens\ArangoDb\Autoloader::init();
```

<br>

<a name="howto_use"/a>
# How to use the PHP client

<a name="setting_up_connection_options"/a>
## Setting up the connection options

In order to use ArangoDB, you need to specify the connection options. We do so by creating a PHP array $connectionOptions. Put this code into a file named test.php in your current directory:

```php
// use the following line when using packagist/composer
// require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . '.composer' . DIRECTORY_SEPARATOR . 'autoload.php';

// use the following line when using git
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ArangoDB-PHP' . DIRECTORY_SEPARATOR . 'autoload.php';

// set up some aliases for less typing later
use triagens\ArangoDb\Connection as ArangoConnection;
use triagens\ArangoDb\ConnectionOptions as ArangoConnectionOptions;
use triagens\ArangoDb\DocumentHandler as ArangoDocumentHandler;
use triagens\ArangoDb\Document as ArangoDocument;
use triagens\ArangoDb\Exception as ArangoException;
use triagens\ArangoDb\ConnectException as ArangoConnectException;
use triagens\ArangoDb\ClientException as ArangoClientException;
use triagens\ArangoDb\ServerException as ArangoServerException;
use triagens\ArangoDb\UpdatePolicy as ArangoUpdatePolicy;

// set up some basic connection options
$connectionOptions = array(
    // server endpoint to connect to
    ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
    // authorization type to use (currently supported: 'Basic')
    ArangoConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
    // user for basic authorization
    ArangoConnectionOptions::OPTION_AUTH_USER => 'root',
    // password for basic authorization
    ArangoConnectionOptions::OPTION_AUTH_PASSWD => '',
    // connection persistence on server. can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
    ArangoConnectionOptions::OPTION_CONNECTION => 'Close',
    // connect timeout in seconds
    ArangoConnectionOptions::OPTION_TIMEOUT => 3,
    // whether or not to reconnect when a keep-alive connection has timed out on server
    ArangoConnectionOptions::OPTION_RECONNECT => true,
    // optionally create new collections when inserting documents
    ArangoConnectionOptions::OPTION_CREATE => true,
    // optionally create new collections when inserting documents
    ArangoConnectionOptions::OPTION_UPDATE_POLICY => ArangoUpdatePolicy::LAST,
);

$connection = new ArangoConnection($connectionOptions);
```

This will make the client connect to ArangoDB

* running on localhost (OPTION_HOST)
* on the default port 8529 (OPTION_PORT)
* with a connection timeout of 3 seconds (OPTION_TIMEOUT)

When creating new documents in a collection that does not yet exist, you have the following choices:

* auto-generate a new collection: if you prefer that, set OPTION_CREATE to true
* fail with an error: if you prefer this behavior, set OPTION_CREATE to false

When updating a document that was previously/concurrently updated by another user, you can select between the following behaviors:

* last update wins: if you prefer this, set OPTION_UPDATE_POLICY to last
* fail with a conflict error: if you prefer that, set OPTION_UPDATE_POLICY to conflict


<a name="creating_collection"/a>
## Creating a collection
*This is just to show how a collection is created.*
<br>
*For these examples it is not needed to create a collection prior to inserting a document, as we set ArangoConnectionOptions::OPTION_CREATE to true.*

So, after we get the settings, we can start with creating a collection. We will create a collection named "users".

The below code will first set up the collection locally in a variable name $user, and then push it to the server and return the collection id created by the server:

```php
$collectionHandler = new CollectionHandler($connection);

// create a new document
$userCollection = new ArangoCollection();
$userCollection->setName('user');
$id = $collectionHandler->add($userCollection);

// print the collection id created by the server
var_dump($id);
```

<a name="creating_document"/a>
## Creating a document

After we created the collection, we can start with creating an initial document. We will create a user document in a collection named "users". This collection does not need to exist yet. The first document we'll insert in this collection will create the collection on the fly. This is because we have set OPTION_CREATE to true in $connectionOptions.

The below code will first set up the document locally in a variable name $user, and then push it to the server and return the document id created by the server:

```php
$handler = new ArangoDocumentHandler($connection);

// create a new document
$user = new ArangoDocument();

// use set method to set document properties
$user->set("name", "John");
$user->set("age", 25);

// use magic methods to set document properties
$user->likes = array('fishing', 'hiking', 'swimming');

// send the document to the server
$id = $handler->add('users', $user);

// print the document id created by the server
var_dump($id);
```

Document properties can be set by using the set() method, or by directly manipulating the document properties.

As you can see, sending a document to the server is achieved by calling the add() method on the client library's DocumentHandler class. It needs the collection name ("users" in this case") plus the document object to be added. add() will return the document id as created by the server. The id is a numeric value that might or might not fit in a PHP integer.

<a name="adding_exception_handling"/a>
## Adding exception handling


The above code will work but it does not check for any errors. To make it work in the face of errors, we'll wrap it into some basic exception handlers

```php
try {
    $connection = new ArangoConnection($connectionOptions);
    $handler = new ArangoDocumentHandler($connection);

    // create a new document
    $user = new ArangoDocument();

    // use set method to set document properties
    $user->set("name", "John");
    $user->set("age", 25);

    // use magic methods to set document properties
    $user->likes = array('fishing', 'hiking', 'swimming');

    // send the document to the server
    $id = $handler->add('users', $user);

    // print the document id created by the server
    var_dump($id);
} catch (ArangoConnectException $e) {
  print 'Connection error: ' . $e->getMessage() . PHP_EOL;
} catch (ArangoClientException $e) {
  print 'Client error: ' . $e->getMessage() . PHP_EOL;
} catch (ArangoServerException $e) {
  print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
}
```

<a name="retrieving_document"/a>
## Retrieving a document

To retrieve a document from the server, the get() method of the DocumentHandler class can be used. It needs the collection name plus a document id. There is also the getById() method which is an alias for get().

```php
// get the document back from the server
$userFromServer = $handler->get('users', $id);
var_dump($userFromServer);

/*
The result of the get() method is a Document object that you can use in an OO fashion:

object(triagens\ArangoDb\Document)##6 (4) {
    ["_id":"triagens\ArangoDb\Document":private]=>
    string(15) "2377907/4818344"
    ["_rev":"triagens\ArangoDb\Document":private]=>
    int(4818344)
    ["_values":"triagens\ArangoDb\Document":private]=>
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
    ["_changed":"triagens\ArangoDb\Document":private]=>
    bool(false)
}
*/
```

Whenever the document id is yet unknown, but you want to fetch a document from the server by any of its other properties, you can use the getByExample() method. It allows you to provide an example of the document that you are looking for. The example should either be a Document object with the relevant properties set, or, a PHP array with the propeties that you are looking for:

```php
$cursor = $handler->getByExample('users', array('name'=>'John'));
var_dump($cursor->getAll());

$user = new Document();
$user->name = 'John';
$cursor = $handler->getByExample('users', $user);
var_dump($cursor->getAll());
```

This will return all documents from the specified collection (here: "users") with the properties provided in the example (here: that have an attribute "name" with a value of "John"). The result is a cursor which can be iterated sequentially or completely. We have chosen to get the complete result set above by calling the cursor's getAll() method.
Note that getByExample() might return multiple documents if the example is ambigious.

<a name="updating_document"/a>
## Updating a document


To update an existing document, the update() method of the DocumentHandler class can be used.

```php
// update a document
$userFromServer->likes = array('fishing', 'swimming');
$userFromServer->state = 'CA';
unset($userFromServer->age);

$result = $handler->update($userFromServer);
var_dump($result);
```

The document that is updated using update() must have been fetched from the server before. If you want to update a document without having fetched it from the server before, use updateById():

```php
// update a document, identified by collection and document id
$user = new Document();
$user->name = 'John';
$user->likes = array('Running', 'Rowing');

// 4818344 is the document's id
$result = $handler->updateById('users', 4818344, $user);
var_dump($result);
```

<a name="deleting_document"/a>
## Deleting a document

To delete an existing document on the server, the delete() method of the DocumentHandler class will do. delete() just needs the document to be deleted:

```php
// delete a document on the server, using a document object
$result = $handler->delete($userFromServer);
var_dump($result);
```

Note that the document must have been fetched from the server before. If you haven't fetched the document from the server before, use the deleteById() method. This requires just the collection name (here: "users") and the document id.

```php
// delete a document on the server, using a collection id and document id
// 4818344 is the document's id
$result = $handler->deleteById('users', 4818344);
var_dump($result);
```


<a name="dropping_collection"/a>
## Dropping a collection


To delete an existing collection on the server, use the drop() method of the CollectionHandler class. drop() just needs the name of the collection name to be dropped:

```php
// delete a collection on the server, using it's name,
$result = $handler->drop('users');
var_dump($result);
```

<a name="alltogether"/a>
## Putting it all together

Here's the full code that combines all the pieces outlined above:

```php
// use the following line when using packagist/composer
//require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . '.composer' . DIRECTORY_SEPARATOR . 'autoload.php';
// use the following line when using git
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ArangoDB-PHP' . DIRECTORY_SEPARATOR . 'autoload.php';

// set up some aliases for less typing later
use triagens\ArangoDb\Connection as ArangoConnection;
use triagens\ArangoDb\ConnectionOptions as ArangoConnectionOptions;
use triagens\ArangoDb\DocumentHandler as ArangoDocumentHandler;
use triagens\ArangoDb\Document as ArangoDocument;
use triagens\ArangoDb\Exception as ArangoException;
use triagens\ArangoDb\ConnectException as ArangoConnectException;
use triagens\ArangoDb\ClientException as ArangoClientException;
use triagens\ArangoDb\ServerException as ArangoServerException;
use triagens\ArangoDb\UpdatePolicy as ArangoUpdatePolicy;

// set up some basic connection options
$connectionOptions = array(
    ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
    // authorization type to use (currently supported: 'Basic')
    ArangoConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
    // user for basic authorization
    ArangoConnectionOptions::OPTION_AUTH_USER => 'root',
    // password for basic authorization
    ArangoConnectionOptions::OPTION_AUTH_PASSWD => '',
    // connection persistence on server. can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
    ArangoConnectionOptions::OPTION_CONNECTION => 'Close',
    // connect timeout in seconds
    ArangoConnectionOptions::OPTION_TIMEOUT => 3,
    // optionally create new collections when inserting documents
    ArangoConnectionOptions::OPTION_CREATE => true,
    // optionally create new collections when inserting documents
    ArangoConnectionOptions::OPTION_UPDATE_POLICY => ArangoUpdatePolicy::LAST,
);

try {
    $connection = new ArangoConnection($connectionOptions);


    $collectionHandler = new CollectionHandler($connection);

    // create a new document
    $userCollection = new ArangoCollection();
	$userCollection->setName('user');
	$id = $collectionHandler->add($userCollection);

    // print the collection id created by the server
    var_dump($id);


    $handler = new ArangoDocumentHandler($connection);

    // create a new document
    $user = new ArangoDocument();

    // use set method to set document properties
    $user->set("name", "John");
    $user->set("age", 25);

    // use magic methods to set document properties
    $user->likes = array('fishing', 'hiking', 'swimming');

    // send the document to the server
    $id = $handler->add('users', $user);

    // print the document id created by the server
    var_dump($id);
    var_dump($user->getId());


    // get the document back from the server
    $userFromServer = $handler->get('users', $id);
    var_dump($userFromServer);

    // get a document list back from the server, using a document example
    $cursor = $handler->getByExample('users', array('name'=>'John'));
    var_dump($cursor->getAll());


    // update a document
    $userFromServer->likes = array('fishing', 'swimming');
    $userFromServer->state = 'CA';
    unset($userFromServer->age);

    $result = $handler->update($userFromServer);
    var_dump($result);

    // get the document back from the server
    $userFromServer = $handler->get('users', $id);
    var_dump($userFromServer);


    // delete a document on the server
    $result = $handler->delete($userFromServer);
    var_dump($result);


    // delete a collection on the server, using it's name,
    $result = $handler->drop('users');
    var_dump($result);
}
catch (ArangoConnectException $e) {
    print 'Connection error: ' . $e->getMessage() . PHP_EOL;
}
catch (ArangoClientException $e) {
    print 'Client error: ' . $e->getMessage() . PHP_EOL;
}
catch (ArangoServerException $e) {
    print 'Server error: ' . $e->getServerCode() . ':' . $e->getServerMessage() . ' ' . $e->getMessage() . PHP_EOL;
}
```

<br>



<a name="more_info"/a>
# More information

* More example code, containing some code to create, delete and rename collections, is provided in the example subdirectory that is provided with the library.

* PHPDoc documentation for the complete library is in the library's docs subdirectory. Point your browser at this directory to get a click-through version of the documentation.

* [Follow us on Twitter @arangodbphp to receive updates on the php driver](https://twitter.com/arangodbphp)

* Check the ArangoDB PHP client on github.com regularly for new releases and updates: [https://github.com/triAGENS/ArangoDB-PHP](https://github.com/triAGENS/ArangoDB-PHP)
