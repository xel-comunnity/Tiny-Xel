<?php

declare(strict_types=1);

namespace Tiny\Xel\Database\Contract;

use Swoole\Http\Server;

/**
 * A DriverContract wires one database access strategy into the framework.
 *
 * Two shapes exist today:
 *  - a coroutine-native driver (SwoolePoolDriver) that pools connections per
 *    Swoole coroutine via Swoole\Database\PDOPool, safe to run concurrently.
 *  - a blocking driver (EloquentDriver) that boots Laravel's Eloquent ORM,
 *    which keeps connection/state resolution in static/global singletons
 *    (Illuminate\Database\Eloquent\Model's static resolver, the global
 *    Capsule instance). That state is not coroutine-safe: two concurrent
 *    coroutines resolving/overwriting the same static connection resolver
 *    would corrupt each other the same way the RouterHandler singleton bug
 *    did for routing. requiresBlockingServer() lets Applications::__init()
 *    disable Swoole's coroutine scheduler server-wide for such a driver, so
 *    requests are processed strictly one at a time - normal, non-concurrent
 *    PHP execution - which is the only way Eloquent's global state stays
 *    safe without patching Eloquent itself.
 */
interface DriverContract
{
    /**
     * Whether this driver requires the Swoole server to run with
     * `enable_coroutine` disabled. Called statically, before the Server is
     * constructed, so Applications::__init() can set that server option
     * ahead of time - it cannot be changed once the server has started.
     */
    public static function requiresBlockingServer(): bool;

    /**
     * Boot the driver once, when the worker process starts.
     *
     * @param array<string, mixed> $config the "db" section of the app's provider config
     */
    public function boot(Server $server, array $config): void;

    /**
     * Release/reset any per-request state. Invoked at the end of every HTTP
     * request (see __flush_context()), regardless of which driver is active,
     * so implementations with no per-request state to release are free to
     * no-op.
     */
    public function release(): void;

    /**
     * A cheap, synchronous connectivity check (e.g. `SELECT 1`) used by
     * Tiny\Xel\Health\HealthCheckHandler. Must never throw - catch any
     * connection failure internally and return false.
     */
    public function ping(): bool;

    /**
     * Tears the driver down when a worker process is stopping (see
     * Applications::onWorkerStop). Unlike release(), which runs after every
     * request, this runs once, right before the process exits - close
     * pooled connections / disconnect here so the OS socket doesn't outlive
     * the process needlessly. Must never throw.
     */
    public function shutdown(): void;
}
