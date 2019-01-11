<?php
/**
 * ArangoDB PHP client testsuite
 * File: ViewTest.php
 *
 * @package ArangoDBClient
 * @author  Jan Steemann
 */

namespace ArangoDBClient;

/**
 * Class ViewTest
 * Basic Tests for the View API implementation
 *
 * @property Connection        $connection
 *
 * @package ArangoDBClient
 */
class ViewTest extends
    \PHPUnit_Framework_TestCase
{
    protected static $testsTimestamp;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        static::$testsTimestamp = str_replace('.', '_', (string) microtime(true));
    }


    public function setUp()
    {
        $this->connection  = getConnection();
        $this->viewHandler = new ViewHandler($this->connection);
    }

    /**
     * Test creation of view
     */
    public function testCreateViewObject()
    {
        $this->view = new View('View1' . '_' . static::$testsTimestamp, 'arangosearch');
        static::assertNull($this->view->getId());
        static::assertEquals('View1' . '_' . static::$testsTimestamp, $this->view->getName());
        static::assertEquals('arangosearch', $this->view->getType());
    }

    /**
     * Test creation of view
     */
    public function testCreateView()
    {
        $this->view = new View('View1' . '_' . static::$testsTimestamp, 'arangosearch');
        $result = $this->viewHandler->createView($this->view);
        static::assertEquals('View1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('arangosearch', $result['type']);
    }
    
    /**
     * Test getting a view
     */
    public function testGetView()
    {
        $this->view = new View('View1' . '_' . static::$testsTimestamp, 'arangosearch');
        $this->viewHandler->createView($this->view);

        $result = $this->viewHandler->getView('View1' . '_' . static::$testsTimestamp, 'arangosearch');
        static::assertEquals('View1' . '_' . static::$testsTimestamp, $result->getName());
        static::assertEquals('arangosearch', $result->getType());
    }
    
    /**
     * Test getting a non-existing view
     */
    public function testGetNonExistingView()
    {
        try {
            $this->viewHandler->getView('View1' . '_' . static::$testsTimestamp, 'arangosearch');
        } catch (\Exception $exception) {
        }
        static::assertEquals(404, $exception->getCode());
    }
    
    /**
     * Test view properties
     */
    public function testViewProperties()
    {
        $this->view = new View('View1' . '_' . static::$testsTimestamp, 'arangosearch');
        $result = $this->viewHandler->createView($this->view);
        static::assertEquals('View1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('arangosearch', $result['type']);

        $result = $this->viewHandler->properties($this->view);
        static::assertEquals([], $result['links']);
    }
    
    
    /**
     * Test set view properties
     */
    public function testViewSetProperties()
    {
        $this->view = new View('View1' . '_' . static::$testsTimestamp, 'arangosearch');
        $result = $this->viewHandler->createView($this->view);
        static::assertEquals('View1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('arangosearch', $result['type']);

        $properties = [
            'links' => [
                '_graphs' => [ 'includeAllFields' => true ]
            ]
        ];
        $result = $this->viewHandler->setProperties($this->view, $properties);
        static::assertEquals('arangosearch', $result['type']);
        static::assertTrue($result['links']['_graphs']['includeAllFields']);
        static::assertEquals([], $result['links']['_graphs']['fields']);
    }
    
    /**
     * Test drop view
     */
    public function testDropView()
    {
        $this->view = new View('View1' . '_' . static::$testsTimestamp, 'arangosearch');
        $this->viewHandler->createView($this->view);
        $result = $this->viewHandler->dropView('View1' . '_' . static::$testsTimestamp);
        static::assertTrue($result);
    }
    
    /**
     * Test drop non-existing view
     */
    public function testDropNonExistingView()
    {
        try {
            $this->viewHandler->dropView('View1' . '_' . static::$testsTimestamp);
        } catch (\Exception $exception) {
        }
        static::assertEquals(404, $exception->getCode());
    }

    public function tearDown()
    {
        $this->viewHandler = new ViewHandler($this->connection);
        try {
            $this->viewHandler->dropView('View1' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->viewHandler->dropView('View2' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
    }
}
