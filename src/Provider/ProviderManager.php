<?php
namespace Tiny\Xel\Provider;

use Exception;
use Swoole\Http\Server;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Tiny\Xel\Gemstone\Router\RouterHandler;
use SplQueue;

/////////////////////////////////////////////////////////////////////////// ? DB Init 
function __db(Server $server, array $config){
    // ? pdo config
    try {
        $data = match($config['driver']) {
            "mysql" => __mysql($config),
            "sqlite" => __sqlite($config),
            "pgsql" => __pgsql($config),
            default => throw new Exception('Unsupported Driver')
        };

        // ? dbmanager
        $server->pdo = $data;

    } catch (Exception $e) { 
       $server->error  = [
            "error-message" => $e->getMessage(),
            "error-line" => $e->getLine(),
            "error-trace" => $e->getTrace(),
        ];
    }
}

function __sqlite($config): PDOPool {


    $conf = new PDOConfig();
    $conf->withDriver($config['driver'])
         ->withDbname($config['database']);

    $pdoPool = new PDOPool($conf, $config['pool']);
    return $pdoPool;
}

function __mysql($config): PDOPool {
    $conf = new PDOConfig();
    $conf->withDriver($config['driver'])
        ->withHost($config['host'])
        ->withPort($config['port'])
        ->withDbName($config['db'])
        ->withCharset($config['charset'])
        ->withUsername($config['username'])
        ->withPassword($config['password']);
    $pdoPool = new PDOPool($conf, $config['pool']);

    return $pdoPool;
}

function __pgsql($config): PDOPool {
    $conf = new PDOConfig();
    $conf->withDriver($config['driver'])
        ->withHost($config['host'])
        ->withPort($config['port'])
        ->withDbName($config['db'])
        ->withCharset($config['charset'])
        ->withUsername($config['username'])
        ->withPassword($config['password']);
    $pdoPool = new PDOPool($conf, $config['pool']);

    return $pdoPool;
}



/////////////////////////////////////////////////////////////////////////// ? Provider Init 

// ? load Provider 
function __provider(Server $server, array $config = []) {


        foreach($config as $key => $value){
            try{
            $server->{$key} = match ($key) {
                "router", "scheduler", "websocket", "custom" => $value,
                default => throw new Exception('Unsupported Provider key')
            };
        }catch(Exception $e){
            $server->error  = [
                "error-message" => $e->getMessage(),
                "error-line" => $e->getLine(),
                "error-trace" => $e->getTrace(),
            ];

        }
    }
   
}

/////////////////////////////////////////////////////////////////////////// ? Instance Init 

function __instance_init(Server $server) {
    $router = new RouterHandler();
    $middlewareQueue = new SplQueue();
    $server->{'router_init'} = $router;
    $server->{'middleware_queue'} = $middlewareQueue;
}
