<?php

namespace Tiny\Test\Http\Service;

use Tiny\Test\Model\User;
use Tiny\Test\Task\LogUserCreatedTask;
use Tiny\Xel\Context\RequestContext;
use Tiny\Xel\Task\TaskDispatcher;

/**
 * Demonstrates the "eloquent" db driver contract: plain Eloquent Model
 * usage, no PDOPool/DBContext plumbing needed. Requires:
 *   - test/config/provider.php: "db.contract" => "eloquent"
 *   - migrations applied first via `php test/migrate.php`
 *
 * No try/catch needed: the framework's global exception handler (see
 * Tiny\Xel\Exception\ExceptionRenderer, wired into __requestHandler)
 * already catches anything thrown here - a query failure, a validation
 * error, whatever - and renders it safely. Throw a
 * Tiny\Xel\Exception\XelException (or one of its ready-made subclasses)
 * for a specific status/error code, or just let an unexpected exception
 * bubble up as a generic 500.
 */
final class EloquentDemo
{
    public function index(): void
    {
        // ? User::all() has no bound - it fetches and JSON-encodes every
        // ? row in one go. Fine while the table is empty, but under any
        // ? real load (or after a load test that inserted thousands of
        // ? rows) that becomes a full table scan plus a huge in-memory
        // ? encode on a single blocking connection, which is slow enough
        // ? to make every concurrent request queue up behind it and time
        // ? out. forPage()/limit()+offset() are on the query builder
        // ? itself - no extra pagination package needed.
        $params = RequestContext::getQueryParams();

        $perPage = max(1, min((int) ($params["per_page"] ?? 25), 100));
        $page = max(1, (int) ($params["page"] ?? 1));

        $users = User::query()
            ->orderBy("id")
            ->forPage($page, $perPage)
            ->get();

        RequestContext::json(
            [
                "data" => $users,
                "meta" => ["page" => $page, "per_page" => $perPage],
            ],
            200
        );
    }

    public function store(): void
    {
        // ? RequestContext::validate() reads the current request's body
        // ? (JSON or form-encoded) and throws Tiny\Xel\Exception\
        // ? ValidationException on failure - already rendered as a 422
        // ? with per-field errors by the global exception handler.
        // ? "unique:users,email" works with zero extra setup: EloquentDriver
        // ? wires the presence verifier automatically on boot.
        $data = RequestContext::validate([
            "name" => "required|string|max:255",
            "email" => "required|email|unique:users,email",
        ]);

        $user = User::create($data);

        // ? offloaded to a Swoole task worker (see Tiny\Xel\Task\
        // ? TaskDispatcher) - the response below doesn't wait on it.
        TaskDispatcher::dispatch(new LogUserCreatedTask($user->email));

        RequestContext::json($user, 201);
    }
}
