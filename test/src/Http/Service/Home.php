<?php

namespace Tiny\Test\Http\Service;

# trait
use Tiny\Test\Provider\Composition\Request;
use Tiny\Test\Provider\Composition\Response;

# additional tools and context
use Tiny\Test\Provider\Composition\HttpCode;
use Tiny\Xel\Context\Context;

# exception lib
use Exception;

final class Home
{
    use Request;
    use Response;

    public function index(): void
    {
        $conn = null;
        try {
            /**
             * @var \Swoole\Database\PDOPool $driver
             */
            $driver = Context::get("dbconnection");

            /**
             * @var \PDO $conn
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
            if ($conn !== null) {

                $driver->put($conn);
            }
        }
    }
}
