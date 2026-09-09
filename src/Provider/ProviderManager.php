<?php

namespace Tiny\Xel\Provider;

use Exception;
use Swoole\Http\Server;
use Swoole\Timer;
use Throwable;
use Tiny\Xel\Database\DriverResolver;
use Tiny\Xel\Gemstone\Router\RouterHandler;

function __boot_app(Server $server, array $provider)
{
    // ?  init db
    __db(config: $provider["db"], server: $server);

    // ?  init provider
    __provider(config: $provider, server: $server);

    // ?  init server
    __instance_init(server: $server);

    // ? reload config
    __periodic_reload(server: $server, hotReload: $provider["server"]["api"]);
}

function __periodic_reload(Server $server, array $hotReload)
{
    if ($hotReload["periodic-reload"][0]) {
        // Open an inotify instance
        Timer::tick($hotReload["periodic-reload"][1], function () use (
            $server
        ) {
            $server->reload(true);
            echo "Source get reload \n";
        });
    }
}

/////////////////////////////////////////////////////////////////////////// ? DB Init

/**
 * Boots the configured db driver contract (see Tiny\Xel\Database\Contract\
 * DriverContract). Defaults to "swoole-pool" - the existing coroutine-native
 * PDOPool driver - to stay backwards compatible with configs that don't set
 * "contract" at all.
 */
function __db(Server $server, array $config)
{
    try {
        $driver = DriverResolver::make($config["contract"] ?? "swoole-pool");
        $driver->boot($server, $config);

        $server->{'db_driver'} = $driver;
    } catch (Throwable $e) {
        // ? kept as the raw Throwable (not a hand-built array) so
        // ? __favIconHandler can replay it through the same
        // ? Tiny\Xel\Exception\ExceptionRenderer every other error in the
        // ? system goes through, instead of dumping a raw trace array to
        // ? every client hitting the server while it's stuck in this state.
        $server->error = $e;
    }
}

/////////////////////////////////////////////////////////////////////////// ? Provider Init

// ? load Provider
function __provider(Server $server, array $config = [])
{
    foreach ($config as $key => $value) {
        try {
            $server->{$key} = match ($key) {
                "server",
                "db",
                "router",
                "scheduler",
                "background",
                "custom"
                    => $value,
                default => throw new Exception("Unsupported Provider key"),
            };
        } catch (Throwable $e) {
            $server->error = $e;
        }
    }
}

function __populate_injection(Server $server)
{
    // ? get provider cutom value
    $injection = $server->{'custom'};

    // ? variable temp binding
    $bind = [];

    // ? pre-injection
    if (
        isset($injection["pre-injection"]) &&
        count($injection["pre-injection"]) > 0
    ) {
        foreach ($injection["pre-injection"] as $key => $value) {
            $bind[$key] = new $value();
        }

        $server->{'pre-injection'} = $bind;
    }

    // ? pre-injection
    if (
        isset($injection["fly-injection"]) &&
        count($injection["fly-injection"]) > 0
    ) {
        $server->{'fly-injection'} = $server->{'custom'}["fly-injection"];
    }
}

/////////////////////////////////////////////////////////////////////////// ? Instance Init

function __instance_init(Server $server)
{
    // ? RouterHandler is stateless per-request (see RouterHandler::handler);
    // ? a single instance shared across every worker coroutine is safe.
    // ? The middleware queue is intentionally NOT built here: a queue shared
    // ? across concurrent coroutines would be mutated by every request at
    // ? once, so RouterHandler builds a fresh one per request instead.
    $router = new RouterHandler();
    $server->{'router_init'} = $router;
}
