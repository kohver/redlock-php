redlock-php - Redis distributed locks in PHP

Based on [Redlock-rb](https://github.com/antirez/redlock-rb) by [Salvatore Sanfilippo](https://github.com/antirez)

This library implements the Redis-based distributed lock manager algorithm [described in this blog post](http://antirez.com/news/77).

To create a lock manager:

```php

$RedLock = new RedLock([
    new Client(['host' => '127.0.0.1', 'port' => 6379, 'timeout' => 0.01]),
    new Client(['host' => '127.0.0.1', 'port' => 6380, 'timeout' => 0.01]),
    new Client(['host' => '127.0.0.1', 'port' => 6381, 'timeout' => 0.01]),
]);

```

To acquire a lock:

```php

$lock = $RedLock->lock('my_resource_name', 1000);

```

Where the resource name is an unique identifier of what you are trying to lock
and 1000 is the number of milliseconds for the validity time.

If the lock was not acquired `LockTimeoutException` will be thrown,
otherwise an instance of `Lock` is returned, having three methods:

* `getValidity`, an integer representing the number of milliseconds the lock will be valid.
* `getResource`, the name of the locked resource as specified by the user.
* `getToken`, a random token value which is used to safe reclaim the lock.

To release a lock:

```php

$RedLock->unlock($lock)

```

It is possible to setup the number of retries (by default 3) and the retry
delay (by default 200 milliseconds) used to acquire the lock.

The retry delay is actually chosen at random between `$retryDelay / 2` milliseconds and
the specified `$retryDelay` value.

**Disclaimer**: As stated in the original antirez's version, this code implements an algorithm
which is currently a proposal, it was not formally analyzed. Make sure to understand how it works
before using it in your production environments.
