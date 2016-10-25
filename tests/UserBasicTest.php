<?php
/**
 * ArangoDB PHP client testsuite
 * File: UserBasicTest.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

use Installer\Exception;

/**
 * Class UserBasicTest
 *
 * @property Connection  $connection
 * @property UserHandler userHandler
 *
 * @package triagens\ArangoDb
 */
class UserBasicTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();
    }


    /**
     * Test permission handling
     */
    public function testGrantPermission()
    {
        $this->userHandler = new UserHandler($this->connection);

        $result = $this->userHandler->addUser('testUser42', 'testPasswd', true);
        static::assertTrue($result);

        $result = $this->userHandler->grantPermissions('testUser42', $this->connection->getDatabase());
        static::assertTrue($result);

        $options                                        = $this->connection->getOptions()->getAll();
        $options[ConnectionOptions::OPTION_AUTH_USER]   = 'testUser42';
        $options[ConnectionOptions::OPTION_AUTH_PASSWD] = 'testPasswd';
        $userConnection                                 = new Connection($options);

        $userHandler = new UserHandler($userConnection);
        $result      = $userHandler->getDatabases('testUser42');
        static::assertEquals($result, ['_system' => 'rw']);


        $this->userHandler->removeUser('testUser42');

        try {
            $userHandler->getDatabases('testUser42');
        } catch (\Exception $e) {
            // Just give us the $e
            static::assertEquals($e->getCode(), 401);
        }
        static::assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');
    }

    /**
     * Test permission handling
     */
    public function testGrantAndRevokePermissions()
    {
        $this->userHandler = new UserHandler($this->connection);

        $result = $this->userHandler->addUser('testUser42', 'testPasswd', true);
        static::assertTrue($result);

        $result = $this->userHandler->grantPermissions('testUser42', $this->connection->getDatabase());
        static::assertTrue($result);

        $options                                        = $this->connection->getOptions()->getAll();
        $options[ConnectionOptions::OPTION_AUTH_USER]   = 'testUser42';
        $options[ConnectionOptions::OPTION_AUTH_PASSWD] = 'testPasswd';
        $userConnection                                 = new Connection($options);

        $userHandler = new UserHandler($userConnection);
        $result      = $userHandler->getDatabases('testUser42');
        static::assertEquals($result, ['_system' => 'rw']);

        $result = $this->userHandler->revokePermissions('testUser42', $this->connection->getDatabase());
        static::assertTrue($result);

        $result = $userHandler->getDatabases('testUser42');
        static::assertEquals($result, ['_system' => 'none']);
    }


    /**
     * Test if Document and DocumentHandler instances can be initialized
     */
    public function testAddReplaceUpdateGetAndDeleteUserWithNullValues()
    {
        $this->userHandler = new UserHandler($this->connection);


        $result = $this->userHandler->addUser('testUser1', null, null, null);
        static::assertTrue($result);


        $this->userHandler->replaceUser('testUser1', null, null, null);
        static::assertTrue($result);


        $this->userHandler->updateUser('testUser1', null, null, null);
        static::assertTrue($result);


        $this->userHandler->removeUser('testUser1');
        static::assertTrue($result);
    }


    /**
     * Test if user can be added, modified and finally removed
     */
    public function testAddReplaceUpdateGetAndDeleteUserWithNonNullValues()
    {
        $this->userHandler = new UserHandler($this->connection);

        $result = $this->userHandler->addUser('testUser1', 'testPass1', true, ['level' => 1]);
        static::assertTrue($result);

        $e = null;
        try {
            $this->userHandler->addUser('testUser1', 'testPass1', true, ['level' => 1]);
        } catch (\Exception $e) {
            // Just give us the $e
            static::assertEquals($e->getCode(), 400);
        }
        static::assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');


        $response = $this->userHandler->get('testUser1');
        $extra    = $response->extra;
        static::assertTrue($response->active);
        static::assertEquals($extra['level'], 1, 'Should return 1');


        $this->userHandler->replaceUser('testUser1', 'testPass2', false, ['level' => 2]);
        static::assertTrue($result);


        $response = $this->userHandler->get('testUser1');
        $extra    = $response->extra;
        static::assertFalse($response->active);

        static::assertEquals($extra['level'], 2, 'Should return 2');


        $this->userHandler->updateUser('testUser1', null, null, ['level' => 3]);
        static::assertTrue($result);


        $response = $this->userHandler->get('testUser1');
        $extra    = $response->extra;
        static::assertFalse($response->active);

        static::assertEquals($extra['level'], 3, 'Should return 3');

        $this->userHandler->removeUser('testUser1');
        static::assertTrue($result);
    }


    // test functions on non-existent user
    public function testFunctionsOnNonExistentUser()
    {
        $this->userHandler = new UserHandler($this->connection);

        $e = null;
        try {
            $this->userHandler->removeUser('testUser1');
        } catch (\Exception $e) {
            // Just give us the $e
            static::assertEquals($e->getCode(), 404, 'Should get 404, instead got: ' . $e->getCode());
        }
        static::assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');


        $e = null;
        try {
            $this->userHandler->updateUser('testUser1', null, null, ['level' => 3]);
        } catch (\Exception $e) {
            // Just give us the $e
            static::assertEquals($e->getCode(), 404, 'Should get 404, instead got: ' . $e->getCode());
        }
        static::assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');


        $e = null;
        try {
            $this->userHandler->replaceUser('testUser1', 'testPass2', false, ['level' => 2]);
        } catch (\Exception $e) {
            // Just give us the $e
            static::assertEquals($e->getCode(), 404, 'Should get 404, instead got: ' . $e->getCode());
        }
        static::assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');


        $e = null;
        try {
            $this->userHandler->get('testUser1');
        } catch (\Exception $e) {
            // Just give us the $e
            static::assertEquals($e->getCode(), 404, 'Should get 404, instead got: ' . $e->getCode());
        }
        static::assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');
    }

    public function tearDown()
    {
        try {
            $this->userHandler->removeUser('testUser1');
        } catch (\Exception $e) {
            // Do nothing
        }

        try {
            $this->userHandler->removeUser('testUser42');
        } catch (\Exception $e) {
            // Do nothing
        }

        unset($this->userHandler, $this->connection);
    }
}
