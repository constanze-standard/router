<?php

/*
 * This file is part of the constanze-standard-route package.
 *
 * (c) Speed Sonic <blldxt@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ConstanzeStandard\Route\Interfaces;

interface CollectionInterface
{
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
    public function attach($methods, string $pattern, $handler, $data);

    /**
     * Get route from map by data.
     * O(n)
     * 
     * @param string|array $mapData
     * @param bool $isFirst
     * 
     * @return array
     */
    public function getRoutesByData($mapData, $isFirst = false);

    /**
     * Get variable map.
     *
     * @return array
     */
    public function getVariableMap(): array;

    /**
     * Get static map.
     *
     * @return array
     */
    public function getStaticMap(): array;

    /**
     * Get handler by id.
     * 
     * @param int $id
     * 
     * @throws RuntimeException
     * 
     * @return \Closure|array|string
     */
    public function getHanlderById(int $id);
}
