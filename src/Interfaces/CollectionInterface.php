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
