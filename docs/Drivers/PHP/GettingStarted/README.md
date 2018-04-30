# ArangoDB-PHP - Getting Started
## Description

This PHP client allows REST-based access to documents on the server.
The *DocumentHandler* class should be used for these purposes.
There is an example for REST-based documents access in the file examples/document.php.

Furthermore, the PHP client also allows to issue more AQL complex queries using the *Statement* class.
There is an example for this kind of statements in the file examples/select.php.

To use the PHP client, you must include the file autoloader.php from the main directory.
The autoloader will care about loading additionally required classes on the fly. The autoloader can be nested with other autoloaders.

The ArangoDB PHP client is an API that allows you to send and retrieve documents from ArangoDB from out of your PHP application. The client library itself is written in PHP and has no further dependencies but just plain PHP 5.6 (or higher).

The client library provides document and collection classes you can use to work with documents and collections in an OO fashion. When exchanging document data with the server, the library internally will use the [HTTP REST interface of ArangoDB](../../../HTTP/index.html). The library user does not have to care about this fact as all the details of the REST interface are abstracted by the client library.

## Requirements

* PHP version 5.6 or higher (Travis-tested with PHP 5.6, 7.0, 7.1 and hhvm)

Note on PHP version support: 

This driver will cease to support old PHP versions as soon as they have reached end-of-life status. Support will be removed with the next minor or patch version of the driver to be released. 

In general, it is recommended to always use the latest PHP versions (currently those in the PHP 7 line) in order to take advantage of all the improvements (especially in performance).

### Important version information on ArangoDB-PHP

Since version 1.0 of this client, it will closely follow the ArangoDB versioning.
That means that:

- ArangoDB-PHP 3.1.x is on par with the functionality of ArangoDB 3.0.y
- ArangoDB-PHP 3.2.x is on par with the functionality of ArangoDB 3.1.y
etc...

Notice: The third level number of the version is not associated to ArangoDB's third level number, as it states minor updates, bugfixes and patches to the client itself.


<br>
<br>

<a name="interoperability_matrix"></a>

### ArangoDB-PHP Client to ArangoDB Server interoperability matrix ##
#### Current Versions (3.x)

<table>
  <tr>
    <th width="25%">ArangoDB-PHP&nbsp;Version</th><th width="25%">ArangoDB&nbsp;Version</th><th width="50%">Comments</th>
  </tr>
  <tr>
    <td>3.0.x</td><td>3.0.x through 3.0.x</td><td>This version is not backwards compatible due to changes in ArangoDB's API</td>
  </tr>
  <tr>
    <td>3.1.x</td><td>3.1.x through 3.1.x</td><td>This version is not backwards compatible due to changes in ArangoDB's API</td>
  </tr>
  <tr>
    <td>3.2.x</td><td>3.2.x through 3.2.x</td><td>This version is not backwards compatible due to changes in ArangoDB's API</td>
  </tr>
  <tr>
    <td>3.3.x</td><td>3.3.x through 3.3.x</td><td>This version is not backwards compatible due to changes in ArangoDB's API</td>
  </tr>
</table>  
<br>

#### Older Versions

<table>
  <tr>
    <th width="25%">ArangoDB-PHP&nbsp;Version</th><th width="25%">ArangoDB&nbsp;Version</th><th width="100%">Comments</th>
  </tr>
  <tr>
    <td>1.0.0</td><td>1.0.0 through 1.0.4</td><td></td>
  </tr>
  <tr>
    <td>1.0.1</td><td>1.0.0 through 1.0.4</td><td></td>
</tr>
  <tr>
    <td>1.1.0</td><td>1.1.0 through 1.1.3</td><td></td>
  </tr>
  <tr>
    <td>1.2.0</td><td>1.2.0 through 1.2.1</td><td></td>
  </tr>
  <tr>
    <td>1.2.1</td><td>1.2.2 through 1.2.3</td><td>This client version provides support for ArangoDB's autoincrement functionality. (ArangoDB Version 1.2.2+)</td>
  </tr>
  <tr>
    <td>1.3.0</td><td>1.3.0 through 1.3.3</td><td>Provides support for Transactions, Auto-Increment, AQL User Functions and the new statistics interface.</td>
  </tr>
  <tr>
    <td>1.3.1</td><td>1.3.0 through 1.3.3</td><td>Added Tracer & simple/all API equvalent. Some Performance fixes. improved docs</td>
  </tr>
  <tr>
    <td>1.4.0</td><td>1.4.0 through 1.4.x</td><td>Multi-Database, Traversal API + others</td>
  </tr>
  <tr>
    <td>2.x.x</td><td>2.0.0 through 2.x.x</td><td></td>
  </tr>
</table>

### Installing the PHP client

To get started you need PHP 5.6 or higher plus an ArangoDB server running on any host that you can access.

There are two alternative ways to get the ArangoDB PHP client:

 * Using Composer
 * Cloning the git repository

#### Alternative 1: Using Composer

```
composer require triagens/arangodb
```
#### Alternative 2: Cloning the git repository

When preferring this alternative, you need to have a git client installed. To clone the ArangoDB PHP client repository from github, execute the following command in your project directory:

    git clone "https://github.com/arangodb/arangodb-php.git"


This will create a subdirectory arangodb-php in your current directory. It contains all the files of the client library. It also includes a dedicated autoloader that you can use for autoloading the client libraries class files.
To invoke this autoloader, add the following line to your PHP files that will use the library:

```php
require 'arangodb-php/autoload.php';
```


The ArangoDB PHP client's autoloader will only care about its own class files and will not handle any other files. That means it is fully nestable with other autoloaders.

#### Alternative 3: Invoking the autoloader directly

If you do not wish to include autoload.php to load and setup the autoloader, you can invoke the autoloader directly:

```php
require 'arangodb-php/lib/ArangoDBClient/autoloader.php';
\ArangoDBClient\Autoloader::init();
```
