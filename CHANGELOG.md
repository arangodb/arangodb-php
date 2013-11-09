
v1.4.0 (2013-11-09)
-----------------------

* Contributors to this version:

  * Frank Mayer (Github: @frankmayer)
  * Jan Steemann (Github: @jsteemann)

=======================
* Fixes to Tests and Travis Setup (jsteemann)

* Implemented Multi-Database and Database API support. Closes #146

* Implemented Endpoint API support by extending the existing endpoint class. Closes #154

* Made changes in order to support the new "Location" header style.
  This renders the 1.4 client incompatible with ArangoDB version <1.4.0

* Support for Traversal API. Closes #148

* Changes to support the new AQL function namespace separator '::' instead of the old one ':'
  This renders the client incompatible to ArangoDB versions <1.4.0

* Changed expected cutdownvalue as ArangoDB's response text is different since 1.4.0
This renders the client incompatible to ArangoDB versions <1.4.0





v1.3.1 (2013-10-17)
-----------------------

* Contributors to this version:

  * Frank Mayer (Github: @frankmayer)
  * Francis Chuang (Github: @F21)
  * Alex (Github: @Beldur)
  * Jan Steemann (Github: @jsteemann)

=======================

* improved  documentation

* Added 'all' Method which corresponds to /simple/all

* performance optimisations (only visible if many requests go over one connection)

* fixed error message expectation

* Added support for deleting all registered functions in a namespace.

* Fixed getRegisteredAQLFunctions() not using the $namespace for filtering.

* Fixed AQL user function's register() not using supplied arguments.

* Added time taken to the tracer.





v1.3.0 (2013-05-13)
-----------------------

* Contributors to this version:
  * Frank Mayer (Github: @frankmayer)
  * Francis Chuang (Github: @F21)

=======================

* Changed PhpDocs @package content to triagens\ArangoDb, closes issue (see https://github.com/triAGENS/ArangoDB-PHP/issues/123)

* [Breaking change] Removed getServerStatus() in favor of getServerStatistics() This breaks intentionally,
	because of the different data and data structure returned by the server in version 1.3

* Cleaning spree :)
  * Fixed a lot of typos
  * Fixed some minor bugs
  * Fixed some tests
  * PSR-2 reformatting
  * Made some things more IDE Friendly (in regards to code-completion) and fixed some errors in the PHPDocs

* Initial AQL user functions implementation (see https://github.com/triAGENS/ArangoDB-PHP/pull/116)

* Implemented support for ArangoDB's transactions (see https://github.com/triAGENS/ArangoDB-PHP/issues/71)

* Re-implemented statistics according to new implementation in ArangoDB 1.3 (see https://github.com/triAGENS/ArangoDB-PHP/issues/113)

* Fixed precondition failed errors (see https://github.com/triAGENS/ArangoDB-PHP/issues/95)

* Minor fixes in tests

-----------------------





v1.2.1 (2013-05-01)
-----------------------

* Contributors to this version:

  * Francis Chuang (Github: @F21)
  * Dorthe Luebbert (Github: @luebbert42)
  * Jan Steemann (Github: @jsteemann)
  * Frank Mayer (Github: @frankmayer)

=======================

* Implemented create functions for each index type. #87

* Upgrade to 1.2.2 for travis.

* Added support for an enhanced tracer. #86

* Added keyOption and status support to collections.

* Added support for the simple any query to retrieve a random document from a collection.

* Added support for index options on index creation.

* Added support for instantiating graphs by passing in a graph name to the constructor. Additionally added getGraph() support to the graph handler.

* New method getAllCollections added

* Moved spl_autoload_register to init() of the autoloader.

* Updated DocumentHandler to include a convenience function store() that aliases add()/save() and replace(). #32

* Updating and replacing documents, edges and vertices will now also update the revision of the document object.

* Increased timeout for issue #55

* Timeout exception for issue #55

* Fixed bad encoding of empty arrays.

* Fixed any() for empty collections.

* Fixed formatting to psr2 standards.

* Fixed fatal errors when AQL statement returns a non-array that cannot be turned into a document.

* More PSR-2 Formatting

* Updated README to include example of invoking the autoloader directly

* Fixed some doc-block errors

-----------------------




v1.2.0 (2013-03-03)
-----------------------

* Implemented ArangoDB User-Management

* Fixed issue #46 "Cannot run AQL queries that produce non-document results"





v1.2.0-BETA2 (2013-02-23)
-----------------------

* Bumped travis ArangoDB version to 1.2.beta3

* Modified CollectionHandler.php to also accept a name and options instead of only a collection object (see https://github.com/triAGENS/ArangoDB-PHP/issues/44). Options can also be set when using an object. Also did some code-style fixing.

* Modified GraphHandler->saveVertex() and GraphHandler->saveEdge() so that an array can be passed instead of an object.

* Modified EdgeHandler->save() so that an array can be passed instead of an edge object.

* Modified DocumentHandler->save() so that an array can be passed instead of a document object.

* Implemented CollectionHandler->updateByExample() & CollectionHandler->replaceByExample(), (see https://github.com/triAGENS/ArangoDB-PHP/issues/40)

* Corrected HTTP response codes assertions in some cases, after ArangoDB is now correctly returning 404 in those cases

* Reformatted code to conform with PSR-1 and partly with PSR-2.

* Fixed: Options not implemented in CollectionHandler->RemoveByExample().

* Refactoring work done around generation of url parameters and body options from $options passed to methods.

* Changed client options on methods to be underscored. The non underscored options still work, but they are deprecated.
  Affected options are: sanitize, hiddenAttributes, includeInternals and ignoreHiddenAttributes.
  The new ones are: _sanitize, _hiddenAttributes, _includeInternals and _ignoreHiddenAttributes.

* Various documentation fixes.





v1.2.0-BETA1 (2013-02-20)
-----------------------

* Cleaned up and Extended Test-Suite.

* Implemented getMetaData() on Cursor.

* Implemented complete Graph REST-API support. See Graph and GraphHandler in the docs.

* Implemented CollectionHandler::firstExample() method.

* Fixed return value documentation of DocumentHandler::getByExample() and CollectionHandler::byExample() method.

* Implemented support for system and volatile collections.

* Implemented import functionality.

* Implemented remove document(s) by example.

* Extended Tests to include FULLTEXT index creation and deletion.

* Implemented getIndexes() and dropIndex() methods in the CollectionHandler.

* Implemented document creation using predefined key.

* Implemented support for ArangoDB 1.2. Users of ArangoDB 1.1 should continue to use v1.1.x of the PHP Client.
  The 1.1 branch of the PHP client will receive bug-fixes if necessary, but development will focus on the v1.2 branch.





v1.1.0 (2013-01-28)
-------------------

* Some refactoring in CollectionHandler.php.

* Implemented loading and unloading collections through collectionHandler->load(collection) and collectionHandler->unload(collection), written tests.

* Implemented collectionHandler->getProperties(collection), written tests.

* Implemented setting and getting journalSize, included in defaultvalues and written tests.

* issue #30: Fixed Bug: Character encoding and json_encode leads to data loss.

* issue #15: Feature: Implement Batch support for using with ArangoDB 1.1.

* issue #28: Feature: implement missing simple-query-related functionality => range(), near(), within().

* issue #22: Feature: Implement update() and updateById() methods on documents for 1.1 API.





v1.0.1 (2012-12-05)
-------------------

* issue #29: Feature: Implement Explain function for AQL queries.

 New Statement methods:

  - $statement->explain();
  - $statement->validate();

* issue #28: Feature: Implement missing simple-query-related functionality 

 Added Commands:
 
 - CollectionHandler->range($collectionId, $attribute, $left, $right, $options);

 - CollectionHandler->near($collectionId, $latitude, $longitude, $options);

 - CollectionHandler->within($collectionId, $latitude, $longitude, $radius, $options);

 Also added missing index creation method.
 
 - CollectionHandler->index($type, $fields, $unique) ;


 
 

v1.0.0 (2012-11-29)
-------------------

* issue #23: Fixed: DocumentHandler::deleteById() and removeById() : Parameter $revision should be optional.
  Fixed bug, written missing tests and corrected documentation.

* issue #21: Added hiding of fields for documents. Written tests and documentation.
  Implemented new feautre, written tests and documentation.

  Hiding of fields for documents:

      This applies to getAll(), __toString(), toJson() and toSerialized().
      getAll() now can be passed an array of options instead of the boolean 'includeInternals'.
      DocumentHandler::get() and getById() can also be given those options.

      CollectionHandler::byExample() can also be given those options.

      All the above functions except the __toString can be given an array of options
      currently these options are:

      'includeInternals' - true to include the internal attributes. Defaults to false.

      'ignoreHiddenAttributes' - true to show hidden attributes. Defaults to false.


* issue #18: Implemented revision check with policy = "error" 
  for update/replace and delete/remove methods.

* issue #17: Implemented basic edges support.

* issue #14: Refactored function names to better match the server api.
  This results in several methods being deprecated:

DocumentHandler:
  update(), updateById() => These are replaced by replace() and replaceById(). They will have their behavior changed in api version 1.1 in favor for the update method that has been added in ArangoDb 1.1.

  delete(), deleteById() => These are replaced by remove() and removeById(). They will be removed in api version 2.0.

  add() => This is being replaced by the new save() method. add() will be removed in api version 2.0.

  getByExample() => This is being replaced by CollectionHandler::byExample(). byExample() will be removed in api version 2.0.

  getAllIds() => This is being replaced by CollectionHandler::getAllIds(). getAllIds() will be removed in api version 2.0.

CollectionHandler:

  add() => This is being replaced by the new create() method. add() will be removed in api version 2.0.

  delete() => This is being replaced by the new drop() method. delete() will be removed in api version 2.0.

  getCount() => This is being replaced by the new count() method. getCount() will be removed in api version 2.0.

  getFigures() => This is being replaced by the new figures() method. getFigures() will be removed in api version 2.0.



* issue #11: Written initial unit tests for api.

* issue #10: Implemented api version info constant and a getVersion() method to go with it.

