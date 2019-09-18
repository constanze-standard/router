<?php

/**
 * JUST FOR TEST!
 */

use ConstanzeStandard\Route\Collector;
use ConstanzeStandard\Route\Dispatcher;

require __DIR__ . '/../vendor/autoload.php';

$collection = new Collector([
    // 'withCache' => __DIR__ . '/cache_file.php'
]);

$collection->attach('get', '/a/{name|[a-z]+}/{age|\d+}', 'controller1', ['a' => 1, 'b' => 2, 'c' => 3]);
$collection->attach('get', '/a/{name|\d+}', 'controller2', ['a' => 1, 'b' => 2, 'c' => 3]);
$collection->attach('get', '/a/{name|[sad]+}', 'controller3', ['a' => 1, 'b' => 2, 'c' => 3]);
$collection->attach('get', '/b/{name}', 'controller4', ['a' => 1, 'b' => 2, 'c' => 3]);

// $result = $collection->getRoutesByData(['a' => 1, 'b' => 2]);
// echo json_encode($result);

$matcher = new Dispatcher($collection);
$result = $matcher->dispatch('get', '/a/asd/23');
print_r($result);
