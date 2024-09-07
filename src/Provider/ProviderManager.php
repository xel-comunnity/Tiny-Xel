<?php

namespace Tiny\Xel\Provider;

use Exception;
use Swoole\Http\Server;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Timer;
use Tiny\Xel\Gemstone\Router\RouterHandler;
use SplQueue;

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
function __db(Server $server, array $config)
{
    // ? pdo config
    try {
        $data = match ($config["config"]["driver"]) {
            "mysql" => __mysql($config),
            "sqlite" => __sqlite($config),
            "pgsql" => __pgsql($config),
            default => throw new Exception("Unsupported Driver"),
        };

        // ? dbmanager
        $server->pdo = $data;
    } catch (Exception $e) {
        $server->error = [
            "error-message" => $e->getMessage(),
            "error-line" => $e->getLine(),
            "error-trace" => $e->getTrace(),
        ];
    }
}

function __sqlite($config): PDOPool
{
    $conf = new PDOConfig();
    $conf
        ->withDriver($config["config"]["driver"])
        ->withDbname($config["config"]["database"]);

    $pdoPool = new PDOPool($conf, $config["config"]["pool"]);
    return $pdoPool;
}

function __mysql($config): PDOPool
{
    $conf = new PDOConfig();
    $conf
        ->withDriver($config["config"]["driver"])
        ->withHost($config["config"]["host"])
        ->withPort($config["config"]["port"])
        ->withDbname($config["config"]["db"])
        ->withCharset($config["config"]["charset"])
        ->withUsername($config["config"]["username"])
        ->withPassword($config["config"]["password"]);
    $pdoPool = new PDOPool($conf, $config["config"]["pool"]);

    return $pdoPool;
}

function __pgsql($config): PDOPool
{
    $conf = new PDOConfig();
    $conf
        ->withDriver($config["config"]["driver"])
        ->withHost($config["config"]["host"])
        ->withPort($config["config"]["port"])
        ->withDbname($config["config"]["db"])
        ->withCharset($config["config"]["charset"])
        ->withUsername($config["config"]["username"])
        ->withPassword($config["config"]["password"]);
    $pdoPool = new PDOPool($conf, $config["config"]["pool"]);

    return $pdoPool;
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
        } catch (Exception $e) {
            $server->error = [
                "error-message" => $e->getMessage(),
                "error-line" => $e->getLine(),
                "error-trace" => $e->getTrace(),
            ];
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
    $router = new RouterHandler();
    $middlewareQueue = new SplQueue();
    $server->{'router_init'} = $router;
    $server->{'middleware_queue'} = $middlewareQueue;
}
