<?php

/**
 * ArangoDB PHP client: Base URLs
 * 
 * @package ArangoDbPhpClient
 * @author Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Some basic URLs
 *
 * @package ArangoDbPhpClient
 */
abstract class URLs {
  /**
   * URL base part for all document-related REST calls
   */
  const URL_DOCUMENT           = '/_api/document';
  
  /**
   * URL base part for all document-related REST calls
   */
  const URL_EDGE               = '/_api/edge';
  
  /**
   * URL base part for all document-related REST calls
   */
  const URL_GRAPH               = '/_api/graph';

  /**
   * URL base part for all collection-related REST calls
   */
  const URL_COLLECTION         = '/_api/collection';

  /**
   * URL base part for all index-related REST calls
   */
  const URL_INDEX         = '/_api/index';

  /**
   * base URL part for cursor related operations
   */
  const URL_CURSOR             = '/_api/cursor'; 

  /**
   * base URL part for aql explain related operations
   */
  const URL_EXPLAIN             = '/_api/explain'; 

  /**
   * base URL part for aql query validation related operations
   */
  const URL_QUERY             = '/_api/query'; 

  /**
   * base URL part for select-by-example
   */
  const URL_EXAMPLE            = '/_api/simple/by-example'; 

  /**
   * base URL part for remove-by-example
   */
  const URL_REMOVE_BY_EXAMPLE            = '/_api/simple/remove-by-example';

  /**
   * base URL part for remove-by-example
   */
  const URL_IMPORT            = '/_api/import';

  /**
   * base URL part for select-range
   */
  const URL_RANGE            = '/_api/simple/range'; 
  /**
   * base URL part for select-range
   */
  const URL_NEAR            = '/_api/simple/near'; 
  /**
   * base URL part for select-range
   */
  const URL_WITHIN            = '/_api/simple/within'; 
  /**
   * base URL part for batch processing
   */
  const URL_BATCH            = '/_api/batch'; 
}
