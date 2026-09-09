<?php

/**
 * Standalone migration runner for the "eloquent" db driver contract.
 *
 * Migrations run once via this CLI command, the same way `php artisan
 * migrate` does in a full Laravel app - never from inside a Swoole worker's
 * boot. Every worker booting would otherwise race to run the same pending
 * migrations against the database at the same time.
 *
 * Usage:
 *   php test/migrate.php                 run pending migrations
 *   php test/migrate.php status           show ran vs pending migrations
 *   php test/migrate.php rollback         roll back the last batch
 *   php test/migrate.php rollback --step=2  roll back the last 2 migrations
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
$path = $db["migrations"]["path"];

$action = $argv[1] ?? "migrate";

$options = [];
foreach (array_slice($argv, 2) as $arg) {
    if (preg_match('/^--step=(\d+)$/', $arg, $m)) {
        $options["step"] = (int) $m[1];
    }
}

switch ($action) {
    case "status":
        $ran = $migrator->getRepository()->getRan();
        $files = $migrator->getMigrationFiles([$path]);

        if (empty($files)) {
            echo "No migration files found in {$path}.\n";
            break;
        }

        foreach ($files as $name => $file) {
            $status = in_array($name, $ran, true) ? "Ran" : "Pending";
            printf("%-8s %s\n", $status, $name);
        }
        break;

    case "rollback":
        $rolledBack = $migrator->rollback([$path], $options);

        if (empty($rolledBack)) {
            echo "Nothing to rollback.\n";
            break;
        }

        foreach ($rolledBack as $migration) {
            echo "Rolled back: {$migration}\n";
        }
        break;

    case "migrate":
        $ran = $migrator->run([$path]);

        if (empty($ran)) {
            echo "Nothing to migrate.\n";
            break;
        }

        foreach ($ran as $migration) {
            echo "Migrated: {$migration}\n";
        }
        break;

    default:
        fwrite(STDERR, "Unknown action \"{$action}\". Use: migrate, status, rollback.\n");
        exit(1);
}
