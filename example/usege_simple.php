<?php
use ConstanzeStandard\Route\Matcher;
use ConstanzeStandard\Route\Collection;

require __DIR__ . '/../vendor/autoload.php';

$collection = new Collection();
$collection->attach('GET', '/user/{name}', 'static data');

$matcher = new Matcher($collection);
$result = $matcher->match('get', '/user/Alice');

if ($result) {
    print_r($result);
}
