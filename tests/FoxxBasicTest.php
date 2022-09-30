<?php
/**
 * ArangoDB PHP client testsuite
 * File: FoxxBasicTest.php
 *
 * @package   ArangoDBClient
 * @author    Tom Regner <thomas.regner@fb-research.de>
 */

namespace ArangoDBClient;


/**
 * Class FoxxBasicTest
 *
 * @property Connection  $connection
 * @property FoxxHandler $foxxHandler
 *
 * @package   ArangoDBClient
 */
class FoxxBasicTest extends
    \PHPUnit_Framework_TestCase
{
    private FoxxHandler $foxxHandler;

    public function setUp(): void
    {
        $this->connection  = getConnection();
        $this->foxxHandler = new FoxxHandler($this->connection);
        
        try {
            // ignore errors
            $this->foxxHandler->uninstallService('/hello_world');
        } catch (ClientException $e) {
            // ignore
        }
    }


    /**
     * Try to upload and install a demo app
     */
    public function testUploadAndInstallFoxxApp()
    {
        $foxxHandler = $this->foxxHandler;
        $zip         = __DIR__ . '/files_for_tests/demo-hello-foxx-master.zip';
        $response    = $foxxHandler->installService($zip, '/hello_world');
        static::assertEquals('/hello_world', $response['mount'], 'Wrong mountpoint');
    }

    /**
     * Fetch service meta data two ways
     */
    public function testServiceInfo()
    {
        $foxxHandler = $this->foxxHandler;
        $zip         = __DIR__ . '/files_for_tests/demo-hello-foxx-master.zip';
        $expected = [
            'mount' => '/hello_world',
            'name'  => 'hello-foxx',
            'version' => '2.0.0',
            'development' => false,
            'legacy' => false,
        ];
        $foxxHandler->installService($zip, '/hello_world');
        $response = $foxxHandler->serviceInfo("/hello_world");
        static::assertEquals(array_intersect_assoc($response, $expected), $expected);
        $response = $foxxHandler->services();
        static::assertEquals(array_intersect_assoc($response[0], $expected), $expected);
    }
    /**
     * Try to upload and install a non-existing app
     */
    public function testUploadAndInstallNonExistingFoxxApp()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $foxxHandler = $this->foxxHandler;
        $zip         = __DIR__ . '/files_for_tests/move_along.zip';
        $foxxHandler->installService($zip, '/move_along');
    }

    /**
     * cleanup and remove the service
     */
    public function tearDown(): void
    {
        $foxxHandler = $this->foxxHandler;
        try {
            // ignore errors
            $foxxHandler->uninstallService('/hello_world');
        } catch (ClientException $e) {
            // ignore
        }
        unset($this->foxxHandler, $this->connection);
    }
}
