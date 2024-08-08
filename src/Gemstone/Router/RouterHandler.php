<?php 

namespace Tiny\Xel\Gemstone\Router;

use FastRoute\Dispatcher;
use Tiny\Xel\Context\Context;
use Tiny\Xel\Context\RequestContext;

# Swoole Server 
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

class RouterHandler{

    private array $dispatcher;
    private Request $request;
    private Response $response;

    private array $middleware;
    private \SplQueue $queue;
    
    private function dispatch(Server $server, array $handler, Request $request){
        // ? get uri
        $method =  $request->server["request_method"];
        $uri =  $request->server["request_uri"];

        // ? Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);

        }
        // ? decode url uri
        $uri = rawurldecode($uri);

        /**
         * @var \FastRoute\Dispatcher $dispatcher
        */
        $dispatcher = $handler['dispatcher'];

        $this->dispatcher = $dispatcher->dispatch($method, $uri);
       
    }

    private function middlewareDispatch(Request $request, Response $response){

        // Add middleware to the queue
        foreach ($this->middleware as $m) {
            $this->queue->enqueue(new $m());
        }

        // run middleware 
        $this->MiddlewareRunner($request, $response);
    }

    private function MiddlewareRunner(Request $request, Response $response){

        // Process the middleware queue
        while (!$this->queue->isEmpty()) {
            /**
             * @var \Tiny\Xel\Gemstone\Middleware\MiddlewareInterface $data
             */
            $data = $this->queue->dequeue();

            // Process the $data as needed
            $data->handle($request, $response, function($request, $response){
                $this->MiddlewareRunner($request, $response);
            });
        }
    }

    public function handler(Server $server , array $handler){
        // ? Get Context request & response
        $request =  RequestContext::getRequest();
        $response = RequestContext::getResponse();

        // ? get middleware queue
        $this->queue = Context::get('middleware_queue');


        $this->dispatch($server, $handler, $request);

        $routeInfo = $this->dispatcher;

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $response->header('Content-Type', 'application/json');
                $response->status(404);
                $response->end(json_encode([
                    "error" => 404,
                    "message" => "The requested resource could not be found on this server."
                ]));
                
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response->header('Content-Type', 'application/json');
                $response->status(404);
                $response->end(json_encode([
                    "error" => 404,
                    "message" => "Method not allowed for this type  of request"
                ]));               
                
                break;

            case Dispatcher::FOUND:
                $handler = $routeInfo[1]['handler'];
                $this->middleware = $routeInfo[1]['middleware'];
                $vars = $routeInfo[2];

                // test shift 

                // ? middleware dispatcher 
                $this->middlewareDispatch($request, $response);
                
                // ? Check if the handler is a callable array (class method)
                if (is_array($handler)) {
                    $instance = new $handler[0](); // Create an instance of the class
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
