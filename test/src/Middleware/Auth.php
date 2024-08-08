<?php 

namespace Tiny\Test\Middleware;
use Tiny\Xel\Context\RequestContext;
use Tiny\Xel\Gemstone\Middleware\MiddlewareInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

class Auth implements MiddlewareInterface{
    
    public function handle(Request $request, Response $response, \Closure $next){
        if($request->server["request_uri"] !== "/api/"){ 
            RequestContext::json("error : unauthorisized", 401);
        }

        $next($request, $response);
    }

}