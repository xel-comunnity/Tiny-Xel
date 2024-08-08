<?php
use Tiny\Xel\Server\Applications;

require __DIR__."/../vendor/autoload.php";

$server = require __DIR__."/config/server.php";
$db = require __DIR__."/config/db.php";
$provider = require __DIR__."/config/provider.php";

// ? fire instance
$app = new Applications($server, $db);

$app
    ->__setProvider($provider)
    ->__init();

























// use Swoole\Coroutine\Channel;
// use Swoole\Http\Server;
// use function Swoole\Coroutine\go;
// use function Tiny\Xel\Provider\__db;

// use Swoole\Coroutine;
// use Swoole\Database\PDOConfig;
// use Swoole\Database\PDOPool;




// ? init config
    // ? Provider (env ,DB, Middleware, CronJob, Container Injection)

    // ? Event Server



// // ? init server 

// $app->__init();

// class Context
// {
//     protected static $pool = [];

//     static function get($key)
//     {
//         $cid = Coroutine::getuid();
//         if ($cid < 0)
//         {
//             return null;
//         }
//         if(isset(self::$pool[$cid][$key])){
//             return self::$pool[$cid][$key];
//         }
//         return null;
//     }

//     static function put($key, $item)
//     {
//         $cid = Coroutine::getuid();
//         if ($cid > 0)
//         {
//             self::$pool[$cid][$key] = $item;
//         }

//     }

//     static function delete($key = null)
//     {
//         $cid = Coroutine::getuid();
//         if ($cid > 0)
//         {
//             if($key){
//                 unset(self::$pool[$cid][$key]);
//             }else{
//                 unset(self::$pool[$cid]);
//             }
//         }
//     }
// }


// class ConnectionPool
// {
//     private $pool;
//     private $size;

//     public function __construct(bool $active = false, int $size = 1)
//     {
//         if($active === false){
//             $this->size = 1;
//             $this->pool = new Channel($size);
//             $this->pool->push($this->createConnection());
//             echo $this->pool->length();

//         }else{
//             $this->size = $size;
//             $this->pool = new Channel($size);
//             for ($i = 0; $i < $this->size; $i++) {
//                 $this->pool->push($this->createConnection());
//             }
//         }

//     }

//     private function createConnection()
//     {
//         $dsn = 'mysql:host=127.0.0.1;dbname=blogs';
//         $username = 'root';
//         $password = 'Todokana1ko!';
//         $options = [
//             PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//             PDO::ATTR_PERSISTENT => true,
//         ];
//         return new PDO($dsn, $username, $password, $options);
//     }

//     public function getConnection()
//     {
//         return $this->pool->pop();
//     }

//     public function releaseConnection($connection)
//     {
//         $this->pool->push($connection);
//     }
// }





// // Initialize the Swoole HTTP server
// $server = new Server("127.0.0.1", 9501, SWOOLE_PROCESS);
// $server->set([
//     "worker_num" => swoole_cpu_num(),
//     'log_level' => SWOOLE_LOG_ERROR,
// ]);


// // Use a worker ID to store the pool in worker scope
// $server->on("workerStart", function (Server $server, $worker_id){
   
//     // ? sqlite driver
//     $mysql =  [
//         'driver'   => 'mysql',
//         "host" => "localhost",
//         "port" => 3306,
//         "db" => "blogs",
//         "username" => "root",
//         "password" => "Todokana1ko!",
//         "charset" => "utf8mb4",
//         "pool" => 10,
    
//     ];

//     // ? sqlite driver
//     $sqlite = [
//         'driver'   => 'sqlite',
//         'database' => __DIR__."/database/xel.sqlite",
//         "pool" => 10,

//     ];


//     // ? db init 
//     __db($server, $sqlite);
// });


// $server->on("request", function ($request, $response) use($server){
    
//     if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
//         $response->end();
//         return;
//     }

//     if(isset($server->error)){
//         $response->header('Content-Type', 'application/json');
//         $response->status(500);
//         $response->end(json_encode($server->error));
//     }

//     go(function() use ($response, $server) {

//         // $pool = $server->setting['data'];
//         $pool = $server->pdo;
//         $conn = null;

//         try {
//             $conn = $pool->get();
//             // Use the connection to perform database operations
//             $stmt = $conn->query("SELECT * FROM users");
//             $result = $stmt->fetchAll();

//             $response->header("Content-Type", "application/json");
//             $response->end(json_encode($result));
//         } catch (Exception $e) {
//             $response->status(500);
//             $response->end("Internal Server Error");
//         } finally {
//             if ($conn!== null) {
//                 $pool->put($conn);
//             }
//         }        
//     });
// });


// $server->start();




// $pool = $server->pdo;


// $conn = $pool->get();
//     try {
//         // Create the users table if it doesn't exist
//         $conn->exec("
//             CREATE TABLE IF NOT EXISTS users (
//                 id INTEGER PRIMARY KEY AUTOINCREMENT,
//                 name VARCHAR(100),
//                 email VARCHAR(100),
//                 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
//             )
//         ");

//         // Insert dummy data if the table is empty
//         $stmt = $conn->query("SELECT COUNT(*) AS count FROM users");
//         $result = $stmt->fetch();
//         if ($result['count'] == 0) {
//             $conn->exec("
//                 INSERT INTO users (name, email) VALUES
//                 ('John Doe', 'john@example.com'),
//                 ('Jane Doe', 'jane@example.com')
//             ");
//         }
//     } catch (Exception $e) {
//         echo "Failed to initialize the database: " . $e->getMessage() . "\n";
//     } finally {
//         $pool->put($conn);
//     }






// use Tiny\Xel\Server\Applications;

// require __DIR__."/../vendor/autoload.php";

