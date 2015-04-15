<?php
/**
 * ArangoDB PHP client testsuite
 * File: ConnectionTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class ConnectionTest
 *
 * @property Connection        $connection
 * @property Collection        $collection
 * @property Collection        $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler   $documentHandler
 *
 * @package triagens\ArangoDb
 */
class ConnectionTest extends
    \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestTracer');
        } catch (\Exception $e) {
            //Silence the exception
        }
    }

    /**
     * Test if Connection instance can be initialized
     */
    public function testInitializeConnection()
    {
        $connection = getConnection();
        $this->assertInstanceOf('triagens\ArangoDb\Connection', $connection);
    }


    /**
     * This is just a test to really test connectivity with the server before moving on to further tests.
     */
    public function testGetStatus()
    {
        $connection = getConnection();
        $response   = $connection->get('/_admin/statistics');
        $this->assertTrue($response->getHttpCode() == 200, 'Did not return http code 200');
    }

    /**
     * Test get options
     */
    public function testGetOptions()
    {
        $connection = getConnection();

        $value = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        $this->assertEquals(12, $value);
        
        $value = $connection->getOption(ConnectionOptions::OPTION_CONNECTION);
        $this->assertEquals('Close', $value);
        
        $value = $connection->getOption(ConnectionOptions::OPTION_RECONNECT);
        $this->assertFalse($value);

        $value = $connection->getOption(ConnectionOptions::OPTION_DATABASE);
        $this->assertEquals("_system", $value);
    }
    
    /**
     * Test set options
     */
    public function testSetOptions()
    {
        $connection = getConnection();

        // timeout
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 10);
        $value = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        $this->assertEquals(10, $value);
        
        // connection
        $connection->setOption(ConnectionOptions::OPTION_CONNECTION, 'Keep-Alive');
        $value = $connection->getOption(ConnectionOptions::OPTION_CONNECTION);
        $this->assertEquals('Keep-Alive', $value);
       
        // reconnect 
        $connection->setOption(ConnectionOptions::OPTION_RECONNECT, true);
        $value = $connection->getOption(ConnectionOptions::OPTION_RECONNECT);
        $this->assertTrue($value);
        
        $connection->setOption(ConnectionOptions::OPTION_RECONNECT, false);
        $value = $connection->getOption(ConnectionOptions::OPTION_RECONNECT);
        $this->assertFalse($value);
    }
    
    /**
     * Test set invalid options
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testSetEndpointOption()
    {
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_ENDPOINT, "tcp://127.0.0.1:8529");
    }
    
    /**
     * Test set invalid options
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testSetHostOption()
    {
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_HOST, "127.0.0.1");
    }
    
    /**
     * Test set invalid options
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testSetPortOption()
    {
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_PORT, "127.0.0.1");
    }
    
    /**
     * Test get/set database
     */
    public function testGetSetDatabase()
    {
        $connection = getConnection();

        $value = $connection->getOption(ConnectionOptions::OPTION_DATABASE);
        $this->assertEquals("_system", $value);
        
        $value = $connection->getDatabase();
        $this->assertEquals("_system", $value);
       
        // set the database to something else and re-check
        $connection->setDatabase("foobar");
        
        $value = $connection->getOption(ConnectionOptions::OPTION_DATABASE);
        $this->assertEquals("foobar", $value);
        
        $value = $connection->getDatabase();
        $this->assertEquals("foobar", $value);
        
        // set the database back and re-check
        $connection->setOption(ConnectionOptions::OPTION_DATABASE, "_system");
        
        $value = $connection->getOption(ConnectionOptions::OPTION_DATABASE);
        $this->assertEquals("_system", $value);
        
        $value = $connection->getDatabase();
        $this->assertEquals("_system", $value);
    }
    
    /**
     * Test timeout exception
     *
     * @expectedException \triagens\ArangoDb\ClientException
     */
    public function testSetTimeoutException()
    {
        $connection = getConnection();
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 3);
        $query = 'RETURN SLEEP(6)';

        $statement = new Statement($connection, array("query" => $query));

        try {
            // this is expected to fail
            $statement->execute();
        } catch (ClientException $exception) {
            $this->assertEquals($exception->getCode(), 408);
            throw $exception;
        }
    }
    
    /**
     * Test timeout, no exception
     */
    public function testSetTimeout()
    {
        $connection = getConnection();
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 5);
        $query = 'RETURN SLEEP(1)';

        $statement = new Statement($connection, array("query" => $query));

        // should work
        $cursor = $statement->execute();
        $this->assertEquals(1, count($cursor->getAll()));
    }

    /**
     * Test if we can get the api version
     */
    public function testGetApiVersion()
    {
        $connection = getConnection();

        $response = $connection->getVersion();
        $this->assertTrue($response >0, 'Version number is not correct!');

        $response = $connection->getClientVersion();
        $this->assertTrue($response >0, 'Version number is not correct!');
    }

    /**
     * Test the basic tracer
     */
    public function testBasicTracer()
    {
        //Setup
        $self        = $this; //Hack for PHP 5.3 compatibility
        $basicTracer = function ($type, $data) use ($self) {
            $self->assertContains(
                 $type,
                 array('send', 'receive'),
                 'Basic tracer\'s type should only be \'send\' or \'receive\''
            );
            $self->assertInternalType('string', $data, 'Basic tracer data is not a string!.');
        };

        $options                                  = getConnectionOptions();
        $options[ConnectionOptions::OPTION_TRACE] = $basicTracer;

        $connection        = new Connection($options);
        $collectionHandler = new CollectionHandler($connection);

        //Try creating a collection
        $collectionHandler->create('ArangoDB_PHP_TestSuite_TestTracer');

        //Delete the collection
        try {
            $collectionHandler->drop('ArangoDB_PHP_TestSuite_TestTracer');
        } catch (Exception $e) {
        }
    }

    /**
     * Test the enhanced tracer
     */
    public function testEnhancedTracer()
    {
        //Setup
        $self = $this; //Hack for PHP 5.3 compatibility

        $enhancedTracer = function ($data) use ($self) {
            $self->assertTrue(
                 $data instanceof TraceRequest || $data instanceof TraceResponse,
                 '$data must be instance of TraceRequest or TraceResponse.'
            );

            $self->assertInternalType('array', $data->getHeaders(), 'Headers should be an array!');
            $self->assertNotEmpty($data->getHeaders(), 'Headers should not be an empty array!');
            $self->assertInternalType('string', $data->getBody(), 'Body must be a string!');

            if ($data instanceof TraceRequest) {
                $self->assertContains(
                     $data->getMethod(),
                     array(
                          HttpHelper::METHOD_DELETE,
                          HttpHelper::METHOD_GET,
                          HttpHelper::METHOD_HEAD,
                          HttpHelper::METHOD_PATCH,
                          HttpHelper::METHOD_POST,
                          HttpHelper::METHOD_PUT
                     ),
                     'Invalid http method!'
                );

                $self->assertInternalType('string', $data->getRequestUrl(), 'Request url must be a string!');
                $self->assertEquals('request', $data->getType());

                foreach ($data->getHeaders() as $header => $value) {
                    $self->assertInternalType('string', $value, "The header value should be a string");
                    $self->assertInternalType('string', $header, "The header should be a string");
                }
            } else {
                $self->assertInternalType('integer', $data->getHttpCode(), 'Http code must be an integer!');
                $self->assertInternalType(
                     'string',
                     $data->getHttpCodeDefinition(),
                     'Http code definition must be a string!'
                );
                $self->assertEquals('response', $data->getType());
                $self->assertInternalType('float', $data->getTimeTaken());
            }
        };

        $options                                           = getConnectionOptions();
        $options[ConnectionOptions::OPTION_TRACE]          = $enhancedTracer;
        $options[ConnectionOptions::OPTION_ENHANCED_TRACE] = true;

        $connection        = new Connection($options);
        $collectionHandler = new CollectionHandler($connection);

        //Try creating a collection
        $collectionHandler->create('ArangoDB_PHP_TestSuite_TestTracer');

        //Delete the collection
        try {
            $collectionHandler->drop('ArangoDB_PHP_TestSuite_TestTracer');
        } catch (Exception $e) {
        }
    }

    public function tearDown()
    {
        unset($this->connection);

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestTracer');
        } catch (\Exception $e) {
            //Silence the exception
        }

        unset($this->collectionHandler);
    }
}
