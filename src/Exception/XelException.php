<?php

declare(strict_types=1);

namespace Tiny\Xel\Exception;

use RuntimeException;
use Throwable;
use Tiny\Xel\Exception\Contract\ExceptionContract;

/**
 * The master exception every business-process exception should extend.
 *
 * It exists so app/business code never has to build its own status
 * code/error code/JSON-shape plumbing: extend this (or one of the ready-made
 * exceptions in this namespace - NotFoundException, ValidationException,
 * UnauthorizedException, ForbiddenException), throw it from anywhere in a
 * request (a route handler, a middleware, a model layer call), and
 * Tiny\Xel\Exception\ExceptionRenderer - already wired into the framework's
 * request lifecycle - renders it consistently. No per-route try/catch
 * needed.
 *
 * Usage:
 *   throw new XelException("Something specific went wrong", 400, "BAD_INPUT");
 *
 * Or, for a reusable business error, extend it once:
 *   class OutOfStockException extends XelException
 *   {
 *       protected int $statusCode = 409;
 *       protected string $errorCode = "OUT_OF_STOCK";
 *
 *       public function __construct(string $sku)
 *       {
 *           parent::__construct("Product {$sku} is out of stock.", context: ["sku" => $sku]);
 *       }
 *   }
 */
class XelException extends RuntimeException implements ExceptionContract
{
    protected int $statusCode = 500;
    protected string $errorCode = "INTERNAL_ERROR";

    /** @var array<string, mixed> */
    protected array $context = [];

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = "Something went wrong.",
        ?int $statusCode = null,
        ?string $errorCode = null,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        // ? null means "keep whatever this class (or a subclass) already
        // ? set as its property default" - lets subclasses fix their own
        // ? statusCode/errorCode once via property defaults instead of
        // ? having to pass them through every constructor call.
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }
        if ($errorCode !== null) {
            $this->errorCode = $errorCode;
        }

        $this->context = $context;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function render(): array
    {
        return [
            "status" => "error",
            "code" => $this->errorCode(),
            "message" => $this->getMessage(),
            "context" => $this->context(),
        ];
    }
}
