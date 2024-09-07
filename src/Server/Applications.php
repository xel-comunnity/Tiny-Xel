<?php

declare(strict_types=1);

namespace Tiny\Xel\Server;

# Server Lib
use Swoole\Http\Server;
use Swoole\Http\Response;
use Swoole\Http\Request;

# Provider Lib
use function Tiny\Xel\Provider\{__boot_app};
use function Tiny\Xel\Gemstone\Handler\{__requestHandler};
use function Tiny\Xel\Gemstone\Handler\Context\{
    __init__context,
    __flush_context
};

class Applications
{
    public Server $server;

    private array $provider = [];

    /**
     * @param array<int, mixed> $provider
     * @return Applications
     */
    public function __setProvider(array $provider): Applications
    {
        $this->provider = $provider;
        return $this;
    }

    // ? server boot
    public function __init(): void
    {
        // ? server init
        $this->server = new Server(
            $this->provider["server"]["api"]["api"]["host"],
            $this->provider["server"]["api"]["api"]["port"],
            $this->provider["server"]["api"]["api"]["mode"],
            $this->provider["server"]["api"]["api"]["sock"]
        );

        // ? server setup
        $this->server->set($this->provider["server"]["api"]["api"]["options"]);

        // ? server events
        $this->server->on("workerStart", [$this, "onWorkerStart"]);
        $this->server->on("request", [$this, "onRequest"]);
        $this->server->on("task", [$this, "onTask"]);

        $this->server->start();
        
    }

    /////////////////////////////////////////////////////////////////////////// ? server event handler

    /**
     * @return void
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        // ? boot  server provider
        __boot_app($server, $this->provider);
    }

    // ? OnStart event : for handling Http Requests
    public function onRequest(Request $request, Response $response): void
    {
        // ? load context
        __init__context($request, $response, $this->server);

        // ? request handler
        __requestHandler($this->server, $request, $response);

        // ? flush context
        __flush_context();
    }

    // ? OnStart event : for handling process when server start
    /**
     * @return void
     */
    public function onStart(): void
    {
    }
    /**
     * @return void
     */
    public function onTask(): void
    {
    }

    /**
     * Summary of periodicReload
     * @return void
     */
    public function periodicReload(): void
    {
    }
}
