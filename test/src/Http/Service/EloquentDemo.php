<?php

namespace Tiny\Test\Http\Service;

use Tiny\Test\Model\User;
use Tiny\Xel\Context\RequestContext;

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
        RequestContext::json(User::all(), 200);
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
        RequestContext::json($user, 201);
    }
}
