<?php

namespace Tiny\Xel\Gemstone\Handler;

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;
use Tiny\Xel\Exception\ExceptionRenderer;

/**
 *@param Server $server
 *@param Request $request
 *@param Response $response
 * Populate request, and before react router handler it will make sure revent double submit chrome  problem for fav icon
 */
function __requestHandler(Server $server, Request $request, Response $response)
{
    // ? Fav Icon Handler / replays a boot-time failure - either one means
    // ? the response is already sent and this request is done.
    if (__favIconHandler($server, $request, $response)) {
        return;
    }

    // ? Anything uncaught from here on - routing (404/405), middleware, or
    // ? the route handler itself - previously propagated straight out of
    // ? onRequest() with nothing catching it: the client's connection would
    // ? just hang instead of ever getting a response. This is the single
    // ? place that guarantees one always gets sent.
    try {
        // ? bindFLy handler injection
        __fly_injection_init(server: $server);

        // ? router handler process
        __router_handler($server);
    } catch (Throwable $e) {
        ExceptionRenderer::respond($response, $e);
    }
}

/**
 *@param Server $server
 *@param Request $request
 *@param Response $response
 * fav icon error handler for chorome browser. Returns true when it has
 * already fully handled (and ended) the response, so the caller must not
 * continue processing this request any further.
 */
function __favIconHandler(Server $server, Request $request, Response $response): bool
{
    if (
        $request->server["path_info"] == "/favicon.ico" ||
        $request->server["request_uri"] == "/favicon.ico"
    ) {
        $response->end();
        return true;
    }

    if (isset($server->error)) {
        // ? a boot-time failure (see ProviderManager::__db/__provider) -
        // ? replayed safely on every request until the worker restarts,
        // ? instead of falling through into a router/context that never
        // ? finished booting.
        ExceptionRenderer::respond($response, $server->{'error'});
        return true;
    }

    return false;
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
