<?php

/**
 * ArangoDB PHP client: Base URLs
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Some basic URLs
 *
 * <br />
 *
 * @package triagens\ArangoDb
 * @since   0.2
 */
abstract class Urls
{
    /**
     * URL base part for all document-related REST calls
     */
    const URL_DOCUMENT = '/_api/document';

    /**
     * URL base part for all document-related REST calls
     */
    const URL_EDGE = '/_api/edge';

    /**
     * URL base part for all document-related REST calls
     */
    const URL_GRAPH = '/_api/graph';

    /**
     * URL base part for all document-related REST calls
     */
    const URLPART_VERTEX = 'vertex';

    /**
     * URL base part for all document-related REST calls
     */
    const URLPART_VERTICES = 'vertices';

    /**
     * URL base part for all document-related REST calls
     */
    const URLPART_EDGE = 'edge';

    /**
     * URL base part for all document-related REST calls
     */
    const URLPART_EDGES = 'edges';

    /**
     * URL base part for all collection-related REST calls
     */
    const URL_COLLECTION = '/_api/collection';

    /**
     * URL base part for all index-related REST calls
     */
    const URL_INDEX = '/_api/index';

    /**
     * base URL part for cursor related operations
     */
    const URL_CURSOR = '/_api/cursor';

    /**
     * base URL part for aql explain related operations
     */
    const URL_EXPLAIN = '/_api/explain';

    /**
     * base URL part for aql query validation related operations
     */
    const URL_QUERY = '/_api/query';

    /**
     * base URL part for select-by-example
     */
    const URL_EXAMPLE = '/_api/simple/by-example';

    /**
     * base URL part for first-example
     */
    const URL_FIRST_EXAMPLE = '/_api/simple/first-example';

    /**
     * base URL part for any
     */
    const URL_ANY = '/_api/simple/any';

    /**
     * base URL part for fulltext
     */
    const URL_FULLTEXT = '/_api/simple/fulltext';

    /**
     * base URL part for first
     */
    const URL_FIRST = '/_api/simple/first';

    /**
     * base URL part for last
     */
    const URL_LAST = '/_api/simple/last';

    /**
     * base URL part for remove-by-example
     */
    const URL_REMOVE_BY_EXAMPLE = '/_api/simple/remove-by-example';

    /**
     * base URL part for update-by-example
     */
    const URL_UPDATE_BY_EXAMPLE = '/_api/simple/update-by-example';

    /**
     * base URL part for replace-by-example
     */
    const URL_REPLACE_BY_EXAMPLE = '/_api/simple/replace-by-example';

    /**
     * base URL part for remove-by-example
     */
    const URL_IMPORT = '/_api/import';

    /**
     * base URL part for select-range
     */
    const URL_RANGE = '/_api/simple/range';

    /**
     * base URL part for select-all
     */
    const URL_ALL = '/_api/simple/all';

    /**
     * base URL part for select-range
     */
    const URL_NEAR = '/_api/simple/near';

    /**
     * base URL part for select-range
     */
    const URL_WITHIN = '/_api/simple/within';

    /**
     * base URL part for batch processing
     */
    const URL_BATCH = '/_api/batch';

    /**
     * base URL part for batch processing
     */
    const URL_TRANSACTION = '/_api/transaction';

    /**
     * base URL part for admin version
     */
    const URL_ADMIN_VERSION = '/_admin/version';
    
    /**
     * base URL part for server role
     */
    const URL_ADMIN_SERVER_ROLE = '/_admin/server/role';

    /**
     * base URL part for admin time
     */
    const URL_ADMIN_TIME = '/_admin/time';

    /**
     * base URL part for admin log
     */
    const URL_ADMIN_LOG = '/_admin/log';

    /**
     * base URL part for admin routing reload
     */
    const URL_ADMIN_ROUTING_RELOAD = '/_admin/routing/reload';

    /**
     * base URL part for admin modules flush
     */
    const URL_ADMIN_MODULES_FLUSH = '/_admin/modules/flush';

    /**
     * base URL part for admin statistics
     */
    const URL_ADMIN_STATISTICS = '/_admin/statistics';

    /**
     * base URL part for admin statistics-description
     */
    const URL_ADMIN_STATISTICS_DESCRIPTION = '/_admin/statistics-description';

    /**
     * base URL part for AQL user functions statistics
     */
    const URL_AQL_USER_FUNCTION = '/_api/aqlfunction';

    /**
     * base URL part for user management
     */
    const URL_USER = '/_api/user';

    /**
     * base URL part for user management
     */
    const URL_TRAVERSAL = '/_api/traversal';

    /**
     * base URL part for endpoint management
     */
    const URL_ENDPOINT = '/_api/endpoint';

    /**
     * base URL part for database management
     */
    const URL_DATABASE = '/_api/database';

}
