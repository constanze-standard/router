<?php

/*
 * This file is part of the constanze-standard-route package.
 *
 * (c) Speed Sonic <blldxt@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ConstanzeStandard\Route;

use ConstanzeStandard\Route\Interfaces\CollectionInterface;
use RuntimeException;

/**
 * Collector of route.
 * 
 * @author Speed Sonic <blldxt@gmail.com>
 */
class Collector implements CollectionInterface
{
    /**
     * Variable map
     *
     * @var array
     */
    public $variableMap = [];

    /**
     * Static map
     *
     * @var array
     */
    protected $staticMap = [];

    /**
     * Route handlers.
     * 
     * @var array
     */
    private $handlers = [];

    /**
     * count and increment handler id.
     * 
     * @var int
     */
    private $idCounter = 0;

    /**
     * Parse the pattern.
     * 
     * @param string $pattern
     * 
     * @return array|false
     */
    private static function parsePatternVariables($pattern)
    {
        $regex = '/{([^\/]+)}/';
        $matched = [];
        if (preg_match_all($regex, $pattern, $matched)) {
            return $matched[1];
        }
        return false;
    }

    /**
     * Set options.
     */
    public function __construct(array $options = [])
    {
        $this->options = $options + [
            'withCache' => false
        ];
    }

    /**
     * Get handler by id.
     * O(1)
     * 
     * @param int $id
     * 
     * @throws RuntimeException
     * 
     * @return \Closure|array|string
     */
    public function getHanlderById(int $id)
    {
        if (array_key_exists($id, $this->handlers)) {
            return $this->handlers[$id];
        }
        throw new RuntimeException('The handler id '. $id .' mismatch.');
    }

    /**
     * Attach data to collection.
     *
     * @param array|string $methods
     * @param string $pattern
     * @param \Closure|array|string $handler
     * @param mixed $data
     * 
     * @throws \InvalidArgumentException
     */
    public function attach($methods, string $pattern, $handler, $data)
    {
        $handlerId = $this->registerHandler($handler);
        $cacheFile = $this->options['withCache'];
        if ($this->options['withCache'] && file_exists($cacheFile)) {
            $this->loadCache($cacheFile);
        } else {
            $mapType = 'staticMap';
            if ($variables = static::parsePatternVariables($pattern)) {
                $mapType = 'variableMap';
            }

            $mapData = [$pattern, $handlerId, $data, $variables];
            if (is_array($methods)) {
                foreach ($methods as $method) {
                    if (! array_key_exists($method, $this->{$mapType})) {
                        $this->{$mapType}[$method] = [];
                    }
                    $this->{$mapType}[$method][] = $mapData;
                }
            } elseif (is_string($methods)) {
                if (! array_key_exists($methods, $this->{$mapType})) {
                    $this->{$mapType}[$methods] = [];
                }
                $this->{$mapType}[$methods][] = $mapData;
            } else {
                throw new \InvalidArgumentException(__CLASS__ . '::attach first parameter must be array or string and not empty.');
            }
            
            if ($this->options['withCache']) {
                $this->createCache($cacheFile);
            }
        }
    }

    /**
     * Get route from map by data.
     * O(n)
     * 
     * @param string|array $mapData
     * @param bool $isFirst
     * 
     * @return array
     */
    public function getRoutesByData($mapData, $isFirst = false)
    {
        $result = [];
        foreach ([$this->staticMap, $this->variableMap] as $map) {
            foreach ($map as $methods) {
                foreach ($methods as list($pattern, $handlerId, $data, $variables)) {
                    $matched = true;
                    foreach ((array) $mapData as $key => $value) {
                        if (! (isset($data[$key]) && (string)$data[$key] == $value)) {
                            $matched = false;
                            break;
                        }
                    }
                    if ($matched) {
                        $routeData = [
                            $pattern,
                            $this->getHanlderById($handlerId),
                            $data,
                            $variables
                        ];
                        if ($isFirst) {
                            return $routeData;
                        } else {
                            $result[] = $routeData;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get variable map.
     *
     * @return array
     */
    public function getVariableMap(): array
    {
        return $this->variableMap;
    }

    /**
     * Get static map.
     *
     * @return array
     */
    public function getStaticMap(): array
    {
        return $this->staticMap;
    }

    /**
     * Load cache data.
     * 
     * @param string $fileName
     */
    public function loadCache($cacheFile)
    {
        if (is_file($cacheFile) && is_readable($cacheFile)) {
            $content = require($cacheFile);
            list($this->staticMap, $this->variableMap) = unserialize($content);
        } else {
            throw new \RuntimeException('Cache file does not exist or not readable.');
        }
    }

    /**
     * Create the cache file by option `withCache`.
     * 
     * @param string $fileName
     * @return bool
     */
    public function createCache($cacheFile)
    {
        if (! is_writable(dirname($cacheFile))) {
            throw new \RuntimeException('Cache directory does not writable.');
        }
        $content = serialize([$this->staticMap, $this->variableMap]);
        $bytes = file_put_contents(
            $cacheFile,
            "<?php return '$content';"
        );
        return (bool)$bytes;
    }

    /**
     * Push a handler to array.
     * 
     * @param \Closure|array|string $handler
     * 
     * @return int the current id
     */
    private function registerHandler($handler): int
    {
        $id = $this->idCounter;
        $this->handlers[$id] = $handler;
        $this->idCounter++;
        return $id;
    }
}
