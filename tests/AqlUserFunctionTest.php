<?php
/**
 * ArangoDB PHP client testsuite
 * File: AqlUserFunction.php
 *
 * @package triagens\ArangoDb
 * @author  Frank Mayer
 */

namespace triagens\ArangoDb;

/**
 * Class AqlUserFunctionTest
 * Basic Tests for the Graph API implementation
 *
 * @property Connection                    $connection
 *
 * @property CollectionHandler             collectionHandler
 *
 * @package triagens\ArangoDb
 */
class AqlUserFunctionTest extends
    \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = getConnection();

        // clean up first
        try {
            $this->collectionHandler->delete('ArangoDB_PHP_TestSuite_TestCollection_01');
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
     * Test if AqlUserFunctions can be registered, listed and unregistered using the register() shortcut method
     */
    public function testRegisterListAndUnregisterAqlUserFunctionUsingShortcut()
    {

        $name = 'myFunctions:myFunction';
        $code = 'function (celsius) { return celsius * 1.8 + 32; }';

        $userFunction = new AqlUserFunction($this->connection);

        $result = $userFunction->register($name, $code);

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

        $e = null;
        try {
            $userFunction->unregister();
        } catch (Exception $e) {
        }

        $this->assertTrue(
            $e->getCode() == 404,
            'Did not return code 404, instead returned: ' . $e->getCode()
        );
    }

    public function testGetAQLFunctionsWithNamespaceFilter()
    {

        $name1 = 'myFunctions:myFunction';
        $name2 = 'myFunctions1:myFunction';
        $code = 'function (celsius) { return celsius * 1.8 + 32; }';

        //Setup
        $userFunction = new AqlUserFunction($this->connection);

        $userFunction->name = $name1;
        $userFunction->code = $code;

        $result = $userFunction->register();

        $userFunction = new AqlUserFunction($this->connection);

        $userFunction->name = $name2;
        $userFunction->code = $code;

        $result = $userFunction->register();

        $functions = $userFunction->getRegisteredUserFunctions('myFunctions');
        $this->assertCount(1, $functions, "myFunctions namespace should only contain 1 function.");

        $functions = $userFunction->getRegisteredUserFunctions('myFunctions1');
        $this->assertCount(1, $functions, "myFunctions namespace should only contain 1 function.");

        $userFunction->unregister($name1);
        $userFunction->unregister($name2);
    }

    public function tearDown()
    {

        unset($this->connection);
    }
}
