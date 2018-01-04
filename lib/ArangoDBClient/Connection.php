<?php

/**
 * ArangoDB PHP client: connection
 *
 * @package   ArangoDBClient
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace ArangoDBClient;

/**
 * Provides access to the ArangoDB server
 *
 * As all access is done using HTTP, we do not need to establish a
 * persistent connection and keep its state.<br>
 * Instead, connections are established on the fly for each request
 * and are destroyed afterwards.<br>
 *
 * @package   ArangoDBClient
 * @since     0.2
 */
class Connection
{
    /**
     * Connection options
     *
     * @var array
     */
    private $_options;

    /**
     * Pre-assembled HTTP headers string for connection
     * This is pre-calculated when connection options are set/changed, to avoid
     * calculation of the same HTTP header values in each request done via the
     * connection
     *
     * @var string
     */
    private $_httpHeader = '';

    /**
     * Pre-assembled base URL for the current database
     * This is pre-calculated when connection options are set/changed, to avoid
     * calculation of the same base URL in each request done via the
     * connection
     *
     * @var string
     */
    private $_baseUrl = '';

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
    private $_batches = [];

    /**
     * $_activeBatch object
     *
     * @var Batch
     */
    private $_activeBatch;

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
     */
    public function __construct(array $options)
    {
        $this->_options      = new ConnectionOptions($options);
        $this->_useKeepAlive = ($this->_options[ConnectionOptions::OPTION_CONNECTION] === 'Keep-Alive');
        $this->setDatabase($this->_options[ConnectionOptions::OPTION_DATABASE]);

        $this->updateHttpHeader();
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
     * Set an option set for the connection
     *
     * @throws ClientException
     *
     * @param string $name  - name of option
     * @param string $value - value of option
     */
    public function setOption($name, $value)
    {
        if ($name === ConnectionOptions::OPTION_ENDPOINT ||
            $name === ConnectionOptions::OPTION_HOST ||
            $name === ConnectionOptions::OPTION_PORT ||
            $name === ConnectionOptions::OPTION_VERIFY_CERT ||
            $name === ConnectionOptions::OPTION_CIPHERS ||
            $name === ConnectionOptions::OPTION_ALLOW_SELF_SIGNED
        ) {
            throw new ClientException('Must not set option ' . $value . ' after connection is created.');
        }

        $this->_options[$name] = $value;

        // special handling for several options
        if ($name === ConnectionOptions::OPTION_TIMEOUT) {
            // set the timeout option: patch the stream of an existing connection
            if (is_resource($this->_handle)) {
                stream_set_timeout($this->_handle, $value);
            }
        } else if ($name === ConnectionOptions::OPTION_CONNECTION) {
            // set keep-alive flag
            $this->_useKeepAlive = (strtolower($value) === 'keep-alive');
        } else if ($name === ConnectionOptions::OPTION_DATABASE) {
            // set database
            $this->setDatabase($value);
        }

        $this->updateHttpHeader();
    }

    /**
     * Get the options set for the connection
     *
     * @return ConnectionOptions
     */
    public function getOptions()
    {
        return $this->_options;
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
     * @param array  $customHeaders
     *
     * @return HttpResponse
     */
    public function get($url, array $customHeaders = [])
    {
        $response = $this->executeRequest(HttpHelper::METHOD_GET, $url, '', $customHeaders);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP POST request with the data provided
     *
     * @throws Exception
     *
     * @param string $url  - POST URL
     * @param string $data - body to post
     * @param array  $customHeaders
     *
     * @return HttpResponse
     */
    public function post($url, $data, array $customHeaders = [])
    {
        $response = $this->executeRequest(HttpHelper::METHOD_POST, $url, $data, $customHeaders);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP PUT request with the data provided
     *
     * @throws Exception
     *
     * @param string $url  - PUT URL
     * @param string $data - body to post
     * @param array  $customHeaders
     *
     * @return HttpResponse
     */
    public function put($url, $data, array $customHeaders = [])
    {
        $response = $this->executeRequest(HttpHelper::METHOD_PUT, $url, $data, $customHeaders);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP Head request with the data provided
     *
     * @throws Exception
     *
     * @param string $url - PUT URL
     * @param array  $customHeaders
     *
     * @return HttpResponse
     */
    public function head($url, array $customHeaders = [])
    {
        $response = $this->executeRequest(HttpHelper::METHOD_HEAD, $url, '', $customHeaders);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP PATCH request with the data provided
     *
     * @throws Exception
     *
     * @param string $url  - PATCH URL
     * @param string $data - patch body
     * @param array  $customHeaders
     *
     * @return HttpResponse
     */
    public function patch($url, $data, array $customHeaders = [])
    {
        $response = $this->executeRequest(HttpHelper::METHOD_PATCH, $url, $data, $customHeaders);

        return $this->parseResponse($response);
    }

    /**
     * Issue an HTTP DELETE request with the data provided
     *
     * @throws Exception
     *
     * @param string $url - DELETE URL
     * @param array  $customHeaders
     * @param string $data - delete body
     *
     * @return HttpResponse
     */
    public function delete($url, array $customHeaders = [], $data = '')
    {
        $response = $this->executeRequest(HttpHelper::METHOD_DELETE, $url, $data, $customHeaders);

        return $this->parseResponse($response);
    }


    /**
     * Recalculate the static HTTP header string used for all HTTP requests in this connection
     */
    private function updateHttpHeader()
    {
        $this->_httpHeader = HttpHelper::EOL;

        $endpoint = $this->_options[ConnectionOptions::OPTION_ENDPOINT];
        if (Endpoint::getType($endpoint) !== Endpoint::TYPE_UNIX) {
            $this->_httpHeader .= sprintf('Host: %s%s', Endpoint::getHost($endpoint), HttpHelper::EOL);
        }

        if (isset($this->_options[ConnectionOptions::OPTION_AUTH_TYPE], $this->_options[ConnectionOptions::OPTION_AUTH_USER])) {
            // add authorization header
            $authorizationValue = base64_encode(
                $this->_options[ConnectionOptions::OPTION_AUTH_USER] . ':' .
                $this->_options[ConnectionOptions::OPTION_AUTH_PASSWD]
            );

            $this->_httpHeader .= sprintf(
                'Authorization: %s %s%s',
                $this->_options[ConnectionOptions::OPTION_AUTH_TYPE],
                $authorizationValue,
                HttpHelper::EOL
            );
        }

        if (isset($this->_options[ConnectionOptions::OPTION_CONNECTION])) {
            // add connection header
            $this->_httpHeader .= sprintf('Connection: %s%s', $this->_options[ConnectionOptions::OPTION_CONNECTION], HttpHelper::EOL);
        }

        if ($this->_database === '') {
            $this->_baseUrl = '/_db/_system';
        } else {
            $this->_baseUrl = '/_db/' . urlencode($this->_database);
        }
    }

    /**
     * Get a connection handle
     *
     * If keep-alive connections are used, the handle will be stored and re-used
     *
     * @throws ClientException
     * @return resource - connection handle
     * @throws \ArangoDBClient\ConnectException
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
     * Execute an HTTP request and return the results
     *
     * This function will throw if no connection to the server can be established or if
     * there is a problem during data exchange with the server.
     *
     * will restore it.
     *
     * @throws Exception
     *
     * @param string $method        - HTTP request method
     * @param string $url           - HTTP URL
     * @param string $data          - data to post in body
     * @param array  $customHeaders - any array containing header elements
     *
     * @return HttpResponse
     */
    private function executeRequest($method, $url, $data, array $customHeaders = [])
    {
        assert($this->_httpHeader !== '');
        $wasAsync = false;
        if (is_array($customHeaders) && isset($customHeaders[HttpHelper::ASYNC_HEADER])) {
            $wasAsync = true;
        }

        HttpHelper::validateMethod($method);
        $url = $this->_baseUrl . $url;

        // create request data
        if ($this->_batchRequest === false) {

            if ($this->_captureBatch === true) {
                $this->_options->offsetSet(ConnectionOptions::OPTION_BATCHPART, true);
                $request = HttpHelper::buildRequest($this->_options, $this->_httpHeader, $method, $url, $data, $customHeaders);
                $this->_options->offsetSet(ConnectionOptions::OPTION_BATCHPART, false);
            } else {
                $request = HttpHelper::buildRequest($this->_options, $this->_httpHeader, $method, $url, $data, $customHeaders);
            }

            if ($this->_captureBatch === true) {
                $batchPart = $this->doBatch($method, $request);
                if (null !== $batchPart) {
                    return $batchPart;
                }
            }
        } else {
            $this->_batchRequest = false;

            $this->_options->offsetSet(ConnectionOptions::OPTION_BATCH, true);
            $request = HttpHelper::buildRequest($this->_options, $this->_httpHeader, $method, $url, $data, $customHeaders);
            $this->_options->offsetSet(ConnectionOptions::OPTION_BATCH, false);
        }


        $traceFunc = $this->_options[ConnectionOptions::OPTION_TRACE];
        if ($traceFunc) {
            // call tracer func
            if ($this->_options[ConnectionOptions::OPTION_ENHANCED_TRACE]) {
                list($header) = HttpHelper::parseHttpMessage($request, $url, $method);
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

            $result = HttpHelper::transfer($handle, $request, $method);

            if ($traceFunc) {
                // only issue syscall if we need it
                $timeTaken = microtime(true) - $startTime;
            }

            $status = socket_get_status($handle);
            if ($status['timed_out']) {
                throw new ClientException('Got a timeout while waiting for the server\'s response', 408);
            }

            if (!$this->_useKeepAlive) {
                // must close the connection
                fclose($handle);
            }

            $response = new HttpResponse($result, $url, $method, $wasAsync);

            if ($traceFunc) {
                // call tracer func
                if ($this->_options[ConnectionOptions::OPTION_ENHANCED_TRACE]) {
                    $traceFunc(
                        new TraceResponse(
                            $response->getHeaders(), $response->getHttpCode(), $response->getBody(),
                            $timeTaken
                        )
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
            if ($body !== '') {
                // check if we can find details in the response body
                $details = json_decode($body, true);
                if (is_array($details) && isset($details['errorMessage'])) {
                    // yes, we got details
                    $exception = new ServerException($details['errorMessage'], $details['code']);
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
     * Stop capturing commands
     *
     * @return Batch - Returns the active batch object
     */
    public function stopCaptureBatch()
    {
        $this->_captureBatch = false;

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
     * returns the active batch
     *
     * @return Batch active batch
     */
    public function getActiveBatch()
    {
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
     */
    public function isInBatchCaptureMode()
    {
        return $this->_captureBatch;
    }


    /**
     * returns the active batch
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
     * if we're not in batch mode it does not return anything, and
     *
     * @return mixed Batchpart or null if not in batch capturing mode
     * @throws \ArangoDBClient\ClientException
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
        if (preg_match('//u', $string)) {
            return true;
        }

        return false;
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
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                // check if the multibyte library function is installed and use it.
                if (function_exists('mb_detect_encoding')) {
                    // check with mb library
                    if (is_string($key) && mb_detect_encoding($key, 'UTF-8', true) === false) {
                        throw new ClientException('Only UTF-8 encoded keys allowed. Wrong encoding in key string: ' . $key);
                    }
                    if (is_string($value) && mb_detect_encoding($value, 'UTF-8', true) === false) {
                        throw new ClientException('Only UTF-8 encoded values allowed. Wrong encoding in value string: ' . $value);
                    }
                } else {
                    // fallback to preg_match checking
                    if (is_string($key) && self::detect_utf($key) === false) {
                        throw new ClientException('Only UTF-8 encoded keys allowed. Wrong encoding in key string: ' . $key);
                    }
                    if (is_string($value) && self::detect_utf($value) === false) {
                        throw new ClientException('Only UTF-8 encoded values allowed. Wrong encoding in value string: ' . $value);
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
     * @throws \ArangoDBClient\ClientException
     */
    public function json_encode_wrapper($data, $options = 0)
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
        $this->_options[ConnectionOptions::OPTION_DATABASE] = $database;
        $this->_database                                    = $database;

        $this->updateHttpHeader();
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

class_alias(Connection::class, '\triagens\ArangoDb\Connection');
