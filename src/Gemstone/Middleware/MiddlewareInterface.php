<?php

namespace Tiny\Xel\Gemstone\Middleware;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface MiddlewareInterface
{
    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     */
    public function handle(
        Request $request,
        Response $response,
        \Closure $next
    );
}
