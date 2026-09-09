<?php

// ? App config
$router = require __DIR__ . "/../src/Router/router.php";
$db = require __DIR__ . "/db.php";
$server = require __DIR__ . "/server.php";

// ? provider external injection
return [
    // ? by default setup for setup is api service http
    // ? u can create listen config for diferent protocol (websockert / tcp) server
    "server" => [
        "api" => $server,
    ],

    // ? change to multi db to enable capability for multi-connection
    // ? by default, multi-connection which injected with db config will process with pre-register mode
    // ? db connection mode is pool for default system
    // ? if you prefer persistance connection, create custom adapather or u can use library provided to make persistance connection
    "db" => [
        "mode" => "single",

        // ? which Tiny\Xel\Database\Contract\DriverContract implementation to boot.
        // ? "swoole-pool" (default): coroutine-native PDOPool, safe with coroutines enabled.
        // ? "eloquent": boots Laravel's Eloquent ORM (Model, query builder, migrations).
        // ?   Eloquent's connection resolver is static/global, so selecting this driver
        // ?   makes Applications::__init() start the server with enable_coroutine=false -
        // ?   see src/Database/Driver/EloquentDriver.php for why.
        "contract" => "swoole-pool", // "eloquent",

        "config" => $db,

        // ? only read by the "eloquent" contract. Point "path" at a directory of
        // ? Illuminate\Database\Migrations\Migration classes and run them with
        // ? `php test/migrate.php` (migrations run once via CLI, not on worker boot).
        "migrations" => [
            "path" => __DIR__ . "/../database/migrations",
            "table" => "migrations",
        ],

        // ? after migrating, seed sample data with `php test/seed.php` - see
        // ? test/database/seeders/DatabaseSeeder.php.
    ],

    // ? this key will process about routing and middleware case
    "router" => [
        "dispatcher" => $router,
        "middleware" => [
            /** for future cutomisation of middleware handler */
        ],
    ],

    // ? scheduler is part of xel feature with separate lib (it will use reactphp to perform cron / scheduler)
    "scheduler" => [
        /** For Cron Based Handler */
    ],

    // ? define and process which consuming time in background
    "background" => [
        /** It will use swoole task to process any background task */
    ],

    // ? create custom functonality or inject external  library  to sistem and brodcast to another class
    // ? fly-injection mode is type of injection will be trigger in each request (it will bootstrapt instance over and over in each request and flush it)
    // ? pre-injection mode is type of injection will be trigger in when master process (worker) start.
    "custom" => [
        "fly-injection" => [],

        "pre-injection" => [],
    ],
];
