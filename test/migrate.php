<?php

/**
 * Standalone migration runner for the "eloquent" db driver contract.
 *
 * Migrations run once via this CLI command, the same way `php artisan
 * migrate` does in a full Laravel app - never from inside a Swoole worker's
 * boot. Every worker booting would otherwise race to run the same pending
 * migrations against the database at the same time.
 *
 * Usage: php test/migrate.php
 */

require __DIR__ . "/../vendor/autoload.php";

use Tiny\Xel\Database\Driver\EloquentDriver;

$provider = require __DIR__ . "/config/provider.php";
$db = $provider["db"];

if (($db["contract"] ?? "swoole-pool") !== "eloquent") {
    fwrite(
        STDERR,
        "db.contract is not \"eloquent\" in test/config/provider.php - nothing to migrate.\n"
    );
    exit(1);
}

if (empty($db["migrations"]["path"])) {
    fwrite(STDERR, "db.migrations.path is not configured.\n");
    exit(1);
}

$driver = new EloquentDriver();
$driver->connect($db);

$migrator = $driver->migrator();
$ran = $migrator->run([$db["migrations"]["path"]]);

if (empty($ran)) {
    echo "Nothing to migrate.\n";
    exit(0);
}

foreach ($ran as $migration) {
    echo "Migrated: {$migration}\n";
}
