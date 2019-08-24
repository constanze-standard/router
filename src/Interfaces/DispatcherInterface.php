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

interface DispatcherInterface
{
    /**
     * Match all data.
     *
     * @param string $rawString
     * @return array|null
     */
    public function dispatch(string $rawMethod, string $rawString);
}
