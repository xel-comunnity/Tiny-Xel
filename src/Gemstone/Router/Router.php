<?php

namespace Tiny\Xel\Gemstone\Router;

use FastRoute\RouteCollector;

class Router
{

    /**
     * Summary of routes
     * @var array
     */
    protected array $routes = [];

    /**
     * Summary of currentGroup
     * @var array
     */
    protected array $currentGroup = ['prefix' => '', 'middleware' => []];


    /**
     * Summary of groupStack
     * @var array
     */
    protected array $groupStack = [];

    /**
     * Summary of currentMiddleware
     * @var array
     */
    protected array $currentMiddleware = [];

    
    /**
     * Summary of globalMiddleware
     * @var array
     */
    protected array $globalMiddleware = [];




    //////////////////////////////////////////////////////////// ? Normal Request

    /**
     * Summary of HEAD
     * @param string $route
     * @param mixed $handler
     * @return void
     */
    public function HEAD(string $route, $handler): void
    {
        $this->addRoute('HEAD', $route, $handler);
    }


    /**
     * Summary of GET
     * @param string $route
     * @param mixed $handler
     * @return void
     */
    public function GET(string $route, $handler): void
    {
        $this->addRoute('GET', $route, $handler);
    }

  
    /**
     * Summary of POST
     * @param string $route
     * @param mixed $handler
     * @return void
     */
    public function POST(string $route, $handler): void
    {
        $this->addRoute('POST', $route, $handler);
    }

    /**
     * Summary of PUT
     * @param string $route
     * @param mixed $handler
     * @return void
     */
    public function PUT(string $route, $handler): void
    {
        $this->addRoute('PUT', $route, $handler);
    }

    /**
     * Summary of PATCH
     * @param string $route
     * @param mixed $handler
     * @return void
     */
    public function PATCH(string $route, $handler): void
    {
        $this->addRoute('PATCH', $route, $handler);
    }

    /**
     * Summary of DELETE
     * @param string $route
     * @param mixed $handler
     * @return void
     */
    public function DELETE(string $route, $handler): void
    {
        $this->addRoute('DELETE', $route, $handler);
    }

    //////////////////////////////////////////////////////////// ? Group Router

    /**
     * Summary of Group
     * @param array $attributes
     * @param callable $callback
     * @return void
    */
    public function Group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;

        $callback($this);

        array_pop($this->groupStack);
    }

     /**
     * Calculate the current group's prefix and middleware
     * @return array
     */
    protected function getCurrentGroup(): array
    {
        $group = [
            'prefix' => '',
            'middleware' => []
        ];

        foreach ($this->groupStack as $stackGroup) {
            if (isset($stackGroup['prefix'])) {
                $group['prefix'] = $this->normalizePath($group['prefix'] . '/' . trim($stackGroup['prefix'], '/'));
            }
            if (isset($stackGroup['middleware'])) {
                $group['middleware'] = array_merge(
                    $group['middleware'],
                    (array)$stackGroup['middleware']
                );
            }
        }

        return $group;
    }

    /**
     * Normalize the path to ensure a leading slash and no double slashes
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * Summary of Middleware
     * @param array|string $middleware
     * @return static
     */
    public function Middleware(array|string $middleware):static
    {
        if (is_array($middleware)) {
            $this->currentMiddleware = array_merge($this->currentMiddleware, $middleware);
        } else {
            $this->currentMiddleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Summary of setGlobalMiddleware
     * @param array|string $middleware
     * @return void
     */
    public function setGlobalMiddleware(array|string $middleware): void
    {
        if (is_array($middleware)) {
            $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        } else {
            $this->globalMiddleware[] = $middleware;
        }
    }

    //////////////////////////////////////////////////////////// ? Collect Router
    /**
     * Summary of addRoute
     * @param mixed $method
     * @param mixed $route
     * @param mixed $handler
     * @return void
     */
    private function addRoute($method, $route, $handler):void
    {
        // $fullRoute = !is_null($this->currentGroup['prefix']) ? $this->currentGroup['prefix'].$route : $route;
        $currentGroup = $this->getCurrentGroup();
        $fullRoute = $this->normalizePath($currentGroup['prefix'] . '/' . ltrim($route, '/'));
        $this->routes[] = [
            'method' => $method,
            'route' => $fullRoute,
            'handler' => $handler,
            'middleware' => array_merge($this->globalMiddleware, $currentGroup['middleware'] ?? [], $this->currentMiddleware)
        ];

        $this->currentMiddleware = []; // Reset middleware after adding route
    }
    
    /**
     * Summary of getRoutes
     * @return array
     */
    public function getRoutes():array
    {
        return $this->routes;
    }


    /**
     * Summary of __collectRouter
     * @return callable
     */
    private function __collectRouter():callable
    {
        return function (RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route['method'], $route['route'], $route);
            }
        };
    }

    /**
     * Summary of getDispatcher
     * @param bool $active
     * @param mixed $path
     * @return \FastRoute\Dispatcher
     */
    public function getDispatcher(bool $active = true, $path = ""):\FastRoute\Dispatcher
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
