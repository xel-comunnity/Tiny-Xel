<?php 

namespace Tiny\Test\Router;
use Tiny\Test\Middleware\Protect;
use Tiny\Test\Service\Home;
use Tiny\Xel\Gemstone\Router\Router;

$router = new Router();

$router->setGlobalMiddleware([
    Protect::class
]);


$router->Group(["prefix" => "/api"], function(Router $router) {
    $router->GET("/", [Home::class,"index"]);
    $router->GET("/view", [Home::class,"view"]);
});


return $router->getDispatcher(true, __DIR__ ."/../Writeable/cache/route.cache");














// use FastRoute\RouteCollector;
// use Tiny\Test\Service\Home;

// require __DIR__."/../Service/callback.php";



// return function (RouteCollector $r) {
//     $r->addGroup("/class", function (RouteCollector $r) {  
//         $r->get("/", [Home::class, 'index']);
//         $r->get("/view", [Home::class, 'view']);
//     });

//     $r->addGroup("/callback", function (RouteCollector $r) {  
//         $r->get("/", 'home');
//         $r->get("/view", 'about');
//     });
// };


