<?php

/**
 * ArangoDB PHP client: default values
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Contains default values used by the client
 *
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
abstract class DefaultValues
{
    /**
     * Default port number (used if no port specified)
     */
    const DEFAULT_PORT = 8529;

    /**
     * Default timeout value (used if no timeout value specified)
     */
    const DEFAULT_TIMEOUT = 30;

    /**
     * Default value for waitForSync (fsync all data to disk on document updates/insertions/deletions)
     */
    const DEFAULT_WAIT_SYNC = false;

    /**
     * Default value for collection journal size
     */
    const DEFAULT_JOURNAL_SIZE = 33554432;

    /**
     * Default value for isVolatile
     */
    const DEFAULT_IS_VOLATILE = false;

    /**
     * Default value for createCollection (create the collection on the fly when the first document is added to an unknown collection)
     */
    const DEFAULT_CREATE = false;

    /**
     * Default value for HTTP Connection header
     */
    const DEFAULT_CONNECTION = "Close";

    /**
     * Default update policy
     */
    const DEFAULT_UPDATE_POLICY = UpdatePolicy::ERROR;

    /**
     * Default replace policy
     */
    const DEFAULT_REPLACE_POLICY = UpdatePolicy::ERROR;

    /**
     * Default delete policy
     */
    const DEFAULT_DELETE_POLICY = UpdatePolicy::ERROR;

    /**
     * Default value for checking if data is UTF-8 conform
     */
    const DEFAULT_CHECK_UTF8_CONFORM = true;
}
