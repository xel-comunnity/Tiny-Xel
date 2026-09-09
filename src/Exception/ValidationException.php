<?php

declare(strict_types=1);

namespace Tiny\Xel\Exception;

use Throwable;

class ValidationException extends XelException
{
    protected int $statusCode = 422;
    protected string $errorCode = "VALIDATION_ERROR";

    /**
     * @param array<string, array<int, string>|string> $errors per-field error messages,
     *        e.g. ["email" => ["The email field is required."]]
     */
    public function __construct(
        array $errors,
        string $message = "The given data was invalid.",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, context: ["errors" => $errors], previous: $previous);
    }
}
