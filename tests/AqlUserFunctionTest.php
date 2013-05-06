<?php
/**
 * ArangoDB PHP client testsuite
 * File: AqlUserFunction.php
 *
 * @package ArangoDbPhpClient
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class AqlUserFunctionTest
 * Basic Tests for the Graph API implementation
 *
 * @property Connection        $connection
 * @package triagens\ArangoDb
 */
class AqlUserFunctionTest extends
    \PHPUnit_Framework_TestCase
{
    /**
     * Unittest setup
     */
    public function setUp()
    {
        $this->connection = getConnection();

        // clean up first
        try {
            $response = $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }
    }


    /**
     * Test if AqlUserFunctions can be registered, listed and unregistered
     */
    public function testRegisterListAndUnregisterAqlUserFunctionWithInitialConfig()
    {

        $name = 'myFunctions:myFunction';
        $code = 'function (celsius) { return celsius * 1.8 + 32; }';

        $array = array(
            'name' => $name,
            'code' => $code
        );

        $userFunction = new AqlUserFunction($this->connection, $array);

        $result = $userFunction->register();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );
        $list = $userFunction->getRegisteredUserFunctions();

        $this->assertCount(1, $list, 'List returned did not return expected 1 attribute');
        $this->assertTrue(
            $list[0]['name'] == $name && $list[0]['code'] == $code,
            'did not return expected Function. Instead returned: ' . $list[0]['name'] . ' and ' . $list[0]['code']
        );

        $result = $userFunction->unregister();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );
    }


    /**
     * Test if AqlUserFunctions can be registered, listed and unregistered with getters and setters
     */
    public function testRegisterListAndUnregisterAqlUserFunctionWithGettersAndSetters()
    {
        $name = 'myFunctions:myFunction';
        $code = 'function (celsius) { return celsius * 1.8 + 32; }';

        $userFunction = new AqlUserFunction($this->connection);
        $userFunction->setName($name);
        $userFunction->setCode($code);

        // check if getters work fine

        $this->assertTrue(
            $userFunction->getName() == $name,
            'Did not return name, instead returned: ' . $userFunction->getName()
        );
        $this->assertTrue(
            $userFunction->getCode() == $code,
            'Did not return code, instead returned: ' . $userFunction->getCode()
        );


        $result = $userFunction->register();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );
        $list = $userFunction->getRegisteredUserFunctions();
        $this->assertCount(1, $list, 'List returned did not return expected 1 attribute');
        $this->assertTrue(
            $list[0]['name'] == $name && $list[0]['code'] == $code,
            'did not return expected Function. Instead returned: ' . $list[0]['name'] . ' and ' . $list[0]['code']
        );

        $result = $userFunction->unregister();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );
    }


    /**
     * Test if AqlUserFunctions can be registered, listed and unregistered
     */
    public function testRegisterListAndUnregisterAqlUserFunctionWithWithMagicSettersAndGetters()
    {

        $name = 'myFunctions:myFunction';
        $code = 'function (celsius) { return celsius * 1.8 + 32; }';


        $userFunction = new AqlUserFunction($this->connection);

        $userFunction->name = $name;
        $userFunction->code = $code;

        // check if getters work fine
        $this->assertTrue(
            $userFunction->name == $name,
            'Did not return name, instead returned: ' . $userFunction->name
        );
        $this->assertTrue(
            $userFunction->code == $code,
            'Did not return code, instead returned: ' . $userFunction->code
        );

        $result = $userFunction->register();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );
        $list = $userFunction->getRegisteredUserFunctions();
        $this->assertCount(1, $list, 'List returned did not return expected 1 attribute');
        $this->assertTrue(
            $list[0]['name'] == $name && $list[0]['code'] == $code,
            'did not return expected Function. Instead returned: ' . $list[0]['name'] . ' and ' . $list[0]['code']
        );

        $result = $userFunction->unregister();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );
    }


    /**
     * Test if AqlUserFunctions can be registered, listed and unregistered
     *
     */
    public function testReRegisterListAndUnregisterAqlUserFunctionTwice()
    {

        $name = 'myFunctions:myFunction';
        $code = 'function (celsius) { return celsius * 1.8 + 32; }';


        $userFunction = new AqlUserFunction($this->connection);

        $userFunction->name = $name;
        $userFunction->code = $code;

        $result = $userFunction->register();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );

        $result = $userFunction->register();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );

        $list = $userFunction->getRegisteredUserFunctions();
        $this->assertCount(1, $list, 'List returned did not return expected 1 attribute');
        $this->assertTrue(
            $list[0]['name'] == $name && $list[0]['code'] == $code,
            'did not return expected Function. Instead returned: ' . $list[0]['name'] . ' and ' . $list[0]['code']
        );

        $result = $userFunction->unregister();

        $this->assertTrue(
            $result['error'] == false,
            'result[\'error\'] Did not return false, instead returned: ' . print_r($result, 1)
        );


        try {
            $result = $userFunction->unregister();
        } catch (Exception $e) {
        }
        $details = $e->getDetails();

        $this->assertTrue(
            $e->getCode() == 404,
            'Did not return code 404, instead returned: ' . $e->getCode()
        );
    }


    /**
     * Unittest teardown
     */
    public function tearDown()
    {

        unset($this->connection);
    }
}
