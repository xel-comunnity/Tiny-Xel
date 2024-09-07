<?php

namespace Tiny\Test\Router;

require __DIR__."/../../../vendor/autoload.php";

# Middleware class


# Router Lib
use Tiny\Test\Http\Service\Home;
use Tiny\Xel\Gemstone\Router\Router;

$router = new Router();

// ? set global middleware
$router->setGlobalMiddleware([
   // Auth::class
]);

// ? router config
$router->Group(['prefix' => "/api"], function (Router $router) {
    $router->Group(['prefix' => "/v1"], function (Router $router) {
        $router->GET("/", [Home::class, "index"]);
        $router->GET("/view", [Home::class, "view"]);
        $router->POST("/data", [Home::class, "data"]);
    });
});

// ? Dispactch router ? u set to false for cache mode (only use on prodcution)

return $router->getDispatcher(
    true,
    __DIR__ . "/../Writeable/cache/route.cache"
);
