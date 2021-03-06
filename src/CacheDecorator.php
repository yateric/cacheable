<?php

/**
 * This file is part of Cacheable.
 *
 * (c) Eric Chow <yateric@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yateric\Cacheable;

use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Cache\Store as CacheStoreContract;
use Yateric\Cacheable\Exceptions\CacheStoreException;
use Yateric\Cacheable\Exceptions\CacheStoreNotFoundException;
use Yateric\Cacheable\Exceptions\NotObjectException;

class CacheDecorator
{
    const GLOBAL_CACHE_MINUTES = 60;

    /**
     * Decorated object or full namespace of the decorated class.
     *
     * @var object|string
     */
    protected $wrappedObject;

    /**
     * Default cache minutes for the decorated object.
     *
     * @var int
     */
    protected $defaultCacheMinutes;

    /**
     * Cache minutes for next method call.
     *
     * @var int
     */
    protected $cacheMinutes;

    /**
     * Underlying cache store.
     *
     * @var CacheStoreContract
     */
    protected static $cacheStore;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected static $cachePrefix = '';

    /**
     * Global cache minutes for all cache decorators.
     *
     * @var int
     */
    protected static $globalCacheMinutes = self::GLOBAL_CACHE_MINUTES;

    /**
     * Create a new cache decorator instance.
     *
     * @param  object|string  $wrappedObject
     * @return void
     * @throws NotObjectException
     */
    public function __construct($wrappedObject)
    {
        if (! is_object($wrappedObject) && ! class_exists($wrappedObject)) {
            throw new NotObjectException('Wrapped object must be an object.');
        }

        $this->wrappedObject = $wrappedObject;
    }

    /**
     * Get the cache key which combined with prefix and hashed class name, method and parameters.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return string
     */
    protected function getCacheKey($method, $parameters)
    {
        $parameters = json_encode($parameters);

        return static::getCachePrefix() . hash('sha256', "{$this->getClassName()}{$method}{$parameters}");
    }

    /**
     * Get the class name of decorated object with full namespace.
     *
     * @return string
     */
    protected function getClassName()
    {
        return is_object($this->wrappedObject) ? get_class($this->wrappedObject) : $this->wrappedObject;
    }

    /**
     * Get the cache minutes.
     *
     * @return int
     */
    public function getCacheMinutes()
    {
        if ($this->cacheMinutes) {
            return $this->cacheMinutes;
        }

        if ($this->defaultCacheMinutes) {
            return $this->defaultCacheMinutes;
        }

        return static::$globalCacheMinutes;
    }

    /**
     * Set the cache minutes.
     *
     * @param  int  $minutes
     * @return $this
     */
    public function setCacheMinutes($minutes)
    {
        $this->cacheMinutes = $minutes;

        return $this;
    }

    /**
     * Set the default cache minutes for the decorated object.
     *
     * @param  int  $minutes
     * @return $this
     */
    public function setDefaultCacheMinutes($minutes)
    {
        $this->defaultCacheMinutes = $minutes;

        return $this;
    }

    /**
     * Get the cache store.
     *
     * @return CacheStoreContract
     * @throws CacheStoreNotFoundException
     */
    protected static function getCacheStore()
    {
        if (static::$cacheStore) {
            return static::$cacheStore;
        }

        if ($laravelCacheManager = static::getLaravelCacheStore()) {
            static::$cacheStore = $laravelCacheManager;

            return static::$cacheStore;
        }

        throw new CacheStoreNotFoundException('Please set the cache store for cache decorator.');
    }

    /**
     * Try to get the Laravel cache store.
     *
     * @return CacheContract|CacheStoreContract|false
     */
    protected static function getLaravelCacheStore()
    {
        if (! function_exists('app')) {
            return false;
        }

        $cacheManager = app('cache');

        if (! $cacheManager instanceof FactoryContract) {
            return false;
        }

        return $cacheManager->store();
    }

    /**
     * Set the cache store.
     *
     * @param  CacheStoreContract $cacheStore
     * @throws CacheStoreException
     */
    public static function setCacheStore($cacheStore)
    {
        if (! $cacheStore instanceof CacheStoreContract) {
            throw new CacheStoreException('Cache store must implement [' . CacheStoreContract::class . '] contract');
        }

        static::$cacheStore = $cacheStore;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public static function getCachePrefix()
    {
        return static::$cachePrefix;
    }

    /**
     * Set the cache key prefix.
     *
     * @param  string  $prefix
     * @return void
     */
    public static function setCachePrefix($prefix) {
        static::$cachePrefix = $prefix;
    }

    /**
     * Get the global cache minutes for all cache decorators.
     *
     * @return int
     */
    public static function getGlobalCacheMinutes()
    {
        return static::$globalCacheMinutes;
    }

    /**
     * Set the global cache minutes for all cache decorators.
     *
     * @param  int  $minutes
     * @return void
     */
    public static function setGlobalCacheMinutes($minutes) {
        static::$globalCacheMinutes = $minutes;
    }

    /**
     * Flush the cache store.
     *
     * @return void
     */
    public static function flush()
    {
        $cacheStore = static::getCacheStore();

        $cacheStore->flush();
    }

    /**
     * Reset the cache decorator static states.
     *
     * @return void
     */
    public static function reset()
    {
        static::setCachePrefix('');
        static::setGlobalCacheMinutes(static::GLOBAL_CACHE_MINUTES);
        static::flush();
    }

    /**
     * Handle dynamic method calls into the cache decorator.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $cacheStore = static::getCacheStore();
        $cacheKey = $this->getCacheKey($method, $parameters);

        if ($value = $cacheStore->get($cacheKey)) {
            return $value;
        }

        $value = call_user_func_array([$this->wrappedObject, $method], $parameters);

        $cacheStore->put($cacheKey, $value, $this->getCacheMinutes());

        $this->cacheMinutes = null;

        return $value;
    }
}