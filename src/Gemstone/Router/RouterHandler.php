<?php

namespace Tiny\Xel\Gemstone\Router;

use FastRoute\Dispatcher;
use Tiny\Xel\Context\Context;
use Tiny\Xel\Exception\MethodNotAllowedException;
use Tiny\Xel\Exception\NotFoundException;

# Swoole Server
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

class RouterHandler
{
    /**
     * A single RouterHandler instance is shared by every worker (see
     * ProviderManager::__instance_init) and its methods run inside a new
     * coroutine per HTTP request. Route info, the middleware list and the
     * middleware queue must therefore stay local to each handler() call
     * instead of being stored as instance properties - otherwise concurrent
     * requests overwrite each other's routing/middleware state mid-flight.
     *
     *@param Server $$server
     *@param array<int, mixed> $handler
     *@param Request $request
     *@return array<int, mixed>
     */

    private function dispatch(
        Server $server,
        array $handler,
        Request $request
    ): array {
        // ? get uri
        $method = $request->server["request_method"];
        $uri = $request->server["request_uri"];

        // ? Strip query string (?foo=bar) and decode URI
        if (false !== ($pos = strpos($uri, "?"))) {
            $uri = substr($uri, 0, $pos);
        }
        // ? decode url uri
        $uri = rawurldecode($uri);

        /**
         * @var \FastRoute\Dispatcher $dispatcher
         */
        $dispatcher = $handler["dispatcher"];

        return $dispatcher->dispatch($method, $uri);
    }

    /**
     *@param array<int, mixed> $middleware
     *@param Request $request
     *@param Response $response
     */

    private function middlewareDispatch(
        array $middleware,
        Request $request,
        Response $response
    ): void {
        // ? Build a queue scoped to this request only; a shared/pooled queue
        // ? would be enqueued/dequeued by every concurrent coroutine at once.
        $queue = new \SplQueue();
        foreach ($middleware as $m) {
            $queue->enqueue(new $m());
        }

        // run middleware
        $this->MiddlewareRunner($queue, $request, $response);
    }

    private function MiddlewareRunner(\SplQueue $queue, Request $request, Response $response)
    {
        // Process the middleware queue
        while (!$queue->isEmpty()) {
            /**
             * @var \Tiny\Xel\Gemstone\Middleware\MiddlewareInterface $data
             */
            $data = $queue->dequeue();

            // Process the $data as needed
            $data->handle($request, $response, function ($request, $response) use ($queue) {
                $this->MiddlewareRunner($queue, $request, $response);
            });
        }
    }

    public function handler(Server $server, array $handler)
    {
        // ? Get Context request & response - Context isolates these per
        // ? coroutine, so this is safe to read concurrently across requests
        $request = Context::get("request");
        $response = Context::get("response");

        // ? route info is a local variable: never shared across requests
        $routeInfo = $this->dispatch($server, $handler, $request);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // ? __requestHandler's catch-all renders this consistently
                // ? with every other error in the system (see
                // ? Tiny\Xel\Exception\ExceptionRenderer). Previously built
                // ? its own one-off JSON shape here.
                throw new NotFoundException();

            case Dispatcher::METHOD_NOT_ALLOWED:
                // ? previously sent status(404) here despite the message
                // ? saying "method not allowed" - the correct code is 405.
                throw new MethodNotAllowedException();

            case Dispatcher::FOUND:
                $handler = $routeInfo[1]["handler"];
                $middleware = $routeInfo[1]["middleware"];
                $vars = $routeInfo[2];

                // ? middleware dispatcher
                $this->middlewareDispatch($middleware, $request, $response);

                // ? Check if the handler is a callable array (class method)
                if (is_array($handler)) {
                    $instance = new ($handler[0])(); // Create an instance of the class
                    $method = $handler[1]; // Get the method name
                    call_user_func_array([$instance, $method], $vars); // Call the method with parameters
                } else {
                    // Handle global functions
                    call_user_func($handler, ...$vars);
                }

                break;
        }
    }
}
