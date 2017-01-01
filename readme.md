Cacheable
=========

[![Build Status](https://img.shields.io/travis/yateric/cacheable/master.svg?style=flat-square)](https://travis-ci.org/yateric/cacheable)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/yateric/cacheable.svg?style=flat-square)](https://packagist.org/packages/yateric/cacheable)
[![Latest Version](https://img.shields.io/github/release/yateric/cacheable.svg?style=flat-square)](https://github.com/yateric/cacheable/releases)

This standalone package makes any object method return cacheable by prepend a chainable method.


## Features

- Make any (static or non-static) object method call cacheable
- Specify cache duration
- Standalone package that you can use it without any frameworks
- Support Laravel 5+ out of the box


## Installing

Either [PHP](https://php.net) 5.5+ or [HHVM](http://hhvm.com) 3.6+ are required.

To get the latest version of Cacheable, simply require the project using [Composer](https://getcomposer.org):

```bash
$ composer require yateric/cacheable
```

Instead, you may of course manually update your require block and run `composer update` if you so choose:

```json
{
    "require": {
        "yateric/cacheable": "^1.0"
    }
}
```


## Usage

First, pull in the `Yateric\Cacheable\Cacheable` trait to a class you want to cache the method result, it could be any kind of class: Eloquent model, repository or just an simple object.
 
```php
use Yateric\Cacheable\Cacheable;

class Worker {
    use Cacheable;
    
    public function timeConsumingTask()
    {
        sleep(10);
        
        return 'Some results';
    }
}
```

You can now cache and return the `timeConsumingTask()` result by prepend a chainable method `cache()`

```php
$worker = new Worker;

// By default, the results will cache for 60 minutes.
$results = $worker->cache()->timeConsumingTask();
```


#### Static method return caching

```php
use Yateric\Cacheable\Cacheable;

class Worker {
    use Cacheable;
    
    public static function timeConsumingTaskInStatic()
    {
        sleep(10);
        
        return 'Some results';
    }
}

// By default, the results will cache for 60 minutes.
$results = Worker::cacheStatic()->timeConsumingTaskInStatic();
```


#### Specific cache duration

```php
// Cache result for 120 minutes.
$results = $worker->cache(120)->timeConsumingTask();
$results = Worker::cacheStatic(120)->timeConsumingTaskInStatic();
```


#### Cache duration hierarchy

There are three level of cache duration setting:
- Runtime level
- Instance level
- Global level

Here are some example of the hierarchy:

```php
use Yateric\Cacheable\CacheDecorator;

// Cache for 60 minutes by default.
$workerA->cache()->timeConsumingTask();

// Cache for 120 minutes by runtime setting.
$workerA->cache(120)->timeConsumingTask();

// Return to default 60 minutes.
$workerA->cache()->timeConsumingTask();

// Set default cache duration to 180 minutes.
$workerA->cache()->setDefaultCacheMinutes(180);

// These calls will cache for 180 minutes.
$workerA->cache()->timeConsumingTaskA();
$workerA->cache()->timeConsumingTaskB();
$workerA->cache()->timeConsumingTaskC();

// Set the global cache duration to 240 minutes.
CacheDecorator::setGlobalCacheMinutes(240);

// Worker A will remain cache for 180 minutes because 
// we have set the default cache duration in instance level.
$workerA->cache()->timeConsumingTask();

// These calls will cache for 240 minutes.
$workerB->cache()->timeConsumingTask();
$workerC->cache()->timeConsumingTask();
```


#### Swap the underlying cache store

If you are using Laravel 5+, cacheable will use the default cache store `config('cache.default')` automatically. 
But you are free to specify any cache store which implement the `Illuminate\Contracts\Cache\Store` contract by calling `setCacheStore()`.

```php
use Yateric\Cacheable\CacheDecorator;

CacheDecorator::setCacheStore(new RedisStore($redis));
```


#### Cache prefix

You can manually set the cache prefix by calling `setCachePrefix()`.

```php
use Yateric\Cacheable\CacheDecorator;

CacheDecorator::setCachePrefix('yourprefix_');
```


## Security

If you discover a security vulnerability within this package, please send an e-mail to Eric Chow at yateric@gmail.com. All security vulnerabilities will be promptly addressed.


## License

Cacheable is licensed under [The MIT License (MIT)](LICENSE).