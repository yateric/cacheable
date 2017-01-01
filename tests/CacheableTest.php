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

use Yateric\Cacheable\CacheDecorator;
use Yateric\Tests\Stubs\DecoratedObject;

class CacheableTest extends TestCase
{
    public function test_it_can_get_the_cache_decorator()
    {
        $decoratedObject = new DecoratedObject;

        $cacheDecorator = $decoratedObject->cache();

        $this->assertInstanceOf(CacheDecorator::class, $cacheDecorator);
        $this->assertEquals('nonstatic', $cacheDecorator->nonStaticMethod());
    }

    public function test_it_can_get_the_static_cache_decorator()
    {
        $cacheDecorator = DecoratedObject::cacheStatic();

        $this->assertInstanceOf(CacheDecorator::class, $cacheDecorator);
        $this->assertEquals('static', $cacheDecorator->staticMethod());
    }

    public function test_it_can_pass_the_cache_minutes_to_cache_decorator()
    {
        $decoratedObject = new DecoratedObject;

        $cacheDecorator = $decoratedObject->cache(123);

        $this->assertEquals(123, $cacheDecorator->getCacheMinutes());
    }
}