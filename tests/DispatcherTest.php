<?php

use ConstanzeStandard\Route\Collector;
use ConstanzeStandard\Route\Dispatcher;
use ConstanzeStandard\Route\MatcherResult;

require_once __DIR__ . '/AbstractTest.php';

class DispatcherTest extends AbstractTest
{
    public function testContruct()
    {
        $collection = new Collector();
        $matcher = new Dispatcher($collection);
        $matcherCollection = $this->getProperty($matcher, 'collection');
        $this->assertEquals($collection, $matcherCollection);
    }

    public function testMatchVariablesFound()
    {
        $collection = new Collector();
        $collection->attach('get', '/variable/{name}', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('get', '/variable/testname');
        $this->assertEquals($result, [Dispatcher::STATUS_OK, 'ctrl', 'data', ['name' => 'testname']]);
    }

    public function testMatchVariablesNotFound()
    {
        $collection = new Collector();
        $collection->attach('get', '/variable/{name}', 'data', [1]);
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('get', '/unknow/testname');
        $this->assertEquals($result, [
            Dispatcher::STATUS_ERROR,
            Dispatcher::ERROR_NOT_FOUND
        ]);
    }

    public function testMatchStaticFound()
    {
        $collection = new Collector();
        $collection->attach('get', '/static', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('get', '/static');
        $this->assertEquals($result, [
            Dispatcher::STATUS_OK,
            'ctrl', 'data', []
        ]);
    }

    public function testMatchStaticNotFound()
    {
        $collection = new Collector();
        $collection->attach('get', '/static', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('get', '/unknow');
        $this->assertEquals($result, [
            Dispatcher::STATUS_ERROR,
            Dispatcher::ERROR_NOT_FOUND
        ]);
    }

    public function testMatchVariablesFoundWithWrongParam()
    {
        $collection = new Collector();
        $collection->attach('get', '/variable/{name1}/{name2', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('get', '/variable/testName111/{name2');
        $this->assertEquals($result, [
            Dispatcher::STATUS_OK,
            'ctrl', 'data', ['name1' => 'testName111']
        ]);
    }

    public function testMatchVariablesFoundWithUrlencodeString()
    {
        $collection = new Collector();
        $url = urlencode('/this is a test!/test param!');
        $collection->attach('get', '/this is a test!/{this param!}', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('get', $url);
        $this->assertEquals($result, [
            Dispatcher::STATUS_OK,
            'ctrl', 'data', ['this param!' => 'test param!']
        ]);
    }

    public function testMatchVariablesFoundWithUnicodeString()
    {
        $collection = new Collector();
        $url = urlencode('/this is a test!/这是一个测试');
        $collection->attach('get', '/this is a test!/{this param!}', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('get', $url);
        $this->assertEquals($result, [
            Dispatcher::STATUS_OK,
            'ctrl', 'data', ['this param!' => '这是一个测试']
        ]);
    }

    public function testMatchVariablesFoundWithOutofChunk()
    {
        $collection = new Collector();
        $url = urlencode('/c/hh');
        $collection->attach('get', '/a/{name}', 'ctrl', 'data');
        $collection->attach('get', '/b/{name}', 'ctrl', 'data');
        $collection->attach('get', '/c/{name}', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $this->setProperty($matcher, 'chunkSize', 2);
        $result = $matcher->dispatch('get', $url);
        $this->assertEquals($result, [
            Dispatcher::STATUS_OK,
            'ctrl', 'data', ['name' => 'hh']
        ]);
    }

    public function testStaticMethodNotAllowed()
    {
        $collection = new Collector();
        $collection->attach('get', '/static', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('post', '/static');
        $this->assertEquals($result, [
            Dispatcher::STATUS_ERROR,
            Dispatcher::ERROR_METHOD_NOT_ALLOWED,
            ['get']
        ]);
    }

    public function testVariableMethodNotAllowed()
    {
        $collection = new Collector();
        $collection->attach('get', '/variable/{name}', 'ctrl', 'data');
        $matcher = new Dispatcher($collection);
        $result = $matcher->dispatch('post', '/variable/{name}');
        $this->assertEquals($result, [
            Dispatcher::STATUS_ERROR,
            Dispatcher::ERROR_METHOD_NOT_ALLOWED,
            ['get']
        ]);
    }

    public function testRegexVariableDifine()
    {
        $collection = new Collector();
        $url = urlencode('/b/123/abc');
        $collection->attach('get', '/a/{name|\w+}/abc', 'ctrl1', 'data1');
        $collection->attach('get', '/b/{age|\d+}/{name|[a-c]+}', 'ctrl2', 'data2');
        $matcher = new Dispatcher($collection);
        $this->setProperty($matcher, 'chunkSize', 2);
        $result = $matcher->dispatch('get', $url);
        $this->assertEquals($result, [
            Dispatcher::STATUS_OK,
            'ctrl2', 'data2', ['age' => '123', 'name' => 'abc']
        ]);
    }
}
