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
        $this->assertEquals(CacheDecorator::GLOBAL_CACHE_MINUTES, $cacheMinutesAfterCall);
    }

    public function test_it_can_get_instance_default_cache_minutes()
    {
        $decoratedObject = new DecoratedObject;
        $cacheDecorator = $decoratedObject->cache();

        $cacheDecorator->setDefaultCacheMinutes(123);

        $this->assertEquals(123, $cacheDecorator->getCacheMinutes());
    }

    public function test_it_can_set_cache_prefix()
    {
        $this->assertEquals('', CacheDecorator::getCachePrefix());

        CacheDecorator::setCachePrefix('prefix');

        $this->assertEquals('prefix', CacheDecorator::getCachePrefix());
    }

    public function test_it_can_set_global_cache_minutes()
    {
        $this->assertEquals(60, CacheDecorator::getGlobalCacheMinutes());

        CacheDecorator::setGlobalCacheMinutes(123);

        $this->assertEquals(123, CacheDecorator::getGlobalCacheMinutes());
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

    public function test_it_use_the_correct_cache_key()
    {
        $arrayStore = new ArrayStore;
        CacheDecorator::setCacheStore($arrayStore);
        CacheDecorator::setCachePrefix('prefix_');

        $decoratedObject = new DecoratedObject;
        $cacheDecorator = $decoratedObject->cache();

        $cacheDecorator->nonStaticMethod();

        $this->assertNotEmpty($arrayStore->get(
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

    public function test_it_can_flush_the_cache_store()
    {
        $arrayStore = new ArrayStore;
        CacheDecorator::setCacheStore($arrayStore);

        $this->assertEmpty($arrayStore->get(hash('sha256',
            'Yateric\Tests\Stubs\DecoratedObjectnonStaticMethod[]'
        )));

        $decoratedObject = new DecoratedObject;
        $decoratedObject->cache()->nonStaticMethod();
        $this->assertNotEmpty($arrayStore->get(hash('sha256',
            'Yateric\Tests\Stubs\DecoratedObjectnonStaticMethod[]'
        )));


        CacheDecorator::flush();
        $this->assertEmpty($arrayStore->get(hash('sha256',
            'Yateric\Tests\Stubs\DecoratedObjectnonStaticMethod[]'
        )));
    }

    public function test_it_can_reset_cache_decorator_static_states()
    {
        CacheDecorator::setCachePrefix('test_prefix');
        CacheDecorator::setGlobalCacheMinutes(123);

        CacheDecorator::reset();

        $this->assertEquals('', CacheDecorator::getCachePrefix());
        $this->assertEquals(CacheDecorator::GLOBAL_CACHE_MINUTES, CacheDecorator::getGlobalCacheMinutes());
    }
}