<?php

/**
 * This file is part of Cacheable.
 *
 * (c) Eric Chow <yateric@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yateric\Tests;

use Illuminate\Cache\ArrayStore;
use PHPUnit_Framework_TestCase;
use Yateric\Cacheable\CacheDecorator;

class TestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        CacheDecorator::setCacheStore(new ArrayStore());
    }

    public function tearDown()
    {
        CacheDecorator::setCachePrefix('');
        CacheDecorator::setGlobalCacheMinutes(60);
        CacheDecorator::flush();
    }
}