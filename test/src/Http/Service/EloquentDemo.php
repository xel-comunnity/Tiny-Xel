<?php

namespace Tiny\Test\Http\Service;

use Tiny\Test\Model\User;
use Tiny\Xel\Context\RequestContext;
use Exception;

/**
 * Demonstrates the "eloquent" db driver contract: plain Eloquent Model
 * usage, no PDOPool/DBContext plumbing needed. Requires:
 *   - test/config/provider.php: "db.contract" => "eloquent"
 *   - migrations applied first via `php test/migrate.php`
 */
final class EloquentDemo
{
    public function index(): void
    {
        try {
            RequestContext::json(User::all(), 200);
        } catch (Exception $e) {
            RequestContext::json(["error" => $e->getMessage()], 500);
        }
    }

    public function store(): void
    {
        try {
            $user = User::create([
                "name" => "Yogi",
                "email" => uniqid("yogi", true) . "@example.com",
            ]);
            RequestContext::json($user, 201);
        } catch (Exception $e) {
            RequestContext::json(["error" => $e->getMessage()], 500);
        }
    }
}
