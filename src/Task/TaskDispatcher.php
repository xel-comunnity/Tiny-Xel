<?php

declare(strict_types=1);

namespace Tiny\Xel\Task;

use RuntimeException;
use Swoole\Http\Server;
use Tiny\Xel\Context\Context;
use Tiny\Xel\Task\Contract\TaskContract;

/**
 * Hands a TaskContract off to one of the server's Swoole task worker
 * processes (see Applications::onTask, which actually runs it).
 */
final class TaskDispatcher
{
    /**
     * @return int|false the task id Swoole assigned, or false if it could
     *         not be dispatched (e.g. every task worker is busy)
     */
    public static function dispatch(TaskContract $task): int|false
    {
        $server = Context::get("server");

        if (!$server instanceof Server) {
            throw new RuntimeException(
                "TaskDispatcher::dispatch() must be called from within a request " .
                "handled by the framework (no Swoole Server found in Context)."
            );
        }

        return $server->task($task);
    }
}
