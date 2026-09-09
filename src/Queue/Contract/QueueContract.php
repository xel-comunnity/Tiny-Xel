<?php

declare(strict_types=1);

namespace Tiny\Xel\Queue\Contract;

/**
 * A durable, FIFO job queue - complements Tiny\Xel\Task\TaskContract's
 * Swoole task workers rather than replacing them: a Swoole task is fast
 * and in-memory, but lost if the worker restarts before it runs; a queue
 * driver persists the job (in Redis, for RedisQueueDriver) so it survives
 * a restart and can be picked up by any process that pops from the same
 * queue name.
 *
 * push()/pop() work with a plain array payload - not a QueueContract
 * implementation's own concern how that payload maps to, say, a
 * TaskContract - so a worker loop popping from this queue decides what
 * to do with the payload it gets back.
 */
interface QueueContract
{
    /**
     * @param array<string, mixed> $payload
     */
    public function push(string $queue, array $payload): void;

    /**
     * @param int $timeout seconds to block waiting for a job; 0 waits
     *        forever, a positive number gives up and returns null after
     *        that many seconds with nothing to pop.
     * @return array<string, mixed>|null
     */
    public function pop(string $queue, int $timeout = 0): ?array;

    public function size(string $queue): int;
}
