<?php

declare(strict_types=1);

namespace Tiny\Xel\Database\Driver;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Swoole\Http\Server;
use Throwable;
use Tiny\Xel\Database\Contract\DriverContract;
use Tiny\Xel\Validation\ValidatorFactory;

/**
 * Boots Laravel's Eloquent ORM (via the standalone Capsule component) so
 * Models, the query builder and migrations can be used the same way they
 * are in a plain Laravel app.
 *
 * Eloquent resolves its connections through a global, static resolver
 * (Model::getConnectionResolver()) and Capsule::setAsGlobal() binds a
 * single global container instance - none of that is scoped per coroutine.
 * Two concurrent coroutines are free to interleave at any I/O boundary, so
 * sharing that static state across coroutines would reproduce the exact
 * class of bug fixed in RouterHandler (state from one request corrupting
 * another's mid-flight) - except inside the ORM instead of the router.
 * requiresBlockingServer() therefore tells Applications::__init() to start
 * Swoole with enable_coroutine=false whenever this driver is selected: the
 * worker then handles exactly one request at a time, so Eloquent behaves
 * exactly as it does in a traditional (non-coroutine) PHP process.
 */
class EloquentDriver implements DriverContract
{
    private Capsule $capsule;
    private ?Migrator $migrator = null;

    public static function requiresBlockingServer(): bool
    {
        return true;
    }

    public function boot(Server $server, array $config): void
    {
        $this->connect($config);

        $server->{"eloquent"} = $this->capsule;
    }

    /**
     * Boots Capsule/Eloquent (and the Migrator, if configured) without
     * requiring a Swoole server instance. boot() delegates to this, but it
     * is also the entry point for a standalone CLI migration runner - see
     * test/migrate.php - since migrations should run once via a command,
     * the same way `php artisan migrate` does, not from inside a worker's
     * boot (every worker booting would otherwise race to run the same
     * pending migrations against the database at once).
     *
     * @param array<string, mixed> $config the "db" section of the app's provider config
     */
    public function connect(array $config): Capsule
    {
        $capsule = new Capsule();

        $driver = $config["config"]["driver"];
        $database = $config["config"]["db"] ?? $config["config"]["database"] ?? null;

        if ($driver === "sqlite" && $database !== null) {
            $this->ensureSqliteFileExists($database);
        }

        // ? Illuminate's ConnectionFactory decides whether to use its
        // ? multi-host failover resolver purely from array_key_exists('host',
        // ? $config) - NOT from whether the value is non-null. A driver like
        // ? sqlite has no host at all, so a literal "host" => null here would
        // ? wrongly route it through host-list resolution and blow up with
        // ? "Database hosts array is empty." Filtering out unset/null keys
        // ? keeps drivers that legitimately omit them (sqlite: host, port,
        // ? username, password) working the same as a native Laravel config.
        $capsule->addConnection(array_filter([
            "driver" => $driver,
            "host" => $config["config"]["host"] ?? null,
            "port" => $config["config"]["port"] ?? null,
            "database" => $database,
            "username" => $config["config"]["username"] ?? null,
            "password" => $config["config"]["password"] ?? null,
            "charset" => $config["config"]["charset"] ?? "utf8mb4",
            "collation" => $config["config"]["collation"] ?? "utf8mb4_unicode_ci",
            "prefix" => $config["config"]["prefix"] ?? "",
        ], static fn($value) => $value !== null));

        // ? Lets Eloquent model events (creating, created, saving, ...) and
        // ? observers fire, same as in a full Laravel app - bootEloquent()
        // ? only wires Eloquent::setEventDispatcher() up when one is already
        // ? bound, so this must run before it.
        $capsule->setEventDispatcher(new Dispatcher($capsule->getContainer()));

        // ? Global by design: with coroutines disabled (requiresBlockingServer)
        // ? only one request runs at a time, so a single global resolver is
        // ? exactly as safe here as it is in a traditional PHP-FPM request.
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // ? Wire up the container bindings a full Laravel app's
        // ? DatabaseServiceProvider would normally register, so migrations
        // ? and app code can use the familiar Schema::/DB:: facades instead
        // ? of reaching into the Capsule instance directly.
        $container = $capsule->getContainer();
        $container->singleton("db", fn () => $capsule->getDatabaseManager());
        $container->singleton(
            "db.schema",
            fn ($app) => $app["db"]->connection()->getSchemaBuilder()
        );
        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        // ? lets "unique:table,column" / "exists:table,column" validation
        // ? rules work against this connection with zero extra setup - see
        // ? Tiny\Xel\Validation\ValidatorFactory.
        ValidatorFactory::usingConnectionResolver($capsule->getDatabaseManager());

        $this->capsule = $capsule;

        if (!empty($config["migrations"]["path"])) {
            $this->bootMigrator($config["migrations"]);
        }

        return $capsule;
    }

    public function capsule(): Capsule
    {
        return $this->capsule;
    }

    public function migrator(): ?Migrator
    {
        return $this->migrator;
    }

    public function release(): void
    {
        $connection = $this->capsule->connection();

        // ? This one connection lives for the whole worker (see the class
        // ? docblock), not per-request like a traditional PHP-FPM process.
        // ? If a request throws between beginTransaction() and commit()/
        // ? rollBack() - a very ordinary bug in business code, not
        // ? something exotic - the connection would otherwise stay inside
        // ? that half-finished transaction forever: every later request on
        // ? this worker would silently run inside it too, seeing
        // ? uncommitted writes or blocking on locks that were never meant
        // ? to outlive the request that opened them. Force it closed here
        // ? so one request's mistake can never leak into the next one.
        if ($connection->transactionLevel() > 0) {
            error_log(sprintf(
                "EloquentDriver: request ended with %d open transaction(s) still on the connection - forcing rollback.",
                $connection->transactionLevel()
            ));
            $connection->rollBack(0);
        }

        // ? keep the query log from growing for the lifetime of the worker.
        $connection->flushQueryLog();
    }

    public function ping(): bool
    {
        try {
            $this->capsule->connection()->select("select 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function shutdown(): void
    {
        // ? closes the one connection this worker has been holding for its
        // ? whole lifetime (see the class docblock) - runs once, right
        // ? before the worker process exits.
        $this->capsule->getDatabaseManager()->disconnect();
    }

    /**
     * @param array{path?: string, table?: string} $config
     */
    private function bootMigrator(array $config): void
    {
        $resolver = $this->capsule->getDatabaseManager();
        $table = $config["table"] ?? "migrations";

        $repository = new DatabaseMigrationRepository($resolver, $table);
        if (!$repository->repositoryExists()) {
            $repository->createRepository();
        }

        $this->migrator = new Migrator($repository, $resolver, new Filesystem());
    }

    /**
     * Illuminate's SQLiteConnector resolves the database path with
     * `realpath($path) ?: realpath(base_path($path))`. realpath() returns
     * false for a file that doesn't exist yet - true on a brand new
     * project, before the first migration has ever run - and base_path()
     * is a global helper a full Laravel app provides but the standalone
     * illuminate/database package we depend on does not define at all, so
     * that fallback is a fatal "Call to undefined function base_path()"
     * instead of the friendlier SQLiteDatabaseDoesNotExistException it's
     * meant to produce.
     *
     * Creating the file (and its directory) upfront - exactly what
     * Laravel's own project skeleton does with
     * `touch database/database.sqlite` before anything ever connects to
     * it - makes that first realpath() succeed, so the base_path()
     * fallback is never reached.
     */
    private function ensureSqliteFileExists(string $database): void
    {
        $isInMemory =
            $database === ":memory:" ||
            str_contains($database, "?mode=memory") ||
            str_contains($database, "&mode=memory");

        if ($isInMemory || is_file($database)) {
            return;
        }

        $directory = dirname($database);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        touch($database);
    }
}
