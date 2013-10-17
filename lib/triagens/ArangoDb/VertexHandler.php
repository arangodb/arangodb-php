<?php

/**
 * ArangoDB PHP client: vertex document handler
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @author    Frank Mayer
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 * @since     1.2
 */

namespace triagens\ArangoDb;

/**
 * A handler that manages vertices.
 * A vertex-document handler that fetches vertices from the server and
 * persists them on the server. It does so by issuing the
 * appropriate HTTP requests to the server.
 *
 * <br />
 *
 * @package   triagens\ArangoDb
 * @since     1.2
 */
class VertexHandler extends
    DocumentHandler
{
    /**
     * Intermediate function to call the createFromArray function from the right context
     *
     * @param $data
     * @param $options
     *
     * @return Document
     */
    public function createFromArrayWithContext($data, $options)
    {
        return Vertex::createFromArray($data, $options);
    }
}
