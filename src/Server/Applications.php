<?php 

namespace Tiny\Xel\Server;
# Server Lib
use Swoole\Http\Server;
use Swoole\Http\Response;
use Swoole\Http\Request;

# Provider Lib
use Tiny\Xel\Context\DBContext;
use Tiny\Xel\Context\RequestContext;
use Tiny\Xel\Context\Context;
use function Tiny\Xel\Provider\{__db, __provider, __instance_init};
use function Tiny\Xel\Gemstone\Handler\__requestHandler;


class Applications{

    public Server $server;

    private array $provider = [];
    

    public function __construct(
        private array $configServer,
        private array $configDB,
    ){}


    public function __setProvider(array $provider){
        $this->provider = $provider;
        return $this;
    }

    // ? server boot 
    public function __init():void{
        // ? server init
        $this->server = new Server
        (
            $this->configServer['api']['host'], 
            $this->configServer['api']['port'],
            $this->configServer['api']['mode'],
            $this->configServer['api']['sock'],
        );
        
        // ? server setup
        $this->server->set($this->configServer['api']['options']);

        // ? server events
        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->on('request', [$this, 'onRequest']);

        $this->server->start();
    }

    /////////////////////////////////////////////////////////////////////////// ? server event handler
    
    // ? OnWorker event : for handling sharing state between IPC Worker 
    public function onWorkerStart(Server $server, int $workerId){
        // ? DB init
         __db($server, $this->configDB);

        // ? Provider init
        __provider($server ,$this->provider);

        // ? instance init 
        __instance_init($server);
    }

    // ? OnStart event : for handling Http Request
    public function onRequest(Request $request, Response $response){

        // ? set Context 
        RequestContext::setRequest($request);
        RequestContext::setResponse($response);
        
        // ? DB Context
        DBContext::setPool($this->server->{'pdo'});

        // ? Custom Context
        Context::set('router_init', $this->server->{'middleware_queue'}); 
        Context::set('middleware_queue', $this->server->{'middleware_queue'});
        Context::set('server', $this->server);  

        // ? request handler
        __requestHandler($this->server);

        // ? clear context
        RequestContext::clear();
        DBContext::releaseConnection();
        Context::clear();
    }

  
    // ? OnStart event : for handling process when server start
    public function onStart(){}
   
    
}





