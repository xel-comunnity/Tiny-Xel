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
use Tiny\Xel\Database\Contract\DriverContract;
use Tiny\Xel\Exception\ExceptionRenderer;

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
        // ? previously dumped $e->getTrace() straight into the response
        // ? body - ExceptionRenderer never leaks that to the client, only
        // ? to the server log.
        ExceptionRenderer::respond($response, $e);
    }
}

function __flush_context()
{
    // ? Let the active db driver release its own per-request state first
    // ? (e.g. SwoolePoolDriver returns its pooled PDO connection, Eloquent
    // ? trims its query log) - it needs to run before Context::clear() below
    // ? removes the "db_driver" entry it's read from.
    $driver = Context::get("db_driver");
    if ($driver instanceof DriverContract) {
        $driver->release();
    }

    // ? Every coroutine-scoped store keyed by Coroutine::getuid() (or by the
    // ? shared blocking-mode slot - see Context::scopeId) must be cleared at
    // ? the end of its request. Coroutine ids are not reused while the
    // ? worker is alive, so leaving any one of these pools unflushed leaks
    // ? that request's data (and, for DBContext, a pooled PDO connection)
    // ? for the lifetime of the worker process.
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

    // ? lets Tiny\Xel\Task\TaskDispatcher hand a task off to
    // ? $server->task() from anywhere in a route handler/middleware,
    // ? without every caller needing the Server instance threaded through.
    Context::set("server", $server);

    // ? RequestContext keeps its own per-coroutine pool (see
    // ? Context::scopeId) so its json()/text()/download()/... helpers work
    // ? off the current request without every handler having to fetch the
    // ? response via Context::get("response") first.
    RequestContext::setRequest($request);
    RequestContext::setResponse($response);

    // ? db driver contract (see Tiny\Xel\Database\Contract\DriverContract) -
    // ? not every driver exposes a raw PDOPool (EloquentDriver doesn't), so
    // ? both are set only when the active driver actually provides them.
    if (isset($server->{'db_driver'})) {
        Context::set("db_driver", $server->{'db_driver'});
    }
    if (isset($server->{'pdo'})) {
        Context::set("dbconnection", $server->{'pdo'});
    }
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
                // ? stop at the first failure - carrying on would just risk
                // ? ending an already-ended response for every injection
                // ? after this one (ExceptionRenderer guards against that
                // ? too, but there's nothing useful left to do here).
                ExceptionRenderer::respond($response, $e);
                return;
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
                ExceptionRenderer::respond($response, $e);
                return;
            }
        }
    }
}
