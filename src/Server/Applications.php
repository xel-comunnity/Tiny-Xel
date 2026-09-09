<?php

declare(strict_types=1);

namespace Tiny\Xel\Server;

# Server Lib
use Swoole\Http\Server;
use Swoole\Http\Response;
use Swoole\Http\Request;

# Provider Lib
use function Tiny\Xel\Provider\{__boot_app};
use function Tiny\Xel\Gemstone\Handler\{__requestHandler};
use function Tiny\Xel\Gemstone\Handler\Context\{
    __init__context,
    __flush_context
};

# Database driver contract
use Tiny\Xel\Database\Contract\DriverContract;
use Tiny\Xel\Database\DriverResolver;

class Applications
{
    public Server $server;

    private array $provider = [];

    /**
     * @param array<int, mixed> $provider
     * @return Applications
     */
    public function __setProvider(array $provider): Applications
    {
        $this->provider = $provider;
        return $this;
    }

    // ? server boot
    public function __init(): void
    {
        // ? server init
        $this->server = new Server(
            $this->provider["server"]["api"]["api"]["host"],
            $this->provider["server"]["api"]["api"]["port"],
            $this->provider["server"]["api"]["api"]["mode"],
            $this->provider["server"]["api"]["api"]["sock"]
        );

        $options = $this->provider["server"]["api"]["api"]["options"];

        // ? Some db driver contracts (e.g. EloquentDriver) rely on
        // ? static/global state that isn't coroutine-safe, and need the
        // ? server to process one request at a time - normal, non-concurrent
        // ? PHP execution - to stay correct. enable_coroutine can only be
        // ? set before the server starts, so this must happen here rather
        // ? than in onWorkerStart, where the db driver is actually booted.
        $dbDriver = DriverResolver::resolve(
            $this->provider["db"]["contract"] ?? "eloquent"
        );
        if ($dbDriver::requiresBlockingServer()) {
            $options["enable_coroutine"] = false;

            // ? task workers get their own coroutine toggle, independent of
            // ? the main one above - task_enable_coroutine defaulting to
            // ? true (see test/config/server.php) would otherwise let
            // ? several dispatched tasks run concurrently as coroutines
            // ? inside one task worker process, each sharing that worker's
            // ? single Eloquent connection: the same class of coroutine-
            // ? safety bug this driver exists to avoid, just relocated to
            // ? task workers instead of HTTP workers.
            $options["task_enable_coroutine"] = false;
        }

        // ? server setup
        $this->server->set($options);

        // ? server events
        $this->server->on("workerStart", [$this, "onWorkerStart"]);
        $this->server->on("workerStop", [$this, "onWorkerStop"]);
        $this->server->on("request", [$this, "onRequest"]);
        $this->server->on("task", [$this, "onTask"]);

        $this->server->start();
        
    }

    /////////////////////////////////////////////////////////////////////////// ? server event handler

    /**
     * @return void
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        // ? boot  server provider
        __boot_app($server, $this->provider);
    }

    /**
     * Closes the db driver's connection(s) before this worker process
     * exits (e.g. on `$server->reload()`, or a graceful shutdown) - without
     * this, a connection/pool booted in onWorkerStart just gets dropped
     * with the process, leaving the OS socket (and, for a database server
     * with a connection limit, its slot) to clean up on its own instead of
     * closing it deliberately.
     */
    public function onWorkerStop(Server $server, int $workerId): void
    {
        $driver = $server->{'db_driver'} ?? null;
        if ($driver instanceof DriverContract) {
            $driver->shutdown();
        }
    }

    // ? OnStart event : for handling Http Requests
    public function onRequest(Request $request, Response $response): void
    {
        // ? load context
        __init__context($request, $response, $this->server);

        // ? request handler
        __requestHandler($this->server, $request, $response);

        // ? flush context
        __flush_context();
    }

    // ? OnStart event : for handling process when server start
    /**
     * @return void
     */
    public function onStart(): void
    {
    }
    /**
     * @return void
     */
    public function onTask(): void
    {
    }

    /**
     * Summary of periodicReload
     * @return void
     */
    public function periodicReload(): void
    {
    }
}
