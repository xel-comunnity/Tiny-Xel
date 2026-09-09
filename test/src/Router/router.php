<?php

namespace Tiny\Test\Router;

require __DIR__."/../../../vendor/autoload.php";

# Middleware class


# Router Lib
use Tiny\Test\Http\Service\Home;
use Tiny\Test\Http\Service\EloquentDemo;
use Tiny\Xel\Gemstone\Router\Router;
use Tiny\Xel\Health\HealthCheckHandler;

$router = new Router();

// ? set global middleware
$router->setGlobalMiddleware([
   // Auth::class
]);

// ? checks the active db driver's connectivity - see
// ? src/Health/HealthCheckHandler.php and DriverContract::ping()
$router->GET("/health", [HealthCheckHandler::class, "handle"]);

// ? router config
$router->Group(['prefix' => "/api"], function (Router $router) {
    $router->Group(['prefix' => "/v1"], function (Router $router) {
        $router->GET("/", [Home::class, "index"]);
        $router->GET("/view", [Home::class, "view"]);
        $router->POST("/data", [Home::class, "data"]);

        // ? demonstrates the "eloquent" db driver contract - see
        // ? test/config/provider.php ("db.contract") and test/migrate.php
        $router->GET("/eloquent-users", [EloquentDemo::class, "index"]);
        $router->POST("/eloquent-users", [EloquentDemo::class, "store"]);
    });
});

// ? Dispactch router ? u set to false for cache mode (only use on prodcution)

return $router->getDispatcher(
    true,
    __DIR__ . "/../Writeable/cache/route.cache"
);
