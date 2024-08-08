<?php 
namespace Tiny\Xel\Gemstone\Router;
use FastRoute\RouteCollector;

class Router{

    protected array $routes = [];
    protected array $currentGroup = ['prefix' => '', 'middleware' => []];
    protected array $currentMiddleware = [];

    protected array $globalMiddleware = []; 

    //////////////////////////////////////////////////////////// ? Normal Request
    public function HEAD(string $route, $handler):void{$this->addRoute('HEAD', $route, $handler);}

    public function GET(string $route, $handler):void{$this->addRoute('GET', $route, $handler);}

    public function POST(string $route, $handler):void{$this->addRoute('POST', $route, $handler);}

    public function PUT(string $route, $handler):void{$this->addRoute('PUT', $route, $handler);}

    public function PATCH(string $route, $handler):void{$this->addRoute('PATCH', $route, $handler);}

    public function DELETE(string $route, $handler):void{$this->addRoute('DELETE', $route, $handler);}

    //////////////////////////////////////////////////////////// ? Group Router 
    
    public function Group(array $attributes, callable $callback)
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = array_merge($this->currentGroup, $attributes);

        $callback($this);

        $this->currentGroup = $previousGroup;
    }

    public function Middleware(array|string $middleware)
    {
        if (is_array($middleware)) {
            $this->currentMiddleware = array_merge($this->currentMiddleware, $middleware);
        } else {
            $this->currentMiddleware[] = $middleware;
        }
       
        return $this;
    }

    public function setGlobalMiddleware(array|string $middleware):void{
        if (is_array($middleware)) {
            $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        } else {
            $this->globalMiddleware[] = $middleware;
        }
    }

    //////////////////////////////////////////////////////////// ? Collect Router 
    private function addRoute($method, $route, $handler)
    {
        $fullRoute = !is_null($this->currentGroup['prefix']) ? $this->currentGroup['prefix'].$route : $route;
        $this->routes[] = [
            'method' => $method,
            'route' => $fullRoute,
            'handler' => $handler,
            'middleware' => array_merge($this->globalMiddleware, $this->currentGroup['middleware'] ?? [], $this->currentMiddleware)
        ];
        $this->currentMiddleware = []; // Reset middleware after adding route
    }

    public function getRoutes()
    {
        return $this->routes;
    }


    private function __collectRouter()
    {
        return function(RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route['method'], $route['route'], $route);
            }
        };
    }

    public function getDispatcher(bool $active = true, $path = "")
    {
        return \FastRoute\cachedDispatcher(
            $this->__collectRouter(),
            [
                "cacheFile" => $path,
                'cacheDisabled' => $active,
            ]
        );
    }
}
