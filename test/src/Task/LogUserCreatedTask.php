<?php

namespace Tiny\Test\Task;

use Tiny\Xel\Task\Contract\TaskContract;

/**
 * Demonstrates Tiny\Xel\Task\TaskDispatcher: dispatched from
 * EloquentDemo::store() after creating a user, this runs in a Swoole task
 * worker process - a separate process from the one that handled the
 * request - so the request doesn't wait on it.
 */
final class LogUserCreatedTask implements TaskContract
{
    public function __construct(private readonly string $email)
    {
    }

    public function handle(): mixed
    {
        error_log("[task] user created: {$this->email}");
        return true;
    }
}
