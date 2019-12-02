<?php
/**
 * ArangoDB PHP client testsuite
 * File: AnalyzerTest.php
 *
 * @package ArangoDBClient
 * @author  Jan Steemann
 */

namespace ArangoDBClient;

/**
 * Class AnalyzerTest
 * Basic Tests for the analyzer API implementation
 *
 * @property Connection        $connection
 *
 * @package ArangoDBClient
 */
class AnalyzerTest extends
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
        $this->analyzerHandler = new AnalyzerHandler($this->connection);
    }
    
    /**
     * Test creation of analyzer
     */
    public function testCreateAnalyzerObject()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $analyzer->getName());
        static::assertEquals('text', $analyzer->getType());
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ],$analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }

    /**
     * Test creation of identity analyzer
     */
    public function testCreateIdentityAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'identity');
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('identity', $result['type']);
        static::assertEquals([],$analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test creation of text analyzer
     */
    public function testCreateTextAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('text', $result['type']);
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ],$analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test creation of text analyzer
     */
    public function testCreateTextAnalyzerFail()
    {
        try {
            $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'text');
            $this->analyzerHandler->create($analyzer);
        } catch (\Exception $exception) {
        }
        static::assertEquals(400, $exception->getCode());
    }
    
    /**
     * Test getting an analyzer
     */
    public function testGetAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        $this->analyzerHandler->create($analyzer);

        $result = $this->analyzerHandler->get('Analyzer1' . '_' . static::$testsTimestamp);
        static::assertEquals('_system::Analyzer1' . '_' . static::$testsTimestamp, $result->getName());
        static::assertEquals('text', $result->getType());
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ],$analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test getting default analyzers
     */
    public function testGetDefaultAnalyzers()
    {
        $result = $this->analyzerHandler->getAll();
        static::assertFalse($result['error']);

        $analyzers = $result['result'];
        static::assertTrue(count($analyzers) > 0);

        $found = [];
        foreach ($analyzers as $analyzer) {
          $name = $analyzer['name'];
          $found[] = $name;
        }

        static::assertTrue(in_array('text_fi', $found));
        static::assertTrue(in_array('text_ru', $found));
        static::assertTrue(in_array('text_de', $found));
        static::assertTrue(in_array('text_en', $found));
        static::assertTrue(in_array('text_pt', $found));
        static::assertTrue(in_array('text_nl', $found));
        static::assertTrue(in_array('text_fr', $found));
        static::assertTrue(in_array('text_zh', $found));
    }
    
    /**
     * Test getting all analyzers
     */
    public function testGetAllAnalyzers()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'identity');
        $this->analyzerHandler->create($analyzer);
        
        $analyzer = new Analyzer('Analyzer2' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        $this->analyzerHandler->create($analyzer);

        $result = $this->analyzerHandler->getAll();
        static::assertFalse($result['error']);

        $analyzers = $result['result'];
        static::assertTrue(count($analyzers) > 0);

        $found = [];
        foreach ($analyzers as $analyzer) {
          $name = $analyzer['name'];
          $found[] = $name;
        }

        static::assertTrue(in_array('_system::Analyzer1' . '_' . static::$testsTimestamp, $found));
        static::assertTrue(in_array('_system::Analyzer2' . '_' . static::$testsTimestamp, $found));
    }
    
    /**
     * Test getting a non-existing analyzer
     */
    public function testGetNonExistingAnalyzer()
    {
        try {
            $this->analyzerHandler->get('Analyzer1' . '_' . static::$testsTimestamp);
        } catch (\Exception $exception) {
        }
        static::assertEquals(404, $exception->getCode());
    }
    
    /**
     * Test analyzer properties
     */
    public function testAnalyzerProperties()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'identity');
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('identity', $result['type']);
        static::assertEquals([], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());

        $result = $this->analyzerHandler->properties($analyzer);
        static::assertEquals('_system::Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('identity', $result['type']);
        static::assertEquals([], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
        
        $analyzer = new Analyzer('Analyzer2' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer2' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('text', $result['type']);
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ],$analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
        
        $result = $this->analyzerHandler->properties($analyzer);
        static::assertEquals('_system::Analyzer2' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('text', $result['type']);
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ],$analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test drop analyzer
     */
    public function testDropAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'identity');
        $this->analyzerHandler->create($analyzer);
        $result = $this->analyzerHandler->drop('Analyzer1' . '_' . static::$testsTimestamp);
        static::assertTrue($result);
    }
    
    /**
     * Test drop non-existing analyzer
     */
    public function testDropNonExistingAnalyzer()
    {
        try {
            $this->analyzerHandler->drop('Analyzer1' . '_' . static::$testsTimestamp);
        } catch (\Exception $exception) {
        }
        static::assertEquals(404, $exception->getCode());
    }
    
    public function tearDown()
    {
        $this->analyzerHandler = new AnalyzerHandler($this->connection);
        try {
            $this->analyzerHandler->drop('Analyzer1' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->analyzerHandler->drop('Analyzer2' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
    }
}
