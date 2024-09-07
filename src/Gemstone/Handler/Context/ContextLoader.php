<?php

namespace Tiny\Xel\Gemstone\Handler\Context;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Throwable;

# context
use Tiny\Xel\Context\Context;

/**
 *@param Request $request
 *@param Response $response
 *@param Server $server
 */
function __init__context(Request $request, Response $response, Server $server)
{
    try {
        // ? define system context
        __system__context($request, $response, $server);

        // ? define custom context on fly init
        __fly__register__context($server, $request, $response);

        // ? define custom context on pre init
        __pre__register__context($server, $request, $response);
    } catch (Throwable $e) {
        $response->end(
            json_encode([
                "status" => "error",
                "message" => $e->getMessage(),
                "error-trace" => $e->getTrace(),
            ])
        );
    }
}

function __flush_context()
{
    Context::clear();
}

function __system__context(Request $request, Response $response, Server $server)
{
    // ? boot context http
    Context::set("request", $request);
    Context::set("response", $response);

    // router , db, middleware provider
    Context::set("dbconnection", $server->{'pdo'});
    Context::set("router_init", $server->{'middleware_queue'});
    Context::set("middleware_queue", $server->{'middleware_queue'});
}

/**
 *@param Server $server
 */
function __fly__register__context(Server $server)
{
    // ? init an instance
    if (isset($server->{'fly-injection'})) {
        // ? get response context
        $response = Context::get("response");

        // ? list fly injection
        $data = $server->{'fly-injection'};
        foreach ($data as $key => $value) {
            try {
                Context::set($key, new $value());
            } catch (Throwable $e) {
                $response->end(
                    json_encode([
                        "status" => $e->getMessage(),
                        "error-trace" => $e->getTrace(),
                    ])
                );
            }
        }
    }
}

/**
 *@param Server $server
 */
function __pre__register__context(Server $server)
{
    // ? init an instance
    if (isset($server->{'pre-injection'})) {
        // ? get response context
        $response = Context::get("response");
        // ? list fly injection
        $data = $server->{'pre-injection'};
        foreach ($data as $key => $value) {
            try {
                Context::set($key, new $value());
            } catch (Throwable $e) {
                $response->end(
                    json_encode([
                        "status" => $e->getMessage(),
                        "error-trace" => $e->getTrace(),
                    ])
                );
            }
        }
    }
}
