<?php

declare(strict_types=1);

namespace Tiny\Xel\Cache\Driver;

use Closure;
use Predis\Client;
use Tiny\Xel\Cache\Contract\CacheContract;
use Tiny\Xel\Config\Env;

/**
 * A Redis-backed cache, via predis/predis - a pure-PHP client, so no PDO-
 * style Swoole coroutine pooling and no ext-redis system dependency, same
 * as every other driver in this framework.
 *
 * predis makes plain blocking TCP calls: fine (and simple) under the
 * "eloquent" db contract's blocking server, but under "swoole-pool"
 * (coroutines enabled) an uncooperative blocking call here would stall the
 * whole worker's event loop for every other concurrent coroutine during
 * each Redis round-trip - unless Swoole's runtime hooks
 * (Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL), enabled once at boot)
 * are active to transparently reschedule it. That's the same class of
 * tradeoff already accepted for EloquentDriver.
 *
 * Values are PHP-serialized (not JSON) so any serializable value - not
 * just arrays/scalars - round-trips correctly, matching how Laravel's own
 * Redis cache store behaves.
 */
final class RedisCacheDriver implements CacheContract
{
    private Client $client;
    private string $prefix;

    /**
     * @param array<string, mixed> $config predis connection parameters
     *        (host/port/password/database/...)
     */
    public function __construct(array $config = [], string $prefix = "cache:")
    {
        $this->client = new Client($config ?: ["host" => "127.0.0.1", "port" => 6379]);
        $this->prefix = $prefix;
    }

    /**
     * Reads connection settings from REDIS_HOST/REDIS_PORT/REDIS_PASSWORD/
     * REDIS_DATABASE (see Tiny\Xel\Config\Env) instead of hardcoding them.
     */
    public static function fromEnv(): self
    {
        $config = array_filter([
            "host" => Env::get("REDIS_HOST", "127.0.0.1"),
            "port" => Env::get("REDIS_PORT", 6379),
            "password" => Env::get("REDIS_PASSWORD"),
            "database" => Env::get("REDIS_DATABASE"),
        ], static fn ($value) => $value !== null && $value !== "");

        return new self($config);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->client->get($this->key($key));

        return $value === null ? $default : unserialize($value);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $serialized = serialize($value);

        $result = $ttl !== null
            ? $this->client->setex($this->key($key), $ttl, $serialized)
            : $this->client->set($this->key($key), $serialized);

        return (string) $result === "OK";
    }

    public function has(string $key): bool
    {
        return (bool) $this->client->exists($this->key($key));
    }

    public function forget(string $key): bool
    {
        return $this->client->del([$this->key($key)]) > 0;
    }

    public function remember(string $key, ?int $ttl, Closure $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    public function flush(): bool
    {
        // ? deletes only keys under this driver's own prefix via SCAN
        // ? (cursor-based, doesn't block Redis the way KEYS does on a large
        // ? keyspace) - never FLUSHDB, which would also wipe out anything
        // ? else sharing this Redis instance/database, such as
        // ? RedisQueueDriver's queues.
        $cursor = "0";
        do {
            [$cursor, $keys] = $this->client->scan($cursor, [
                "match" => "{$this->prefix}*",
                "count" => 100,
            ]);

            if (!empty($keys)) {
                $this->client->del($keys);
            }
        } while ((string) $cursor !== "0");

        return true;
    }

    private function key(string $key): string
    {
        return $this->prefix . $key;
    }
}
