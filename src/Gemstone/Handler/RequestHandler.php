<?php

namespace Tiny\Xel\Gemstone\Handler;

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 *@param Server $server
 *@param Request $request
 *@param Response $response
 * Populate request, and before react router handler it will make sure revent double submit chrome  problem for fav icon
 */
function __requestHandler(Server $server, Request $request, Response $response)
{
    // ? Fav Icon Handler
    __favIconHandler($server, $request, $response);

    // ? bindFLy handler injection
    __fly_injection_init(server: $server);

    // ? router handler process
    __router_handler($server);
}

/**
 *@param Server $server
 *@param Request $request
 *@param Response $response
 *  fav icon error handler for chorome browser
 */
function __favIconHandler(Server $server, Request $request, Response $response)
{
    if (
        $request->server["path_info"] == "/favicon.ico" ||
        $request->server["request_uri"] == "/favicon.ico"
    ) {
        $response->end();
        return;
    }

    if (isset($server->error)) {
        $response->header("Content-Type", "application/json");
        $response->status(500);

        $response->end(json_encode($server->{'error'}));
    }
}

////////////////////////////////////////////////////////////////////////////////////////// ? Instance handler
/**
 *@param Server $server
 */
function __router_handler(Server $server)
{
    /**
     * @var \Tiny\Xel\Gemstone\Router\RouterHandler $router
     */
    $router = $server->{'router_init'};

    /**
     * @var array $config
     */
    $config = $server->{'router'};
    $router->handler($server, $config);
}

/**
 *@param Server $server
 */
function __fly_injection_init(Server $server)
{
    $bindFly = [];
    if (isset($server->{'fly-injection'})) {
        $fly = $server->{'fly-injection'};
        foreach ($fly as $key => $value) {
            $bindFly[$key] = new $value();
        }
        $server->{'bindFly'} = $bindFly;
    }
}
