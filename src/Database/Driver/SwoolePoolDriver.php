<?php

declare(strict_types=1);

namespace Tiny\Xel\Database\Driver;

use Exception;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Http\Server;
use Throwable;
use Tiny\Xel\Context\DBContext;
use Tiny\Xel\Database\Contract\DriverContract;

/**
 * The existing coroutine-native driver: a Swoole\Database\PDOPool checked
 * out per-coroutine via DBContext. Safe to run with coroutines enabled.
 */
class SwoolePoolDriver implements DriverContract
{
    private PDOPool $pool;

    public static function requiresBlockingServer(): bool
    {
        return false;
    }

    public function boot(Server $server, array $config): void
    {
        $this->pool = match ($config["config"]["driver"]) {
            "mysql" => $this->mysql($config),
            "sqlite" => $this->sqlite($config),
            "pgsql" => $this->pgsql($config),
            default => throw new Exception("Unsupported Driver"),
        };

        DBContext::setPool($this->pool);

        // ? kept for backwards compatibility: existing app code reads the
        // ? pool straight off the Server object via Context "dbconnection"
        $server->pdo = $this->pool;
    }

    public function release(): void
    {
        DBContext::releaseConnection();
    }

    public function ping(): bool
    {
        try {
            DBContext::getConnection()->query("SELECT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function shutdown(): void
    {
        // ? closes the pool's channel and every connection sitting in it -
        // ? nothing left checked out mid-request at this point, since this
        // ? runs once at worker shutdown, after that worker has stopped
        // ? accepting new requests.
        $this->pool->close();
    }

    private function sqlite(array $config): PDOPool
    {
        $conf = new PDOConfig();
        $conf
            ->withDriver($config["config"]["driver"])
            ->withDbname($config["config"]["database"]);

        return new PDOPool($conf, $config["config"]["pool"]);
    }

    private function mysql(array $config): PDOPool
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

        return new PDOPool($conf, $config["config"]["pool"]);
    }

    private function pgsql(array $config): PDOPool
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

        return new PDOPool($conf, $config["config"]["pool"]);
    }
}
