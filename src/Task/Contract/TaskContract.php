<?php

declare(strict_types=1);

namespace Tiny\Xel\Task\Contract;

/**
 * A unit of work offloaded to a Swoole task worker process - a separate
 * process from the one handling the HTTP request, so it can do something
 * slow (send an email, process a file, call a slow third-party API)
 * without blocking that request or any other request on the same worker.
 *
 * Carry whatever data handle() needs via the class's own constructor (the
 * same pattern as a Laravel queued Job) - Tiny\Xel\Task\TaskDispatcher
 * hands the whole object to Swoole's task worker, which runs handle() in
 * a completely different process.
 *
 * Usage:
 *   class SendWelcomeEmail implements TaskContract
 *   {
 *       public function __construct(private string $email) {}
 *       public function handle(): mixed
 *       {
 *           // ... send the email ...
 *           return true;
 *       }
 *   }
 *
 *   TaskDispatcher::dispatch(new SendWelcomeEmail($user->email));
 */
interface TaskContract
{
    public function handle(): mixed;
}
