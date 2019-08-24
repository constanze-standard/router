<?php

/**
 * JUST FOR TEST!
 */

use ConstanzeStandard\Route\Collector;
use ConstanzeStandard\Route\Dispatcher;

require __DIR__ . '/../vendor/autoload.php';

$collection = new Collector([
    'withCache' => __DIR__ . '/cache_file.php'
]);
$collection->attach('get', '/a', 'controller', ['a' => 1, 'b' => 2, 'c' => 3]);

// $result = $collection->getRoutesByData(['a' => 1, 'b' => 2]);
// echo json_encode($result);

$matcher = new Dispatcher($collection);
$result = $matcher->dispatch('get', '/a');
print_r($result);
