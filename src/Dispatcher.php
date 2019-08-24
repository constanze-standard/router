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
use ConstanzeStandard\Route\Interfaces\DispatcherInterface;

/**
 * The route dispatcher.
 * 
 * @author Speed Sonic <blldxt@gmail.com>
 */
class Dispatcher implements DispatcherInterface
{
    const STATUS_OK = 1;
    const STATUS_ERROR = 2;

    const ERROR_ALLOWED_METHODS = 1;
    const ERROR_NOT_FOUND = 2;

    /**
     * The group chunk size.
     * 
     * @var int
     */
    private $chunkSize = 10;

    /**
     * Data collection
     *
     * @var CollectionInterface
     */
    protected $collection;

    /**
     * Construct Matcher.
     *
     * @param CollectionInterface $collection
     */
    public function __construct(CollectionInterface $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Match all data.
     * [status, handler, data, params]
     * [status, errorType, ?allowedMethods]
     *
     * @param string $rawString
     * @return array
     */
    public function dispatch(string $rawMethod, string $rawString)
    {
        $allowedMethods = [];
        $rawString = \urldecode($rawString);
        foreach ($this->collection->getStaticMap() as $method => $staticMap) {
            foreach ($staticMap as list($pattern, $handlerId, $data, $variables)) {
                if ($pattern == $rawString) {
                    if (strcasecmp($method, $rawMethod) === 0) {
                        $handler = $this->collection->getHanlderById($handlerId);
                        return [static::STATUS_OK, $handler, $data, []];
                    }
                    $allowedMethods[] = $method;
                }
            }
        }

        foreach ($this->collection->getVariableMap() as $method => $variablesMap) {
            $chunkVariableMap = \array_chunk($variablesMap, $this->chunkSize);
            foreach ($chunkVariableMap as $variableMap) {
                list($regex, $map) = $this->getRegexAndMap($variableMap);
                if (preg_match($regex, $rawString, $matches)) {
                    if (strcasecmp($method, $rawMethod) === 0) {
                        $variableData = $map[count($matches)];
                        array_shift($matches);
                        $params = $this->mergeVariablesAndValues($variableData[3], $matches);
                        $handler = $this->collection->getHanlderById($variableData[1]);
                        return [static::STATUS_OK, $handler, $variableData[2], $params];
                    }
                    $allowedMethods[] = $method;
                }
            }
        }

        if (!empty($allowedMethods)) {
            return [static::STATUS_ERROR, static::ERROR_ALLOWED_METHODS, $allowedMethods];
        }

        return [static::STATUS_ERROR, static::ERROR_NOT_FOUND];
    }

    /**
     * Merge variables and values.
     *
     * @param array $variables
     * @param array $values
     * @return array
     */
    private function mergeVariablesAndValues($variables, $values)
    {
        $params = [];
        foreach ($variables as $index => $variable) {
            $params[$variable] = $values[$index];
        }
        return $params;
    }

    /**
     * Merge all variable data patterns in one pattern.
     * This part has been referred to nikic/fast-route
     *
     * @return string
     * 
     * @see https://github.com/nikic/FastRoute/blob/master/src/DataGenerator/GroupCountBased.php
     */
    private function getRegexAndMap($variableMap)
    {
        $patterns = [];
        $map = [];
        $numGroups = 0;

        foreach ($variableMap as $variableData) {
            $numVariables = count($variableData[3]);
            $numGroups = max($numGroups, $numVariables);
            $patterns[] = $variableData[0] . str_repeat('()', $numGroups - $numVariables);
            $map[$numGroups + 1] = $variableData;
            ++$numGroups;
        }

        $pattern = implode('|', $patterns);
        $regex = preg_replace('/{[^\/]+}/', '([^/]+)', '~^(?|' . $pattern . ')$~');
        return [$regex, $map];
    }
}
