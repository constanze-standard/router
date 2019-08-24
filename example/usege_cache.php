<?php
use ConstanzeStandard\Route\Matcher;
use ConstanzeStandard\Route\Collection;

require __DIR__ . '/../vendor/autoload.php';

class Test
{
    public function aa()
    {
        return 1;
    }

    public static function __set_state($array)
    {
        return new static;
    }
}

$cacheName = 'cache_file';
$collection = new Collection();
if (is_file($cacheName . '.php')) {
    $collection->loadCache($cacheName);
} else {
    $collection->attach('get', '/user/{name}', new Test);
    $collection->putCache($cacheName);
}
