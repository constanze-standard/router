<?php

use ConstanzeStandard\Route\Collector;

require_once __DIR__ . '/AbstractTest.php';

class CollectionTest extends AbstractTest
{

    public function testAttachRouteVariable()
    {
        $collection = new Collector();
        $collection->attach('get', '/variable/{name}', 'ctrl', 'data');
        $value = $this->getProperty($collection, 'variableMap');
        $this->assertCount(1, $value);
        $this->assertEquals($value['get'][0], [
            '/variable/{name}',
            0,
            'data',
            ['name']
        ]);
    }

    public function testGetVariableRoutes()
    {
        $collection = new Collector();
        $this->setProperty($collection, 'variableMap', ['a']);
        $variableMap = $collection->getVariableMap();
        $this->assertEquals(['a'], $variableMap);
    }

    public function testGetStaticRoutes()
    {
        $collection = new Collector();
        $this->setProperty($collection, 'staticMap', ['a']);
        $staticMap = $collection->getStaticMap();
        $this->assertEquals(['a'], $staticMap);
    }

    public function testAttachStaticWithMathods()
    {
        $collection = new Collector();
        $collection->attach(['get', 'post'], '/a', 'ctrl', 'data');
        $staticMap = $this->getProperty($collection, 'staticMap');
        $this->assertEquals($staticMap['get'], [['/a', 0, 'data', false]]);
        $this->assertEquals($staticMap['post'], [['/a', 0, 'data', false]]);
    }

    public function testAttachVaribleWithMathods()
    {
        $collection = new Collector();
        $collection->attach(['get', 'post'], '/a/{name}', 'ctrl', 'data');
        $variableMap = $this->getProperty($collection, 'variableMap');
        $this->assertEquals($variableMap['get'], [['/a/{name}', 0, 'data', ['name']]]);
        $this->assertEquals($variableMap['post'], [['/a/{name}', 0, 'data', ['name']]]);
    }

    /**
     * @expectedException \Exception
     */
    public function testAttachException()
    {
        $collection = new Collector();
        $collection->attach(null, '/a/{name}', 'ctrl', 'data');
    }

    public function testCreateCache()
    {
        $cacheName = __DIR__ . '/cache_test.php';
        $collection = new Collector();
        $collection->attach('post', '/b', 'ctrl', 'data');
        $collection->attach('post', '/variable/{name}', 'ctrl', 'data');
        $result = $collection->createCache($cacheName);
        list($statics, $vars) = require($cacheName);
        $this->assertEquals($statics['post'][0], ['/b', 0, 'data', false]);
        $this->assertEquals($vars['post'][0], ['/variable/{name}', 1, 'data', ['name']]);
        $this->assertTrue($result);
        unlink($cacheName);
    }

    public function testLoadCache()
    {
        $cacheName = __DIR__ . '/cache_test.php';
        $collection = new Collector();
        $collection->attach('get', '/b', 'ctrl', 'data');
        $collection->attach('get', '/variable/{name}', 'ctrl', 'data');
        $collection->createCache($cacheName);

        $collection2 = new Collector();
        $collection2->loadCache($cacheName);

        $staticMap = $this->getProperty($collection2, 'staticMap');
        $variableMap = $this->getProperty($collection2, 'variableMap');

        $this->assertEquals($staticMap, ['get' => [['/b', 0, 'data', false]]]);
        $this->assertEquals($variableMap, ['get' => [['/variable/{name}', 1, 'data', ['name']]]]);

        unlink($cacheName);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLoadCacheWithException()
    {
        $cacheName = __DIR__ . '/cache_test.php';
        $collection = new Collector();
        $collection->loadCache($cacheName);
    }

    public function testGetRoutesByData()
    {
        $collection = new Collector();
        $collection->attach('get', '/a', 'ctrl1', ['a' => 1, 'b' => 2, 'c' => 3]);
        $collection->attach('get', '/b/{id}', 'ctrl2', ['a' => 1, 'b' => 2, 'c' => 4]);
        $collection->attach('get', '/c', 'ctrl', ['a' => 1, 'c' => 2, 'd' => 3]);

        $result = $collection->getRoutesByData(['a' => 1, 'b' => 2]);
        $this->assertEquals([
            ["/a", 'ctrl1', ["a" => 1,"b" => 2,"c" => 3],false],
            ["/b/{id}", 'ctrl2', ["a" => 1,"b" => 2,"c" => 4],["id"]]
        ], $result);
    }

    public function testGetRoutesByDataFirst()
    {
        $collection = new Collector();
        $collection->attach('get', '/a', 'ctrl', ['a' => 1, 'b' => 2, 'c' => 3]);
        $collection->attach('get', '/b/{id}', 'ctrl', ['a' => 1, 'b' => 2, 'c' => 4]);
        $collection->attach('get', '/c', 'ctrl', ['a' => 1, 'c' => 2, 'd' => 3]);

        $result = $collection->getRoutesByData(['a' => 1, 'b' => 2], true);
        $this->assertEquals(["/a", 'ctrl', ["a" => 1,"b" => 2,"c" => 3],false], $result);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetHanlderById()
    {
        $collection = new Collector();
        $collection->getHanlderById(100);
    }

    public function testAttachWithCacheCreate()
    {
        $cacheFile = __DIR__ . '/cache_test.php';
        $collection = new Collector([
            'withCache' => $cacheFile
        ]);
        $collection->attach('get', '/a', 'ctrl', 'data');
        $this->assertFileExists($cacheFile);
        unlink($cacheFile);
    }

    public function testAttachWithCacheLoad()
    {
        $cacheFile = __DIR__ . '/cache_test.php';
        $collection = new Collector([
            'withCache' => $cacheFile
        ]);
        $collection->attach('get', '/a', 'ctrl', 'data');

        $collection2 = new Collector([
            'withCache' => $cacheFile
        ]);
        $collection2->attach('get', '/a', 'ctrl', 'data2');
        $staticMap = $this->getProperty($collection2, 'staticMap');
        $this->assertEquals([
            'get' => [
                ['/a', 0, 'data', false]
            ]
        ], $staticMap);
        unlink($cacheFile);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateCacheNotWritable()
    {
        $collection = new Collector();
        $collection->createCache(__DIR__ . '/nothin/aa.php');
    }
}
