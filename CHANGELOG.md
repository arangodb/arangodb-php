# Changelog

## Release notes for the ArangoDB-PHP driver 3.8.x

This version of the PHP driver removes support for the MMFiles storage engine, which was
deprecated in the arangod server in version 3.6.0, and removed in version 3.7.0..

Updating, replacing or removing documents in the database using a revision id guard value
may return a different error message in case the revision id value of the found document is
not as expected.
Previous versions before 3.7 returned HTTP error 412, an ArangoDB error code 1200 and the 
error message string "precondition failed". This was changed in version 3.7 to return the 
same error codes, but an error message string of "conflict". Version 3.8 changes this again
so the error message string is now "conflict, _rev values do not match".


## Release notes for the ArangoDB-PHP driver 3.7.x

The corresponding ArangoDB version, ArangoDB 3.7 has dropped support for the MMFiles storage
engine. Usage of the MMFiles storage engine is still supported in the 3.7 version of the PHP
driver, in order to use the driver to connect to an ArangoDB 3.6.
But MMFiles support will be dropped from the PHP driver in version 3.8.

Updating, replacing or removing documents in the database using a revision id guard value
may return a different error message in case the revision id value of the found document is
not as expected.

Previous versions returned HTTP error 412, an ArangoDB error code 1200 and an error message
string "precondition failed". This version now returns the same error codes, but the error
message string is changed to "conflict".


## Release notes for the ArangoDB-PHP driver 3.6.x

Deprecated `Collection::setMinReplicationFactor()` and `Collection::getMinReplicationFactor()` 
in favor of `Collection::setWriteConcern()` and `Collection::getWriteConcern()`.

Added support for per-database default options (`replicationFactor`, `writeConcern` and 
`sharding`).

Added `maxRuntime` option to `Statement` class for automatically timing out queries on 
the server-side.

Added support for custom Analyzers for ArangoSearch.


## Release notes for the ArangoDB-PHP driver 3.5.x

Made `DocumentHandler::save()` an alias for `DocumentHandler::insert()`, to more closely
match the function names used in arangosh/arangod.

Added support for streaming transactions (i.e. transactions that can be composed of multiple
operations on the client side piece-by-piece without specifying the full transaction operations 
in advance).

Streaming transactions currently support the following operations:

- fetch documents by id, i.e. `DocumentHandler::getById()`
- update documents by id, i.e. `DocumentHandler::updateById()` 
- replace documents by id, i.e. `DocumentHandler::replaceById()` 
- remove documents by id, i.e. `DocumentHandler::removeById()`
- insert documents, i.e. `DocumentHandler::insert()`
- counting documents in a collection, i.e. `CollectionHandler::count()`
- truncating a collection, i.e. `CollectionHandler::truncate()`
- running AQL queries, i.e. `Statement::execute()`

Other driver operations than the above are currently not supported within streaming transactions.

Streaming transactions are provided by a new class `StreamingTransaction` and a new handler
`StreamingTransactionHandler`.

    $document           = new DocumentHandler($connection);
    $transactionHandler = new StreamingTransactionHandler($connection);

    // creates a transaction object
    $trx = new StreamingTransaction($connection, [
         TransactionBase::ENTRY_COLLECTIONS => [
             TransactionBase::ENTRY_WRITE => [ 'testCollection' ]
         ]
    ]);

    // starts the transaction
    $trx = $transactionHandler->create($trx);

    // get a StreamingTransactionCollection object. this is used to execute operations
    // in a transaction context
    $trxCollection = $trx->getCollection('testCollection');

    // pass the StreamingTransactionCollection into the document operations instead of
    // a regular Collection object - this will make the operations execute in the context
    // of the currently running transaction
    $result = $documentHandler->insert($trxCollection, [ '_key' => 'test1', 'value' => 'test1' ]);

    $result = $documentHandler->insert($trxCollection, [ '_key' => 'test2', 'value' => 'test2' ]);

    // commits the transaction
    $transactionHandler->commit($trx);

Caveat: streaming transactions will normally stay open on the server side until they are explicitly 
aborted or committed by the client application, or until they time out automatically on the server.
Therefore by default the PHP driver will automatically keep track of all begun streaming transactions,
via an instance variable in the `StreamingTransactionHandler`.

Streaming transactions are automatically aborted on shutdown via a shutdown function, and all
transactions started via `StreamingTransactionHandler` instances that were neither committed nor 
aborted by the user will be aborted. 
In order to take over the management of a transaction from the `StreamingTransactionHandler`, it is
possible to call the handler's `stealTransaction()` method with the transaction's id. This will
make the handler "forget" about auto-aborting this particular transaction.


Deprecated several methods in `CollectionHandler`, because they are deprecated in the arangod
server as well:

- CollectionHandler::fulltext()
- CollectionHandler::updateByExample()
- CollectionHandler::replaceByExample()
- CollectionHandler::range()
- CollectionHandler::near()
- CollectionHandler::within()


Added method `CollectionHandler::getShards()` to retrieve the list of available shards of a collection.

Added method `CollectionHandler::getResponsibleShard()` to retrieve the shard id of the shard
responsible for storing a particular document.


All index-specific index-creation methods in `CollectionHandler` are now deprecated in favor of
the much more general method `CollectionHandler::createIndex()`. This new methods replaces the
following deprecated methods:

- CollectionHandler::createHashIndex()
- CollectionHandler::createFulltextIndex()
- CollectionHandler::createSkipListIndex()
- CollectionHandler::createPersistentIndex()
- CollectionHandler::createTtlIndex()
- CollectionHandler::createGeoIndex()
- CollectionHandler::index()

`CollectionHandler::createIndex()` now also supports named indexes and background indexing via
setting the respective options on index creation, e.g.

    $collectionHandler->createIndex($collection, [
        'type'         => 'persistent',
        'name'         => 'my-index',
        'fields'       => ['a', 'b'],
        'unique'       => true,
        'sparse'       => false,
        'inBackground' => true
    ]);

The now deprecated specialized index methods will be removed in a future release of the driver
in favor of the generic `createIndex` method.


The `CollectionHandler` class got a new method `createTtlIndex` for creating time-to-live (TTL)
indexes on the server.

All specialized methods for index creation also got an extra optional attribute `$inBackground` that 
enables background index creation.

Added driver support for the following attributes on collection level:

- distributeShardsLike
- smartJoinAttribute (only effective in ArangoDB enterprise edition)
- minReplicationFactor

Removed unused `$_action` member in class `AqlUserFunction`, also 
removed its `__toString()` method.


## Release notes for the ArangoDB-PHP driver 3.4.x


Starting with release 3.4.0, the following constants were removed from the 
`CollectionHandler` class:

- `OPTION_IGNORE_NULL`
- `OPTION_CONSTRAINT`

These constants were geo-index related, and the geo-index functionality changes in ArangoDB
3.4 have made these constants obsolete.

For the same reason, the `createGeoIndex` function signature in the same class has
changed from

    public function createGeoIndex($collectionId, array $fields, $geoJson = null, $constraint = null, $ignoreNull = null)

to just

    public function createGeoIndex($collectionId, array $fields, $geoJson = null)

Additionally the 3.4 release of the driver adds support for the following collection
properties:

- replicationFactor: number of replicas to keep per shard in a cluster environment
  (a replication factor of 1 will be used if this is not specified)
- shardingStrategy: sharding strategy to be used for the collection

The `Collection` class also got the new methods `setReplicationFactor`, `getReplicationFactor`,
`setShardingStrategy` and `getShardingStrategy`.

A method `getEntries` was added to the `QueryCacheHandler` class, which allows to
peek into the contents of the query cache at runtime.

The single-document APIs in class `DocumentHandler` have been augmented so they support
the attributes `returnOld` and `returnNew`. This allows retrieving the previous version
of documents on update/replace/remove, and returning the new version of documents after
insert/update/replace.
In addition, the `save` method of `DocumentHandler` will now understand the `overwrite`
option, which will turn an insert into a replace operation in case the insert fails with a 
unique constraint violation error on the primary key.

The method `insert` was introduced in `DocumentHandler` as an alias for the existing `save`
method to be consistent with the server-side method naming.

Basic support for ArangoSearch Views was added in 3.4.0, via the `View` and `ViewHandler`
classes.


## Release notes for the ArangoDB-PHP driver 3.3.x

Starting from release 3.3.1, the PHP driver has support for automatic failover, for
ArangoDB servers that are started in the active failover mode. This setup requires 
using ArangoDB 3.3.

In order to use automatic failover from the PHP driver, simply change the "endpoint"
attribute of the connection options from a simple endpoint string into an array of
endpoint strings:

    $connectionOptions = [
        ConnectionOptions::OPTION_ENDPOINT => [ 'tcp://localhost:8531', 'tcp://localhost:8532', 'tcp://localhost:8530' ],
        ...
    ];
    $connection = new Connection($connectionOptions);

instead of just

    $connectionOptions = [
        ConnectionOptions::OPTION_ENDPOINT => 'tcp://localhost:8530',
        ...
    ];
    $connection = new Connection($connectionOptions);


Additionally, retrieving the endpoint value of `ConnectionOptions` will now always 
return an array of endpoints. For the single-server case, the returned value will be
an array with the specified endpoint. When active failover is used, the result will
be an array with the specified endpoints or the endpoints found (added) at runtime.
For example, in

    $options = [ ConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529' ];
    $co = new ConnectionOptions($options);
    print_r($co[ConnectionOptions::OPTION_ENDPOINT]);

This will now print an array (`[ 'tcp://127.0.0.1:8529' ]`) and not just the string
(`tcp://127.0.0.1:8529'). Client applications that retrieve the endpoint value via
the `ConnectionOptions` object and expect it to be a string should be adjusted to 
pick the first value from the now-returned result array instead.

Using the port option for setting up `ConnectionOptions` and reading it back is now 
deprecated and will not be useful when using different endpoints with different port
numbers.

For example, reading the `port` option here will provide just one of the specified
ports, so it should be avoided:

    $options = [ ConnectionOptions::OPTION_ENDPOINT => [ 'tcp://127.0.0.1:8529', 'tcp://127.0.0.1:8530' ] ];
    $co = new ConnectionOptions($options);
    print_r($co[ConnectionOptions::OPTION_PORT]);


## Release notes for the ArangoDB-PHP driver 3.2.x

- the default value for the authentication type of the `Connection` class is now `Basic`

- the default value for the connection type is now `Keep-Alive` and not `Close`

- Document ID's are now handled correctly. This is a necessary and ___backwards incompatible change___, in order to be consistent with ArangoDB's API.

  Why: It was necessary to fix the document ids to correctly use ArangoDB's _ids instead of only the _key. 

  __Important incompatible changes related to this:__
  - Document::getId(): Will return the correct id (CollectionName/DocumentID) instead of the key (DocumentID).
  - UrlHelper::getDocumentIdFromLocation(): Will  now return a "real" _id instead of what was essentially the `_key`

  __Other changes related to this:__
  - DocumentHandler::getById(): Will work as before, but it will also accept a "real" document ID in addition to the key. 
  If a real document ID is given, the collection data will be extracted from that string. That means that the first parameter `$collection` does not need to have a valid value, in that case.

- The namespace `\triagens\ArangoDb` was replaced with `\ArangoDBClient`.
For each class in the old namespace there is now a class alias that points
from the new namespace to the old namespace, so existing applications can
still use the class names from the `\triagens\ArangoDb` namespace

- Support for PHP 5.5 has been removed.

- added new methods for collection and database level permissions:
  - `UserHandler::getDatabasePermissionLevel` 
  - `UserHandler::getCollectionPermissionLevel`
  - `UserHandler::grantCollectionPermissions`
  - `UserHandler::revokeCollectionPermissions`


## Release notes for the ArangoDB-PHP driver 3.1.0

This version of the driver is compatible with ArangoDB 3.1.x
It is not compatible to earlier versions of ArangoDB (i.e. 2.x).
Please use one of the `2.x` branches of the driver for 2.x-compatibility.

**Caution!**

- Up until the 3.0.x versions of this driver, there were still deprecated methods and parameter compatibility functions in the code, which unfortunately were not removed according to their deprecation annotations.
That deprecated code was now finally removed with this version (3.1.0) of the driver, in order to clean up the codebase.
- With this version of the driver, the method signature that used to accept $options either as an array or a non-array type has been removed. The specific compatibility layer was deprecated a long time ago and did not provide any benefits apart from compatibility. Starting with this version of the driver, there is now only one method signature that will require $options to be an array. 

Please check and change your code accordingly!

### Changes

- Removed old deprecated methods:
  - AdminHandler::flushServerModuleCache()
  - CollectionHandler::add()
  - CollectionHandler::getCount()
  - CollectionHandler::getFigures()
  - CollectionHandler::delete()
  - DocumentHandler::getAllIds()
  - DocumentHandler::getByExample()
  - DocumentHandler::add()
  - DocumentHandler::delete()
  - DocumentHandler::deleteById()
  - EdgeHandler::add()
  - Graph::setVerticesCollection()
  - Graph::getVerticesCollection()
  - Graph::setEdgesCollection()
  - Graph::getEdgesCollection()
  - Handler::getCursorOptions()

- Removed the old-style compatibility layer for parameter-passing in various methods that was used prior to switching to the $options parameter.
  This means, that wherever an $option array is passed to methods and a non-array type was also allowed (bool, string) for $options, the $options parameter **must** now be an array - it will not accept bool values or string values anymore, like for example a policy definition.

- Performance might be a bit better due to the removal of the compatibility layer for $options.

- Cleaned up and enriched annotations

- Applied various smaller bug fixes

- GraphHandler: Optimized code to do less work when not necessary
- GraphHandler: Implemented optional cache that caches the Vertex/Edge-Collections instead of making expensive calls to the DB.
- GraphHandler: Is now batch-able. However, if any collections need to be fetched, they will be done out-of-batch.
  If a lot of calls to the GraphHandler are being made, the use of the new caching functionality is encouraged.
- Batches: Some work has been done, to optimize batches. This is still in development.
- Switched from phpDocumentor to apigen
- New Docs were generated


## Release notes for the ArangoDB-PHP driver 3.0.8

This version of the driver is compatible with ArangoDB 3.0.x
It is not compatible to earlier versions of ArangoDB (i.e. 2.x).
Please use ones of the `2.x` branches of the driver for 2.x-compatibility.

### Bug fixes

Fixed bug related to creating the correct collection type.
This was no problem for the default, which is 'document', but it was a problem
when the option 'createCollection'=>true was passed with save_edge().


## Release notes for the ArangoDB-PHP driver 3.0.7

This version of the driver is compatible with ArangoDB 3.0.7
It is not compatible to earlier versions of ArangoDB (i.e. 2.x).
Please use ones of the `2.x` branches of the driver for 2.x-compatibility.

### Changed functionality

**Batch processing**

Added an option to pre-define a batch size for a batch.
This results in the driver using an SplFixedArray for the storage of the batch parts,
which in turn results to a bit (5% to 15%) more performance in batch processing.

The option is called batchSize and accepts an integer.

Example:
        $batch = new Batch($this->connection, ['batchSize' => 10000]);

### Bug fixes

Do to the many API changes in version 3 of ArangoDB, the driver had to go through a lot of changes too.
This resulted in some inconsistencies in its functionality. Version 3.0.7 has hopefully dealt with them all.
If there should be any more left, please create an issue to report it.


## Release notes for the ArangoDB-PHP driver 3.0

This version of the driver is compatible with ArangoDB 3.0. 
It is not compatible to earlier versions of ArangoDB (i.e. 2.x).
Please use ones of the `2.x` branches of the driver for 2.x-compatibility.

### Changed functionality

**Graph Management**

When replacing edges via the `EdgeHandler::replace()` method, it is now
required to specify both the `_from` and `_to` values of the replacing edge.
If either attribute is missing or invalid, the replace operation will fail
with an error `invalid edge attribute` on the server-side.

That means the following may not work:

```php
$edgeHandler = new EdgeHandler($connection);

$edge = new Edge();
$edge->set("_id", $idOfExistingEdge);
/* set some other edge attributes */
...

$result = $edgeHandler->replace($edge);
```

until at least `_from` and `_to` are also set via the `setFrom()` and `setTo()`
methods:

```php
$edgeHandler = new EdgeHandler($connection);

$edge = new Edge();
$edge->set("_id", $idOfExistingEdge);
/* set some other edge attributes */
...
$edge->setFrom($fromHandle);
$edge->setTo($toHandle);

$result = $edgeHandler->replace($edge);
```

Note that this affects only the `replace()` and `replaceById()` methods and
not `update()` nor `updateById()`.

**User management**

The user management APIs in class `UserHandler` have changed slightly. The methods for adding,
replacing and updating users had an optional parameter named `$options`, which did nothing.
This parameter has been removed.

The API methods simplify to:

- UserHandler::addUser($username, $passwd = null, $active = null, $extra = null, $options = array())
- UserHandler::replaceUser($username, $passwd = null, $active = null, $extra = null, $options = array())
- UserHandler::updateUser($username, $passwd = null, $active = null, $extra = null, $options = array())

- UserHandler::addUser($username, $passwd = null, $active = null, $extra = null)
- UserHandler::replaceUser($username, $passwd = null, $active = null, $extra = null)
- UserHandler::updateUser($username, $passwd = null, $active = null, $extra = null)

Note that when adding a new user via the `addUser()` method, the new user will now be given
access permissions for the current database the PHP driver is connected to.
User permissions can be adjusted manually by using the following new methods of the
`UserHandler` class:

- UserHandler::grantPermissions($username, $databaseName) 
- UserHandler::revokePermissions($username, $databaseName) 

### Unsupported functionality

**Cap constraints**

Support for cap constraints has been discontinued on the 3.0 version of ArangoDB.
Therefore, the following methods have also been removed from the PHP driver in
the 3.0 branch:

- CollectionHandler::createCapConstraint($collectionId, $size)
- CollectionHandler::first($collectionId, $count = null)
- CollectionHandler::last($collectionId, $count = null)

**Graph functions**

The ArangoDB PHP driver provided PHP wrapper methods for common graph functions
that were implemented server-side. When one of these wrapper methods was called,
the PHP driver assembled an AQL query that called the equivalent graph AQL functions
on the server. The driver has provided some extra post-filtering options for some
of the graph functions, but for others it only provided a subset of the features
available server-side.

With ArangoDB 3.0, the graph functionality on the server-side has changed: the
previously available AQL graph functions that were called by the PHP driver are 
not available anymore in 3.0. This affects the following previously existing
methods of the PHP driver's `GraphHandler` class, which are now gone in 3.0:

- GraphHandler::getNeighborVertices($graph, $vertexExample, $options = array())
- GraphHandler::getConnectedEdges($graph, $vertexId, $options = array())
- GraphHandler::getVertices($graph, $options = array())
- GraphHandler::getEdges($graph, $options = array())
- GraphHandler::getPaths($graph, $options = array())
- GraphHandler::getShortestPaths($graph, $startVertexExample = array(), $endVertexExample = array(), $options = array())
- GraphHandler::getDistanceTo($graph, $startVertexExample = null, $endVertexExample = null, $options = array())
- GraphHandler::getCommonNeighborVertices($graph, $vertex1Example = null, $vertex2Example = null, $options1 = array(),$options2 = array())
- GraphHandler::getCommonProperties($graph, $vertex1Example= null, $vertex2Example = null, $options = array())
- GraphHandler::getAbsoluteEccentricity($graph, $vertexExample = null, $options = array())
- GraphHandler::getEccentricity($graph, $options = array())
- GraphHandler::getAbsoluteCloseness($graph, $vertexExample = null, $options = array())
- GraphHandler::getCloseness($graph, $options = array())
- GraphHandler::getAbsoluteBetweenness($graph, $options = array())
- GraphHandler::getBetweenness($graph, $options = array())
- GraphHandler::getRadius($graph, $options = array())
- GraphHandler::getDiameter($graph, $options = array())

Most of these methods can be emulated by issuing an AQL query from the PHP driver.
AQL provides provides blocks for computing the vertices, connected edges, and paths 
in a graph or just dedicated collections. As a bonus, by using AQL queries one is
not limited to the subset of the functionality that was available in the "old"
graph functions' interfaces, but can use the full functionality and composability
of AQL.

**Custom queues**

"Custom queues" were an undocumented, experimental feature in later versions
of the 2.x driver. Its purpose was to send requests to dedicated processing
queues on the server. This functionality has been removed from the 3.0 ArangoDB
server and the 3.0 driver.

Due to that the following undocumented methods have been removed from the
PHP driver:

- Handler::enableCustomQueue($queueName, $count = null) 
- Handler::disableCustomQueue() 
- Connection::enableCustomQueue($queueName, $count = null) 
- Connection::disableCustomQueue() 

**Client versioning**

The client-side versioning feature was also removed from the driver in version
3.0. The versioning feature allowed sending the HTTP header `X-Arango-Version`
with the desired version number for all requests made from the driver. The
ArangoDB server interpreted the value of this HTTP header at some endpoints and
returned result structures matching the ones from older versions of ArangoDB.

This feature was abandoned on the server-side in 3.0 so the versioning was
removed from the driver as well. This also means the following methods have
been removed from the driver's `Connection` class.

- Connection::getVersion()
- Connection::getClientVersion()
