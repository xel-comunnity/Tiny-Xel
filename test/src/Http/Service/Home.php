<?php

namespace Tiny\Test\Http\Service;
use Exception;
use Tiny\Test\Provider\Composition\Request;
use Tiny\Test\Provider\Composition\Response;
use Tiny\Test\Provider\Composition\HttpCode;
use Tiny\Xel\Context\Context;
use Tiny\Xel\Context\DBContext;

final class Home
{
    use Request, Response;

    public function index(): void
    {
        $conn = null;
        try {
            /**
             * @var \Swoole\Database\PDOPool $driver
             */
            $driver = Context::get("dbconnection");

            /**
             * @var \Swoole\Database\PDOProxy $conn
             */
            $conn = $driver->get();
            // Use the connection to perform database operations
            $stmt = $conn->query("SELECT id, name, email FROM users");
            $result = $stmt->fetchAll();

            $this->response()->Json($result, HttpCode::CREATED);
        } catch (Exception $e) {
            // RequestContext::json($e->getTrace(), 500);
            $this->response()->Json($e->getMessage(), 500);
        } finally {
            $conn->reset();
            if ($conn !== null) {
                $driver->put($conn);
            }
        }
    }

    public function view(): void
    {
        $conn = null;
        try {
            /**
             * @var \Swoole\Database\PDOPool $driver
             */
            $driver = Context::get("dbconnection");

            /**
             * @var \Swoole\Database\PDOStatementProxy $conn
             */
            $conn = $driver->get();

            // Define the number of rows to insert and the chunk size
            $totalRows = 1000000;
            $chunkSize = 1000;

            // Prepare the insert statement
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email) VALUES (:name, :email)"
            );

            // Use coroutine channels to manage concurrency
            $channel = new \Swoole\Coroutine\Channel(1);

            // Create a coroutine for each chunk of data
            for ($i = 0; $i < $totalRows; $i += $chunkSize) {
                \Swoole\Coroutine::create(function () use (
                    $stmt,
                    $i,
                    $chunkSize,
                    $channel
                ) {
                    $dataChunk = [];
                    for ($j = 0; $j < $chunkSize; $j++) {
                        $timestamp = time();
                        $randomString = bin2hex(random_bytes(4));
                        $dataChunk[] = [
                            "name" => "User_{$timestamp}_{$randomString}",
                            "email" => "user_{$timestamp}_{$randomString}@example.com",
                        ];
                    }

                    // Insert each row in the chunk
                    foreach ($dataChunk as $newUser) {
                        $stmt->execute($newUser);
                    }

                    // Send the result to the channel
                    $channel->push(true);
                });
            }

            // Wait for all coroutines to finish
            for ($i = 0; $i < $totalRows / $chunkSize; $i++) {
                $channel->pop();
            }

            // Close the channel
            $channel->close();

            $this->response()->Json(
                ["message" => "Insertion completed"],
                HttpCode::CREATED
            );
        } catch (Exception $e) {
            $this->response()->Json(
                ["error" => $e->getMessage(), $e->getTrace()],
                HttpCode::INTERNAL_SERVER_ERROR
            );
        } finally {
            if ($conn !== null) {
                DBContext::releaseConnection();
            }
        }
    }

    public function data(): void
    {
        $conn = null;
        try {
            /**
             * @var \Swoole\Database\PDOPool $driver
             */
            $driver = Context::get("dbconnection");

            /**
             * @var \Swoole\Database\PDOStatementProxy $conn
             */
            $conn = $driver->get();
            // Define pagination parameters
            $page = $this->request()->query("page", 1); // Default to page 1 if not provided
            $pageSize = $this->request()->query("page_size", 20); // Default to 20 records per page
            $offset = ($page - 1) * $pageSize;

            // Fetch the paginated results
            $stmt = $conn->prepare(
                "SELECT * FROM users LIMIT :limit OFFSET :offset"
            );
            $stmt->bindParam(":limit", $pageSize, \PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Fetch total count of records for pagination metadata
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $total = $stmt->fetchColumn();

            $response = [
                "data" => $results,
                "pagination" => [
                    "total" => $total,
                    "page" => $page,
                    "page_size" => $pageSize,
                    "total_pages" => ceil($total / $pageSize),
                ],
            ];

            $this->response()->Json($response, HttpCode::OK);
        } catch (Exception $e) {
            $this->response()->Json(
                ["error" => $e->getMessage(), $e->getTrace()],
                HttpCode::INTERNAL_SERVER_ERROR
            );
        } finally {
            if ($conn !== null) {
                DBContext::releaseConnection();
            }
        }
    }
}
