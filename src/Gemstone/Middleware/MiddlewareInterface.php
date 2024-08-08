<?php 

namespace Tiny\Xel\Gemstone\Middleware;
use Swoole\Http\Request;
use Swoole\Http\Response;

interface MiddlewareInterface{
    public function handle(Request $request, Response $response, \Closure $next);
}