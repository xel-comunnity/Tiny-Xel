<?php

/**
 * Standalone database seeder runner for the "eloquent" db driver contract.
 *
 * Same reasoning as test/migrate.php: run this once via CLI, never from
 * inside a Swoole worker's boot.
 *
 * Usage: php test/seed.php
 */

require __DIR__ . "/../vendor/autoload.php";

use Tiny\Test\Database\Seeders\DatabaseSeeder;
use Tiny\Xel\Database\Driver\EloquentDriver;

$provider = require __DIR__ . "/config/provider.php";
$db = $provider["db"];

if (($db["contract"] ?? "swoole-pool") !== "eloquent") {
    fwrite(
        STDERR,
        "db.contract is not \"eloquent\" in test/config/provider.php - nothing to seed.\n"
    );
    exit(1);
}

$driver = new EloquentDriver();
$driver->connect($db);

(new DatabaseSeeder())
    ->setContainer($driver->capsule()->getContainer())
    ->run();

echo "Seeding complete.\n";
