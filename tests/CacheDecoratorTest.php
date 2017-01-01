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
use Illuminate\Contracts\Cache\Store;
use Yateric\Cacheable\CacheDecorator;
use Yateric\Cacheable\Exceptions\CacheStoreException;
use Yateric\Cacheable\Exceptions\NotObjectException;
use Yateric\Tests\Stubs\DecoratedObject;

class CacheDecoratorTest extends TestCase
{
    public function test_it_throw_exception_if_wrapped_object_is_not_an_object()
    {
        try {
            new CacheDecorator('NonExistingClass');
        } catch (NotObjectException $e) {
            new CacheDecorator(new DecoratedObject);
            new CacheDecorator(DecoratedObject::class);
            return;
        }

        $this->fail('It should throw the NotObjectException if the wrapped object is not an object.');
    }

    public function test_it_can_get_runtime_cache_minutes()
    {
        $decoratedObject = new DecoratedObject;
        $cacheDecorator = $decoratedObject->cache(123);

        $this->assertEquals(123, $cacheDecorator->getCacheMinutes());
    }

    public function test_runtime_cache_minutes_will_be_flushed_after_a_method_call()
    {
        $decoratedObject = new DecoratedObject;
        $cacheDecorator = $decoratedObject->cache();

        $cacheDecorator->setCacheMinutes(123);
        $cacheMinutesBeforeCall = $cacheDecorator->getCacheMinutes();

        $cacheDecorator->nonStaticMethod();
        $cacheMinutesAfterCall = $cacheDecorator->getCacheMinutes();

        $this->assertEquals(123, $cacheMinutesBeforeCall);
        // Default global cache minutes is 60
        $this->assertEquals(60, $cacheMinutesAfterCall);
    }

    public function test_it_can_get_instance_default_cache_minutes()
    {
        $decoratedObject = new DecoratedObject;
        $cacheDecorator = $decoratedObject->cache();

        $cacheDecorator->setDefaultCacheMinutes(123);

        $this->assertEquals(123, $cacheDecorator->getCacheMinutes());
    }

    public function test_it_can_get_global_cache_minutes()
    {
        $decoratedObject = new DecoratedObject;
        $cacheDecorator = $decoratedObject->cache();

        CacheDecorator::setGlobalCacheMinutes(123);

        $this->assertEquals(123, $cacheDecorator->getCacheMinutes());
    }

    public function test_it_throw_exception_if_cache_store_not_implement_store_contract()
    {
        try {
            CacheDecorator::setCacheStore('DummyCacheStore');
        } catch (CacheStoreException $e) {
            CacheDecorator::setCacheStore(new ArrayStore);
            return;
        }

        $this->fail('It should throw the CacheStoreException if the cache store is not implementing [' . Store::class . '] contract.');
    }

    public function test_it_can_set_cache_prefix()
    {
        $arrayStore = new ArrayStore;
        CacheDecorator::setCacheStore($arrayStore);
        CacheDecorator::setCachePrefix('prefix_');

        $decoratedObject = new DecoratedObject;
        $cacheDecorator = $decoratedObject->cache();

        $nonStaticMethodReturn = $cacheDecorator->nonStaticMethod();

        $this->assertEquals($nonStaticMethodReturn, $arrayStore->get(
            'prefix_' . hash('sha256', 'Yateric\Tests\Stubs\DecoratedObjectnonStaticMethod[]')
        ));
    }

    public function test_it_can_cache_the_wrapped_object_method_return()
    {
        $arrayStore = new ArrayStore;
        CacheDecorator::setCacheStore($arrayStore);

        $decoratedObject = new DecoratedObject;
        $cacheDecorator = $decoratedObject->cache();
        $staticCacheDecorator = DecoratedObject::cacheStatic();

        $nonStaticMethodReturn = $cacheDecorator->nonStaticMethod();
        $nonStaticMethodWithParameterReturn = $cacheDecorator->nonStaticMethod('test');
        $staticMethodReturn = $staticCacheDecorator->staticMethod();
        $staticMethodWithParameterReturn = $staticCacheDecorator->staticMethod('test');

        $this->assertEquals($nonStaticMethodReturn, $arrayStore->get(hash('sha256',
            'Yateric\Tests\Stubs\DecoratedObjectnonStaticMethod[]'
        )));
        $this->assertEquals($nonStaticMethodWithParameterReturn, $arrayStore->get(hash('sha256',
            'Yateric\Tests\Stubs\DecoratedObjectnonStaticMethod["test"]'
        )));
        $this->assertEquals($staticMethodReturn, $arrayStore->get(hash('sha256',
            'Yateric\Tests\Stubs\DecoratedObjectstaticMethod[]'
        )));
        $this->assertEquals($staticMethodWithParameterReturn, $arrayStore->get(hash('sha256',
            'Yateric\Tests\Stubs\DecoratedObjectstaticMethod["test"]'
        )));
    }
}