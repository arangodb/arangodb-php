<?php

/**
 * ArangoDB PHP client: connection
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Provides access to the ArangoDB server
 *
 * As all access is done using HTTP, we do not need to establish a
 * persistent connection and keep its state.<br>
 * Instead, connections are established on the fly for each request
 * and are destroyed afterwards.<br>
 * <br>
 *
 * @package   triagens\ArangoDb
 * @since     0.2
 */
class Connection
{
    /**
     * Api Version
     *
     * @var string
     */
    public static $_apiVersion = 20000;

    /**
     * Connection options
     *
     * @var array
     */
    private $_options;

    /**
     * Connection handle, used in case of keep-alive
     *
     * @var resource
     */
    private $_handle;

    /**
     * Flag if keep-alive connections are used
     *
     * @var bool
     */
    private $_useKeepAlive;

    /**
     * Batches Array
     *
     * @var array
     */
    private $_batches = array();

    /**
     * $_activeBatch object
     *
     * @var array
     */
    private $_activeBatch = null;

    /**
     * $_captureBatch boolean
     *
     * @var boolean
     */
    private $_captureBatch = false;

    /**
     * $_batchRequest boolean
     *
     * @var boolean
     */
    private $_batchRequest = false;

    /**
     * $_database string
     *
     * @var string
     */
    private $_database = '';

    /**
     * Set up the connection object, validate the options provided
     *
     * @throws Exception
     *
     * @param array $options - initial connection options
     *
     * @return Connection
     */
    public function __construct(array $options)
    {
        $this->_options      = new ConnectionOptions($options);
        $this->_useKeepAlive = ($this->_options[ConnectionOptions::OPTION_CONNECTION] === 'Keep-Alive');
        $this->setDatabase($this->_options[ConnectionOptions::OPTION_DATABASE]);
    }

    /**
     * Close existing connection handle if a keep-alive connection was used
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->_useKeepAlive && is_resource($this->_handle)) {
            @fclose($this->_handle);
        }
    }

    /**
     * Get an option set for the connection
     *
     * @throws ClientException
     *
     * @param string $name - name of option
     *
     * @return mixed
     */
    public function getOption($name)
    {
        assert(is_string($name));

        return $this->_options[$name];
    }

    /**
     * Issue an HTTP GET request
     *
     * @throws Exception
     *
     * @param string $url - GET URL
     * @param array $customerHeader
     *
     * @return HttpResponse
     */
    public function get($url, $customerHeader = array())
    {
        $response = $this->executeRequest(HttpHelper::METHOD_GET, $url, '', $customerHeader);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP POST request with the data provided
     *
     * @throws Exception
     *
     * @param string $url  - POST URL
     * @param string $data - body to post
     *
     * @return HttpResponse
     */
    public function post($url, $data)
    {
        $response = $this->executeRequest(HttpHelper::METHOD_POST, $url, $data);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP PUT request with the data provided
     *
     * @throws Exception
     *
     * @param string $url  - PUT URL
     * @param string $data - body to post
     *
     * @return HttpResponse
     */
    public function put($url, $data)
    {
        $response = $this->executeRequest(HttpHelper::METHOD_PUT, $url, $data);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP Head request with the data provided
     *
     * @throws Exception
     *
     * @param string $url  - PUT URL
     * @param array $customerHeader
     *
     * @return HttpResponse
     */
    public function head($url, $customerHeader=array())
    {
        $response = $this->executeRequest(HttpHelper::METHOD_HEAD, $url, '', $customerHeader );

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP PATCH request with the data provided
     *
     * @throws Exception
     *
     * @param string $url  - PATCH URL
     * @param string $data - patch body
     *
     * @return HttpResponse
     */
    public function patch($url, $data)
    {
        $response = $this->executeRequest(HttpHelper::METHOD_PATCH, $url, $data);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP DELETE request with the data provided
     *
     * @throws Exception
     *
     * @param string $url - DELETE URL
     *
     * @return HttpResponse
     */
    public function delete($url)
    {
        $response = $this->executeRequest(HttpHelper::METHOD_DELETE, $url, '');

        return $this->parseResponse($response);
    }


    /**
     * Get a connection handle
     *
     * If keep-alive connections are used, the handle will be stored and re-used
     *
     * @throws ClientException
     * @return resource - connection handle
     */
    private function getHandle()
    {
        if ($this->_useKeepAlive && $this->_handle && is_resource($this->_handle)) {
            // keep-alive and handle was created already
            $handle = $this->_handle;

            // check if connection is still valid
            if (!feof($handle)) {
                // connection still valid
                return $handle;
            }

            // close handle
            @fclose($this->_handle);
            $this->_handle = 0;

            if (!$this->_options[ConnectionOptions::OPTION_RECONNECT]) {
                // if reconnect option not set, this is the end
                throw new ClientException('Server has closed the connection already.');
            }
        }

        // no keep-alive or no handle available yet or a reconnect
        $handle = HttpHelper::createConnection($this->_options);

        if ($this->_useKeepAlive && is_resource($handle)) {
            $this->_handle = $handle;
        }

        return $handle;
    }

    /**
     * Parse the response return the body values as an assoc array
     *
     * @throws Exception
     *
     * @param HttpResponse $response - the response as supplied by the server
     *
     * @return HttpResponse
     */
    public function parseResponse(HttpResponse $response)
    {
        $httpCode = $response->getHttpCode();

        if ($httpCode < 200 || $httpCode >= 400) {
            // failure on server

            $body = $response->getBody();
            if ($body != '') {
                // check if we can find details in the response body
                $details = json_decode($body, true);
                if (is_array($details)) {

                    // yes, we got details
                    $exception = new ServerException($response->getResult(), $httpCode);
                    $exception->setDetails($details);
                    throw $exception;
                }
            }

            // no details found, throw normal exception
            throw new ServerException($response->getResult(), $httpCode);
        }

        return $response;
    }

    /**
     * Execute an HTTP request and return the results
     *
     * This function will throw if no connection to the server can be established or if
     * there is a problem during data exchange with the server.
     *
     * will restore it.
     *
     * @throws Exception
     *
     * @param string $method - HTTP request method
     * @param string $url    - HTTP URL
     * @param string $data   - data to post in body
     * @param array $customerHeader - any arry containing header elements
     *
     * @return HttpResponse
     */
    private function executeRequest($method, $url, $data, $customerHeader = array())
    {
        HttpHelper::validateMethod($method);
        $database = $this->getDatabase();
        if ($database === '') {
            $url = '/_db/' . '_system' . $url;
        } else {
            $url = '/_db/' . $database . $url;
        }


        // create request data
        if ($this->_batchRequest === false) {

            if ($this->_captureBatch === true) {
                $this->_options->offsetSet(ConnectionOptions::OPTION_BATCHPART, true);
                $request = HttpHelper::buildRequest($this->_options, $method, $url, $data, $customerHeader);
                $this->_options->offsetSet(ConnectionOptions::OPTION_BATCHPART, false);
            } else {
                $request = HttpHelper::buildRequest($this->_options, $method, $url, $data, $customerHeader);
            }

            if ($this->_captureBatch === true) {
                $batchPart = $this->doBatch($method, $request);
                if (!is_null($batchPart)) {
                    return $batchPart;
                }
            }
        } else {
            $this->_batchRequest = false;

            $this->_options->offsetSet(ConnectionOptions::OPTION_BATCH, true);

            $request = HttpHelper::buildRequest($this->_options, $method, $url, $data, $customerHeader);
            $this->_options->offsetSet(ConnectionOptions::OPTION_BATCH, false);
        }


        $traceFunc = $this->_options[ConnectionOptions::OPTION_TRACE];
        if ($traceFunc) {
            // call tracer func
            if ($this->_options[ConnectionOptions::OPTION_ENHANCED_TRACE]) {
                list($header) = HttpHelper::parseHttpMessage($request);
                $headers = HttpHelper::parseHeaders($header);
                $traceFunc(new TraceRequest($headers[2], $method, $url, $data));
            } else {
                $traceFunc('send', $request);
            }
        }


        // open the socket. note: this might throw if the connection cannot be established
        $handle = $this->getHandle();

        if ($handle) {
            // send data and get response back

            if ($traceFunc) {
                // only issue syscall if we need it
                $startTime = microtime(true);
            }

            $result = HttpHelper::transfer($handle, $request);

            if ($traceFunc) {
                // only issue syscall if we need it
                $timeTaken = microtime(true) - $startTime;
            }

            if (!$this->_useKeepAlive) {
                // must close the connection
                fclose($handle);
            }

            /*
                        $status = socket_get_status($handle);
                        if ($status['timed_out']) {
                            throw new ClientException('Got a timeout when waiting on the server\'s response');
                        }
            */
            $response = new HttpResponse($result);

            if ($traceFunc) {
                // call tracer func
                if ($this->_options[ConnectionOptions::OPTION_ENHANCED_TRACE]) {
                    $traceFunc(
                        new TraceResponse($response->getHeaders(), $response->getHttpCode(), $response->getBody(
                        ), $timeTaken)
                    );
                } else {
                    $traceFunc('receive', $result);
                }
            }

            return $response;
        }

        throw new ClientException('Whoops, this should never happen');
    }

    /**
     * Get the client version (alias for getClientVersion)
     *
     * @return string
     */
    public static function getVersion()
    {
        return self::getClientVersion();
    }


    /**
     * Get the client version
     *
     * @return string
     */
    public static function getClientVersion()
    {
         return self::$_apiVersion;
    }

    /**
     * Stop capturing commands
     *
     * @return Batch - Returns the active batch object
     */
    public function stopCaptureBatch()
    {
        $this->_captureBatch = false;

        return $this->getActiveBatch();
    }


    /**
     * returns the active batch
     *
     * @return Batch active batch
     */
    public function getActiveBatch()
    {
        return $this->_activeBatch;
    }

    /**
     * Sets the active Batch for this connection
     *
     * @param Batch $batch - Sets the given batch as active
     *
     * @return Batch active batch
     */
    public function setActiveBatch($batch)
    {
        $this->_activeBatch = $batch;

        return $this->_activeBatch;
    }


    /**
     * Sets the batch capture state (true, if capturing)
     *
     * @param boolean $state true to turn on capture batch mode, false to turn it off
     */
    public function setCaptureBatch($state)
    {
        $this->_captureBatch = $state;
    }


    /**
     * Sets connection into Batch-request mode. This is needed for some operations to act differently when in this mode.
     *
     * @param boolean $state sets the connection state to batch request, meaning it is currently doing a batch request.
     */
    public function setBatchRequest($state)
    {
        $this->_batchRequest = $state;
    }


    /**
     * Returns true if this connection is in Batch-Capture mode
     *
     * @return bool
     *
     * returns the active batch
     */
    public function isInBatchCaptureMode()
    {
        return $this->_captureBatch;
    }


    /**
     * returns the active batch
     *
     */
    public function getBatches()
    {
        return $this->_batches;
    }


    /**
     * This is a helper function to executeRequest that captures requests if we're in batch mode
     *
     * @param mixed  $method  - The method of the request (GET, POST...)
     *
     * @param string $request - The request to process
     *
     * This checks if we're in batch mode and returns a placeholder object,
     * since we need to return some object that is expected by the caller.
     * if we're not in batch mode it doesn't return anything, and
     *
     * @return mixed Batchpart or null if not in batch capturing mode
     */
    private function doBatch($method, $request)
    {
        $batchPart = null;
        if ($this->_captureBatch === true) {

            /** @var $batch Batch */
            $batch = $this->getActiveBatch();

            $batchPart = $batch->append($method, $request);
        }

        # do batch processing
        return $batchPart;
    }


    /**
     * This function checks that the encoding of a string is utf.
     * It only checks for printable characters.
     *
     *
     * @param array $string the data to check
     *
     * @return boolean true if string is UTF-8, false if not
     */
    public static function detect_utf($string)
    {
        if (preg_match("//u", $string)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * This function checks that the encoding of the keys and
     * values of the array are utf-8, recursively.
     * It will raise an exception if it encounters wrong encoded strings.
     *
     * @param array $data the data to check
     *
     * @throws ClientException
     */
    public static function check_encoding($data)
    {
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                // check if the multibyte library function is installed and use it.
                if (function_exists('mb_detect_encoding')) {
                    // check with mb library
                    if (mb_detect_encoding($key, 'UTF-8', true) === false) {
                        throw new ClientException("Only UTF-8 encoded keys allowed. Wrong encoding in key string: " . $key);
                    }
                    if (mb_detect_encoding($value, 'UTF-8', true) === false) {
                        throw new ClientException("Only UTF-8 encoded values allowed. Wrong encoding in value string: " . $value);
                    }
                } else {
                    // fallback to preg_match checking
                    if (self::detect_utf($key) == false) {
                        throw new ClientException("Only UTF-8 encoded keys allowed. Wrong encoding in key string: " . $key);
                    }
                    if (self::detect_utf($value) == false) {
                        throw new ClientException("Only UTF-8 encoded values allowed. Wrong encoding in value string: " . $value);
                    }
                }
            } else {
                self::check_encoding($value);
            }
        }
    }


    /**
     * This is a json_encode() wrapper that also checks if the data is utf-8 conform.
     * internally it calls the check_encoding() method. If that method does not throw
     * an Exception, this method will happily return the json_encoded data.
     *
     * @param mixed $data    the data to encode
     * @param mixed $options the options for the json_encode() call
     *
     * @return string the result of the json_encode
     */
    public function json_encode_wrapper($data, $options = null)
    {
        if ($this->_options[ConnectionOptions::OPTION_CHECK_UTF8_CONFORM] === true) {
            self::check_encoding($data);
        }

        if (empty($data)) {
            $response = json_encode($data, $options | JSON_FORCE_OBJECT);
        } else {
            $response = json_encode($data, $options);
        }

        return $response;
    }


    /**
     * Set the database to use with this connection
     *
     * Sets the database to use with this connection, for example: 'my_database'<br>
     * Further calls to the database will be addressed to the given database.
     *
     * @param string $database the database to use
     */
    public function setDatabase($database)
    {
        $this->_database = $database;
    }

    /**
     * Get the database that is currently used with this connection
     *
     * Get the database to use with this connection, for example: 'my_database'
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->_database;
    }
}
