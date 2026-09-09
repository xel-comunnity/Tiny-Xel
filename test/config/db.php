<?php

use Tiny\Xel\Config\Env;

// ? Real values come from test/.env (see test/.env.example) - nothing
// ? here is a secret, so this file is safe to commit. Defaults fall back
// ? to a local sqlite file so the demo app runs out of the box with no
// ? external database required.
return match (Env::get("DB_CONNECTION", "sqlite")) {
    "mysql" => [
        "driver" => "mysql",
        "host" => Env::get("DB_HOST", "127.0.0.1"),
        "port" => Env::get("DB_PORT", 3306),
        "db" => Env::get("DB_DATABASE", "tiny_xel"),
        "username" => Env::get("DB_USERNAME", "root"),
        "password" => Env::get("DB_PASSWORD", ""),
        "charset" => Env::get("DB_CHARSET", "utf8mb4"),
        "pool" => Env::get("DB_POOL_SIZE", 10),
    ],

    "pgsql" => [
        "driver" => "pgsql",
        "host" => Env::get("DB_HOST", "127.0.0.1"),
        "port" => Env::get("DB_PORT", 5432),
        "db" => Env::get("DB_DATABASE", "tiny_xel"),
        "username" => Env::get("DB_USERNAME", "postgres"),
        "password" => Env::get("DB_PASSWORD", ""),
        "charset" => Env::get("DB_CHARSET", "UTF8"),
        "pool" => Env::get("DB_POOL_SIZE", 10),
    ],

    default => [
        "driver" => "sqlite",
        "database" => Env::get("DB_DATABASE", __DIR__ . "/../database/xel.sqlite"),
        "pool" => Env::get("DB_POOL_SIZE", 10),
    ],
};
