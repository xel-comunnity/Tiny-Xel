<?php 

namespace Tiny\Test\Service;
use Exception;
use Tiny\Test\Provider\Composition\Request;
use Tiny\Test\Provider\Composition\Response;
use Tiny\Test\Provider\Composition\HttpStatusCodes;
use Tiny\Xel\Context\DBContext;
use Tiny\Xel\Context\Context;
use Tiny\Xel\Context\RequestContext;

final class Home{

    use Request, Response;

    public function index():void{    
        $conn = null;
        try {
            /**
             * @var \Swoole\Database\PDOStatementProxy $conn
             */ 
            $conn = DBContext::getConnection();

            // Use the connection to perform database operations
            $stmt = $conn->query("SELECT * FROM users");
            $result = $stmt->fetchAll();
            
            // RequestContext::json($result, 200);

            $this->response()->Json($result, 201);

        }catch(Exception $e){
            // RequestContext::json($e->getTrace(), 500);
            $this->response()->Json($e->getMessage(), 500);

       
        } finally {
            if ($conn!== null) {
               DBContext::releaseConnection();
            }
        }        
    }

    // public function view():void{
    //     $conn = null;
    //     try {
    //         /**
    //          * @var \Swoole\Database\PDOStatementProxy $conn
    //          */
    //         $conn = DBContext::getConnection();

    //         // Generate unique user data
    //         $timestamp = time();
    //         $randomString = bin2hex(random_bytes(4)); // 8 character random string

    //         $newUser = [
    //             'name' => "User_{$timestamp}_{$randomString}",
    //             'email' => "user_{$timestamp}_{$randomString}@example.com",
    //             'created_at' => date('Y-m-d H:i:s', $timestamp)
    //         ];

    //         // Prepare the insert statement
    //         $stmt = $conn->prepare("INSERT INTO users (name, email, created_at) VALUES (:name, :email, :created_at)");

    //         // Execute the insert statement
    //         $stmt->execute($newUser);

    //         // Get the ID of the newly inserted row
    //         $newUserId = $conn->lastInsertId();

    //         // Fetch the newly inserted user to confirm the insertion
    //         $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    //         $stmt->execute(['id' => $newUserId]);
    //         $result = $stmt->fetch(\PDO::FETCH_ASSOC);

    //         $this->response()->Json($result, HttpStatusCodes::CREATED);
    //     } catch(Exception $e) {
    //         $this->response()->Json(['error' => $e->getMessage()], HttpStatusCodes::INTERNAL_SERVER_ERROR);
    //     } finally {
    //         if ($conn !== null) {
    //             DBContext::releaseConnection();
    //         }
    //     }
    // }

    // public function data() : void {
    //     $this->response()->Json(['message' => ' Post request successfully', 'user' =>  $this->request()->all()], HttpStatusCodes::CREATED);
    // }
}
