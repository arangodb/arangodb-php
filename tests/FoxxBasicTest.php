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
    public function setUp(): void
    {
        $this->connection  = getConnection();
        $this->foxxHandler = new FoxxHandler($this->connection);
        
        try {
            // ignore errors
            $this->foxxHandler->removeFoxxApp('/hello_world');
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
        $response    = $foxxHandler->installFoxxZip($zip, '/hello_world');
        static::assertEquals(200, (int) $response['code'], 'Status not 200');
        static::assertEquals('/hello_world', $response['mount'], 'Wrong mountpoint');
    }

    /**
     * Try to upload and install a non-existing app
     */
    public function testUploadAndInstallNonExistingFoxxApp()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $foxxHandler = $this->foxxHandler;
        $zip         = __DIR__ . '/files_for_tests/move_along.zip';
        $foxxHandler->installFoxxZip($zip, '/move_along');
    }


    public function tearDown(): void
    {
        $foxxHandler = $this->foxxHandler;
        try {
            // ignore errors
            $foxxHandler->removeFoxxApp('/hello_world');
        } catch (ClientException $e) {
            // ignore
        }
        unset($this->foxxHandler, $this->connection);
    }
}
