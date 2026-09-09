<?php

declare(strict_types=1);

namespace Tiny\Xel\Cache\Contract;

use Closure;

/**
 * A key/value cache. Tiny\Xel\Cache\Driver\RedisCacheDriver is the only
 * implementation today, but business code should depend on this contract
 * rather than that class directly, the same way db access goes through
 * Tiny\Xel\Database\Contract\DriverContract rather than a specific driver.
 */
interface CacheContract
{
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @param int|null $ttl seconds until expiry, or null to never expire
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool;

    public function has(string $key): bool;

    public function forget(string $key): bool;

    /**
     * Returns the cached value for $key if present, otherwise calls
     * $callback(), caches its return value, and returns that.
     *
     * @param int|null $ttl seconds until expiry, or null to never expire
     */
    public function remember(string $key, ?int $ttl, Closure $callback): mixed;

    /**
     * Removes everything from the cache. Careful: on a shared Redis
     * instance this can affect more than just cache keys - see
     * RedisCacheDriver's docblock.
     */
    public function flush(): bool;
}
