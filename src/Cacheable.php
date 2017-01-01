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

trait Cacheable
{
    /**
     * Cache decorator instance.
     *
     * @var CacheDecorator
     */
    protected $cacheDecorator;

    /**
     * Cache decorator instance
     *
     * @var CacheDecorator
     */
    protected static $staticCacheDecorator;

    /**
     * Prepare a new or cached cache decorator instance for non-static method call.
     *
     * @param  int|null  $minutes
     * @return CacheDecorator
     */
    public function cache($minutes = null)
    {
        if (! $this->cacheDecorator) {
            $this->cacheDecorator = new CacheDecorator($this);
        }

        return $this->cacheDecorator->setCacheMinutes($minutes);
    }

    /**
     * Prepare a new or cached cache decorator instance for static method call.
     *
     * @param  int|null  $minutes
     * @return CacheDecorator
     */
    public static function cacheStatic($minutes = null)
    {
        if (! static::$staticCacheDecorator) {
            static::$staticCacheDecorator = new CacheDecorator(get_class());
        }

        return static::$staticCacheDecorator->setCacheMinutes($minutes);
    }
}