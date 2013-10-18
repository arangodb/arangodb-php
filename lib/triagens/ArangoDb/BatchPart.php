<?php

/**
 * ArangoDB PHP client: batchpart
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 * @since   1.1
 *
 */

namespace triagens\ArangoDb;

/**
 * Provides batch part functionality
 *
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     1.1
 */


class BatchPart
{


    /**
     * An array of BatchPartCursor options
     *
     * @var array $_batchParts
     */
    private $_cursorOptions = array();


    /**
     * An array of BatchPartCursor options
     *
     * @var array $_batchParts
     */
    private $_id = null;


    /**
     * An array of BatchPartCursor options
     *
     * @var array $_batchParts
     */
    private $_type = null;


    /**
     * An array of BatchPartCursor options
     *
     * @var array $_batchParts
     */
    private $_request = array();


    /**
     * An array of BatchPartCursor options
     *
     * @var array $_batchParts
     */
    private $_response = array();


    /**
     * The batch that this instance is part of
     *
     * @var Batch $_batch
     */
    private $_batch = null;


    /**
     * Constructor
     *
     * @internal
     *
     * @param Batch $batch    the batch object, that this part belongs to
     * @param mixed $id       The id of the batch part. TMust be unique and wil be passed to the server in the content-id header
     * @param mixed $type     The type of the request. This is to distinguish the different request type in order to return correct results.
     * @param mixed $request  The request string
     * @param mixed $response The response string
     * @param mixed $options  optional, options like sanitize, that can be passed to the request/response handler.
     *
     * @return BatchPart
     */

    public function __construct($batch, $id, $type, $request, $response, $options)
    {
        $sanitize = false;
        $options  = array_merge($options, $this->getCursorOptions($sanitize));
        extract($options, EXTR_IF_EXISTS);
        $this->setBatch($batch);
        $this->setId($id);
        $this->setType($type);
        $this->setRequest($request);
        $this->setResponse($response);
        $this->_cursorOptions[Cursor::ENTRY_SANITIZE] = $sanitize;

        return $this;
    }


    /**
     * Sets the id for the current batch part.
     *
     * @param Batch $batch
     *
     * @return Batch
     */
    public function setBatch($batch)
    {
        $this->_batch = $batch;

        return $this;
    }


    /**
     * Sets the id for the current batch part.
     *
     * @param mixed $id
     *
     * @return Batch
     */
    public function setId($id)
    {
        $this->_id = $id;

        return $this;
    }


    /**
     * Gets the id for the current batch part.
     *
     * @return Batch
     */
    public function getId()
    {
        return $this->_id;
    }


    /**
     * Sets the type for the current batch part.
     *
     * @param mixed $type
     *
     * @return Batch
     */
    public function setType($type)
    {
        $this->_type = $type;

        return $this;
    }


    /**
     * Gets the type for the current batch part.
     *
     * @return Batch
     */
    public function getType()
    {
        return $this->_type;
    }


    /**
     * Sets the request for the current batch part.
     *
     * @param mixed $request
     *
     * @return Batch
     */
    public function setRequest($request)
    {
        $this->_request = $request;

        return $this;
    }


    /**
     * Gets the request for the current batch part.
     *
     * @return Batch
     */
    public function getRequest()
    {
        return $this->_request;
    }


    /**
     * Sets the response for the current batch part.
     *
     * @param mixed $response
     *
     * @return Batch
     */
    public function setResponse($response)
    {
        $this->_response = $response;

        return $this;
    }


    /**
     * Gets the response for he current batch part.
     *
     * @return HttpResponse
     */
    public function getResponse()
    {
        return $this->_response;
    }


    /**
     * Gets the HttpCode for he current batch part.
     *
     * @return int
     */
    public function getHttpCode()
    {
        return $this->getResponse()->getHttpCode();
    }


    /**
     * Get the batch part identified by the array key (0...n) or its id (if it was set with nextBatchPartId($id) )
     *
     * @throws ClientException
     * @return mixed $partId
     */
    public function getProcessedResponse()
    {
        $response = $this->getResponse();
        switch ($this->_type) {
            case 'getdocument':
                $json             = $response->getJson();
                $options          = $this->getCursorOptions();
                $options['isNew'] = false;
                $response         = Document::createFromArray($json, $options);
                break;
            case 'document':
                $json = $response->getJson();
                if ($json['error'] === false) {
                    $id       = $json[Document::ENTRY_ID];
                    $response = $id;
                }
                break;
            case 'getedge':
                $json             = $response->getJson();
                $options          = $this->getCursorOptions();
                $options['isNew'] = false;
                $response         = Edge::createFromArray($json, $options);
                break;
            case 'edge':
                $json = $response->getJson();
                if ($json['error'] === false) {
                    $id       = $json[Edge::ENTRY_ID];
                    $response = $id;
                }
                break;
            case 'getcollection':
                $json             = $response->getJson();
                $options          = $this->getCursorOptions();
                $options['isNew'] = false;
                $response         = Collection::createFromArray($json, $options);
                break;
            case 'collection':
                $json = $response->getJson();
                if ($json['error'] === false) {
                    $id       = $json[Collection::ENTRY_ID];
                    $response = $id;
                }
                break;
            case 'cursor':
                $options          = $this->getCursorOptions();
                $options['isNew'] = false;
                $response         = new Cursor($this->_batch->getConnection(), $response->getJson(), $options);
                break;
            default:
                throw new ClientException('Could not determine response data type.');
                break;
        }

        return $response;
    }


    /**
     * Return an array of cursor options
     *
     * @return array - array of options
     */
    private function getCursorOptions()
    {
        return $this->_cursorOptions;
    }
}
