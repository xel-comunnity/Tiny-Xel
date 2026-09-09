<?php

namespace Tiny\Xel\Gemstone\Handler\Context;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Throwable;

# context
use Tiny\Xel\Context\Context;
use Tiny\Xel\Context\RequestContext;
use Tiny\Xel\Context\ErrorContext;
use Tiny\Xel\Context\DBContext;

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
    // ? Every coroutine-scoped store keyed by Coroutine::getuid() must be
    // ? cleared at the end of its request. Coroutine ids are not reused
    // ? while the worker is alive, so leaving any one of these pools
    // ? unflushed leaks that request's data (and, for DBContext, a pooled
    // ? PDO connection) for the lifetime of the worker process.
    Context::clear();
    RequestContext::clear();
    ErrorContext::clear();
    DBContext::releaseConnection();
}

function __system__context(Request $request, Response $response, Server $server)
{
    // ? boot context http - isolated per coroutine via Context::set/get
    Context::set("request", $request);
    Context::set("response", $response);

    // ? db provider (a coroutine-safe PDOPool, not a single connection)
    Context::set("dbconnection", $server->{'pdo'});
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
