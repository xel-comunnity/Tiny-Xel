<?php 

namespace Tiny\Xel\Gemstone\Handler;
use Tiny\Xel\Context\RequestContext;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;


function __requestHandler(Server $server) {
    // ? get context
    $request =  RequestContext::getRequest();
    $response = RequestContext::getResponse();

    // ? Fav Icon Handler
    __favIconHandler($server, $request , $response);

    __router_handler($server);
   
}

// ? fav icon error handler for chorome browser 
function __favIconHandler(Server $server, Request $request, Response $response) {
    

    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->end();
        return;
    }

    if(isset($server->error)){
        $response->header('Content-Type', 'application/json');
        $response->status(500);

        $response->end(json_encode($server->{'error'}));
    }
}


////////////////////////////////////////////////////////////////////////////////////////// ? Instance handler

function __router_handler(Server $server){
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


////////////////////////////////////////////////////////////////////////////////////////// ?  request handler



