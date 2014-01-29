<?php

/**
 * ArangoDB PHP client: http helper methods
 *
 * @package   triagens\ArangoDb
 * @author    Jan Steemann
 * @copyright Copyright 2012, triagens GmbH, Cologne, Germany
 */

namespace triagens\ArangoDb;

/**
 * Helper methods for HTTP request/response handling
 *
 * <br>
 *
 * @package triagens\ArangoDb
 * @since   0.2
 */
class HttpHelper
{
    /**
     * HTTP POST string constant
     */
    const METHOD_POST = 'POST';

    /**
     * HTTP PUT string constant
     */
    const METHOD_PUT = 'PUT';

    /**
     * HTTP DELETE string constant
     */
    const METHOD_DELETE = 'DELETE';

    /**
     * HTTP GET string constant
     */
    const METHOD_GET = 'GET';

    /**
     * HTTP HEAD string constant
     */
    const METHOD_HEAD = 'HEAD';

    /**
     * HTTP PATCH string constant
     */
    const METHOD_PATCH = 'PATCH';

    /**
     * Chunk size (number of bytes processed in one batch)
     */
    const CHUNK_SIZE = 8192;

    /**
     * End of line mark used in HTTP
     */
    const EOL = "\r\n";

    /**
     * HTTP protocol version used, hard-coded to version 1.1
     */
    const PROTOCOL = 'HTTP/1.1';

    /**
     * HTTP protocol version used, hard-coded to version 1.1
     */
    const MIME_BOUNDARY = 'XXXsubpartXXX';


    /**
     * Validate an HTTP request method name
     *
     * @throws ClientException
     *
     * @param string $method - method name
     *
     * @return bool - always true, will throw if an invalid method name is supplied
     */
    public static function validateMethod($method)
    {
        if ($method === self::METHOD_POST ||
            $method === self::METHOD_PUT ||
            $method === self::METHOD_DELETE ||
            $method === self::METHOD_GET ||
            $method === self::METHOD_HEAD ||
            $method === self::METHOD_PATCH
        ) {
            return true;
        }

        throw new ClientException('Invalid request method');
    }

    /**
     * Create a request string (header and body)
     *
     * @param ConnectionOptions $options - connection options
     * @param string            $method  - HTTP method
     * @param string            $url     - HTTP URL
     * @param string            $body    - optional body to post
     * @param array             $customerHeader - any arry containing header elements
     *
     * @return string - assembled HTTP request string
     */
    public static function buildRequest(ConnectionOptions $options, $method, $url, $body, $customerHeader = array())
    {
        $host   = $contentType = $authorization = $connection = '';
        $length = strlen($body);

        $endpoint = $options[ConnectionOptions::OPTION_ENDPOINT];
        if (Endpoint::getType($endpoint) !== Endpoint::TYPE_UNIX) {
            $host = sprintf('Host: %s%s', Endpoint::getHost($endpoint), self::EOL);
        }

        if ($options[ConnectionOptions::OPTION_BATCH] === true) {
            $contentType = 'Content-Type: multipart/form-data; boundary=' . self::MIME_BOUNDARY . self::EOL;
        } else {
            if ($length > 0 && $options[ConnectionOptions::OPTION_BATCHPART] === false) {
                // if body is set, we should set a content-type header
                $contentType = 'Content-Type: application/json' . self::EOL;
            }
        }

        if (isset($options[ConnectionOptions::OPTION_AUTH_TYPE]) && isset($options[ConnectionOptions::OPTION_AUTH_USER])) {
            // add authorization header
            $authorizationValue = base64_encode(
                $options[ConnectionOptions::OPTION_AUTH_USER] . ':' . $options[ConnectionOptions::OPTION_AUTH_PASSWD]
            );

            $authorization = sprintf(
                'Authorization: %s %s%s',
                $options[ConnectionOptions::OPTION_AUTH_TYPE],
                $authorizationValue,
                self::EOL
            );
        }

        if (isset($options[ConnectionOptions::OPTION_CONNECTION])) {
            // add connection header
            $connection = sprintf("Connection: %s%s", $options[ConnectionOptions::OPTION_CONNECTION], self::EOL);
        }

        $apiVersion = 'x-arango-version: ' . Connection::$_apiVersion . self::EOL;
        $customerHeaders = "";
        foreach ($customerHeader as $headerKey => $headerValue) {
            $customerHeaders .= $headerKey.": " . $headerValue . self::EOL;
        }

        // finally assemble the request
        $request = sprintf('%s %s %s%s', $method, $url, self::PROTOCOL, self::EOL) .
            $host .
            $apiVersion .
            $customerHeaders .
            $contentType .
            $authorization .
            $connection .
            sprintf('Content-Length: %s%s%s', $length, self::EOL, self::EOL) .
            $body;

        return $request;
    }

    /**
     * Execute an HTTP request on an opened socket
     *
     * It is the caller's responsibility to close the socket
     *
     * @param resource $socket  - connection socket (must be open)
     * @param string   $request - complete HTTP request as a string
     *
     * @throws ClientException
     * @return string - HTTP response string as provided by the server
     */
    public static function transfer($socket, $request)
    {
        if (!is_resource($socket)) {
            throw new ClientException('Invalid socket used');
        }

        assert(is_string($request));

        @fwrite($socket, $request);
        @fflush($socket);

        $contentLength  = null;
        $expectedLength = null;
        $totalRead      = 0;

        $result = '';
        $first  = true;

        while ($first || !feof($socket)) {
            $read = @fread($socket, self::CHUNK_SIZE);
            if ($read === false || $read === '') {
                break;
            }
            $totalRead += strlen($read);

            if ($first) {
                $result = $read;
                $first  = false;
            } else {
                $result .= $read;
            }

            if ($contentLength === null) {
                // check if content-length header is present
                $pos = stripos($result, "content-length: ");

                if ($pos !== false) {
                    $contentLength = (int) substr($result, $pos + 15, 10);
                }
            }

            if ($contentLength !== null && $expectedLength === null) {
                $bodyStart = strpos($result, "\r\n\r\n");
                if ($bodyStart !== false) {
                    $bodyStart += 4;
                    $expectedLength = $bodyStart + $contentLength;
                }
            }

            if ($totalRead >= $expectedLength) {
                break;
            }
        }

        return $result;
    }

    /**
     * Create a one-time HTTP connection by opening a socket to the server
     *
     * It is the caller's responsibility to close the socket
     *
     * @throws ConnectException
     *
     * @param ConnectionOptions $options - connection options
     *
     * @return resource - socket with server connection, will throw when no connection can be established
     */
    public static function createConnection(ConnectionOptions $options)
    {
        $fp = @fsockopen(
            $options[ConnectionOptions::OPTION_ENDPOINT],
            $options[ConnectionOptions::OPTION_PORT],
            $number,
            $message,
            $options[ConnectionOptions::OPTION_TIMEOUT]
        );
        if (!$fp) {
            throw new ConnectException($message, $number);
        }

        stream_set_timeout($fp, $options[ConnectionOptions::OPTION_TIMEOUT]);

        return $fp;
    }

    /**
     * Splits a http message into its header and body.
     *
     * @param string $httpMessage The http message string.
     *
     * @throws ClientException
     * @return array
     */
    public static function parseHttpMessage($httpMessage)
    {
        assert(is_string($httpMessage));

        $barrier = HttpHelper::EOL . HttpHelper::EOL;
        $parts   = explode($barrier, $httpMessage, 2);
        $parsed = HttpHelper::parseHeaders($parts[0]);
        if ($parsed[0] == 304 ||
            $parsed[0] == 204) {
            return $parts;
        }

        if (!isset($parts[1]) or $parts[1] === null) {
            throw new ClientException('Got an invalid response from the server');
        }

        return $parts;
    }

    /**
     * Process a string of HTTP headers into an array of header => values.
     *
     * @param string $headers - the headers string
     *
     * @return array
     */
    public static function parseHeaders($headers)
    {
        $httpCode  = null;
        $result    = null;
        $processed = array();

        foreach (explode(HttpHelper::EOL, $headers) as $lineNumber => $line) {
            $line = trim($line);

            if ($lineNumber == 0) {
                // first line of result is special
                $result = $line;
                if (preg_match("/^HTTP\/\d+\.\d+\s+(\d+)/", $line, $matches)) {
                    $httpCode = (int) $matches[1];
                }
            } else {
                // other lines contain key:value-like headers
                list($key, $value) = explode(':', $line, 2);
                $processed[strtolower(rtrim($key))] = ltrim($value);
            }
        }

        return array($httpCode, $result, $processed);
    }
}
