<?php

declare(strict_types=1);

namespace Tiny\Xel\Queue\Driver;

use Predis\Client;
use Tiny\Xel\Config\Env;
use Tiny\Xel\Queue\Contract\QueueContract;

/**
 * A Redis list used as a FIFO queue: push() is RPUSH, pop() is BLPOP (a
 * blocking pop - the calling process waits, up to $timeout seconds, for a
 * job to become available instead of busy-polling). See QueueContract's
 * docblock for how this relates to Tiny\Xel\Task\TaskContract.
 */
final class RedisQueueDriver implements QueueContract
{
    private Client $client;
    private string $prefix;

    /**
     * @param array<string, mixed> $config predis connection parameters
     */
    public function __construct(array $config = [], string $prefix = "queue:")
    {
        $this->client = new Client($config ?: ["host" => "127.0.0.1", "port" => 6379]);
        $this->prefix = $prefix;
    }

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

    public function push(string $queue, array $payload): void
    {
        $this->client->rpush($this->key($queue), [json_encode($payload)]);
    }

    public function pop(string $queue, int $timeout = 0): ?array
    {
        $result = $this->client->blpop([$this->key($queue)], $timeout);

        if ($result === null) {
            return null;
        }

        // ? blpop replies [key, value] - only the value matters here.
        $decoded = json_decode($result[1], true);

        return is_array($decoded) ? $decoded : null;
    }

    public function size(string $queue): int
    {
        return $this->client->llen($this->key($queue));
    }

    private function key(string $queue): string
    {
        return $this->prefix . $queue;
    }
}
