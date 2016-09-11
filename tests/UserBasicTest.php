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
 * @property Connection              $connection
 * @property UserHandler             userHandler
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
        $this->assertTrue($result);
        
        $result = $this->userHandler->grantPermissions('testUser42', $this->connection->getDatabase());
        $this->assertTrue($result);
        
        $options = $this->connection->getOptions()->getAll();
        $options[ConnectionOptions::OPTION_AUTH_USER] = 'testUser42';
        $options[ConnectionOptions::OPTION_AUTH_PASSWD] = 'testPasswd';
        $userConnection = new Connection($options);

        $userHandler = new UserHandler($userConnection);
        $result =  $userHandler->getDatabases('testUser42');
        $this->assertEquals($result, array($this->connection->getDatabase()));
        
        $this->userHandler->removeUser('testUser42');

        try {
            $result = $userHandler->getDatabases('testUser42');
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode() == 401);
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');
    }
    
    /**
     * Test permission handling
     */
    public function testGrantAndRevokePermissions()
    {
        $this->userHandler = new UserHandler($this->connection);

        $result = $this->userHandler->addUser('testUser42', 'testPasswd', true);
        $this->assertTrue($result);
        
        $result = $this->userHandler->grantPermissions('testUser42', $this->connection->getDatabase());
        $this->assertTrue($result);
        
        $options = $this->connection->getOptions()->getAll();
        $options[ConnectionOptions::OPTION_AUTH_USER] = 'testUser42';
        $options[ConnectionOptions::OPTION_AUTH_PASSWD] = 'testPasswd';
        $userConnection = new Connection($options);

        $userHandler = new UserHandler($userConnection);
        $result = $userHandler->getDatabases('testUser42');
        $this->assertEquals($result, array($this->connection->getDatabase()));
        
        $result = $this->userHandler->revokePermissions('testUser42', $this->connection->getDatabase());
        $this->assertTrue($result);

        try {
            $result = $userHandler->getDatabases('testUser42');
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode() == 401);
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');
    }


    /**
     * Test if Document and DocumentHandler instances can be initialized
     */
    public function testAddReplaceUpdateGetAndDeleteUserWithNullValues()
    {
        $this->userHandler = new UserHandler($this->connection);


        $result = $this->userHandler->addUser('testUser1', null, null, null);
        $this->assertTrue($result);


        $this->userHandler->replaceUser('testUser1', null, null, null);
        $this->assertTrue($result);


        $this->userHandler->updateUser('testUser1', null, null, null);
        $this->assertTrue($result);


        $this->userHandler->removeUser('testUser1');
        $this->assertTrue($result);
    }


    /**
     * Test if user can be added, modified and finally removed
     */
    public function testAddReplaceUpdateGetAndDeleteUserWithNonNullValues()
    {
        $this->userHandler = new UserHandler($this->connection);

        $result = $this->userHandler->addUser('testUser1', 'testPass1', true, array('level' => 1));
        $this->assertTrue($result);

        $e = null;
        try {
            $this->userHandler->addUser('testUser1', 'testPass1', true, array('level' => 1));
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode() == 400);
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');


        $response = $this->userHandler->get('testUser1');
        $extra    = $response->extra;
        $this->assertTrue($response->active);
        $this->assertTrue($extra['level'] == 1, 'Should return 1');


        $this->userHandler->replaceUser('testUser1', 'testPass2', false, array('level' => 2));
        $this->assertTrue($result);


        $response = $this->userHandler->get('testUser1');
        $extra    = $response->extra;
        $this->assertFalse($response->active);

        $this->assertTrue($extra['level'] == 2, 'Should return 2');


        $this->userHandler->updateUser('testUser1', null, null, array('level' => 3));
        $this->assertTrue($result);


        $response = $this->userHandler->get('testUser1');
        $extra    = $response->extra;
        $this->assertFalse($response->active);

        $this->assertTrue($extra['level'] == 3, 'Should return 3');

        $this->userHandler->removeUser('testUser1');
        $this->assertTrue($result);
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
            $this->assertTrue($e->getCode() == 404, 'Should get 404, instead got: ' . ($e->getCode()));
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');


        $e = null;
        try {
            $this->userHandler->updateUser('testUser1', null, null, array('level' => 3));
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode() == 404, 'Should get 404, instead got: ' . ($e->getCode()));
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');


        $e = null;
        try {
            $this->userHandler->replaceUser('testUser1', 'testPass2', false, array('level' => 2));
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode() == 404, 'Should get 404, instead got: ' . ($e->getCode()));
        }
        $this->assertInstanceOf('triagens\ArangoDb\ServerException', $e, 'should have gotten an exception');


        $e = null;
        try {
            $this->userHandler->get('testUser1');
        } catch (\Exception $e) {
            // Just give us the $e
            $this->assertTrue($e->getCode() == 404, 'Should get 404, instead got: ' . ($e->getCode()));
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
        
        try {
            $this->userHandler->removeUser('testUser42');
        } catch (\Exception $e) {
            // Do nothing
        }

        unset($this->userHandler);
        unset($this->connection);
    }
}
