<?php

declare(strict_types=1);

namespace Tiny\Xel\Health;

use Tiny\Xel\Context\Context;
use Tiny\Xel\Context\RequestContext;
use Tiny\Xel\Database\Contract\DriverContract;

/**
 * A ready-to-wire /health route: checks the active db driver's
 * connectivity (see DriverContract::ping()) and renders a standard JSON
 * body, no setup beyond adding the route itself.
 *
 * Wire it up:
 *   $router->GET("/health", [HealthCheckHandler::class, "handle"]);
 */
final class HealthCheckHandler
{
    public function handle(): void
    {
        $driver = Context::get("db_driver");
        $databaseHealthy = $driver instanceof DriverContract && $driver->ping();

        $healthy = $databaseHealthy;

        RequestContext::json(
            [
                "status" => $healthy ? "ok" : "degraded",
                "checks" => [
                    "database" => $databaseHealthy,
                ],
            ],
            $healthy ? 200 : 503
        );
    }
}
