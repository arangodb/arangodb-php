<?php
/**
 * ArangoDB PHP client testsuite
 * File: userbasictest.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

use Installer\Exception;

class UserBasicTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
    }

    /**
     * Test if Document and DocumentHandler instances can be initialized
     */
    public function testAddReplaceUpdateGetAndDeleteUserWithNullValues()
    {
        $connection        = $this->connection;
        $this->userHandler = new \triagens\ArangoDb\UserHandler($this->connection);

        $result = $this->userHandler->addUser('testUser1', null, null, null);
        $this->assertTrue($result);

        $response = $this->userHandler->replaceUser('testUser1', null, null, null);
        $this->assertTrue($result);

        $response = $this->userHandler->updateUser('testUser1', null, null, null);
        $this->assertTrue($result);

        $response = $this->userHandler->removeUser('testUser1');
        $this->assertTrue($result);

        unset ($document);
    }

    /**
     * Test if user can be added, modifed and finally removed
     */
    public function testAddReplaceUpdateGetAndDeleteUserWithNonNullValues()
    {
        $connection        = $this->connection;
        $this->userHandler = new \triagens\ArangoDb\UserHandler($this->connection);

        $result = $this->userHandler->addUser('testUser1', 'testPass1', true, array('level' => 1));
        $this->assertTrue($result);

        $e=null;
        try {
            $this->userHandler->addUser('testUser1', 'testPass1', true, array('level' => 1));
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode()==400);
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');

        $response = $this->userHandler->get('testUser1');
        $extra    = $response->extra;
        $this->assertTrue($response->active, 'Should be true');
        $this->assertTrue($extra['level'] == 1, 'Should return 1');

        $response = $this->userHandler->replaceUser('testUser1', 'testPass2', false, array('level' => 2));
        $this->assertTrue($result);

        $response       = $this->userHandler->get('testUser1');
        $extra          = $response->extra;
        $secondPassword = $response->passwd;
        $this->assertFalse($response->active, 'Should be false');

        $this->assertTrue($extra['level'] == 2, 'Should return 2');

        $response = $this->userHandler->updateUser('testUser1', null, null, array('level' => 3));
        $this->assertTrue($result);

        $response = $this->userHandler->get('testUser1');
        $extra    = $response->extra;
        $this->assertFalse($response->active, 'Should be false');

        $this->assertTrue($extra['level'] == 3, 'Should return 3');
        $this->assertFalse($response->active, 'Should be false');

        $response = $this->userHandler->removeUser('testUser1');
        $this->assertTrue($result);

        unset ($document);
    }

    // test functions on non-existant user
    public function testFunctionsOnNonExistantUser()
    {
        $connection        = $this->connection;
        $this->userHandler = new \triagens\ArangoDb\UserHandler($this->connection);

        $e=null;
        try {
            $this->userHandler->removeUser('testUser1');
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode()==404, 'Should get 404, instead got: '.($e->getCode()));
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');

        $e=null;
        try {
            $this->userHandler->updateUser('testUser1', null, null, array('level' => 3));
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode()==404, 'Should get 404, instead got: '.($e->getCode()));
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');

        $e=null;
        try {
            $this->userHandler->replaceUser('testUser1', 'testPass2', false, array('level' => 2));
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode()==404, 'Should get 404, instead got: '.($e->getCode()));
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');

        $e=null;
        try {
            $this->userHandler->get('testUser1');
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode()==404, 'Should get 404, instead got: '.($e->getCode()));
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');

    }

    public function tearDown()
    {
        try {
            $this->userHandler->removeUser('testUser1');
        } catch (\Exception $e) {
            // Do nothing
        }

        unset($this->userHandler);
        unset($this->connection);
    }
}
