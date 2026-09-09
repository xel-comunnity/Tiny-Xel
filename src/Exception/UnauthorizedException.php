<?php

declare(strict_types=1);

namespace Tiny\Xel\Exception;

use Throwable;

class UnauthorizedException extends XelException
{
    protected int $statusCode = 401;
    protected string $errorCode = "UNAUTHORIZED";

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = "Authentication is required to access this resource.",
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, context: $context, previous: $previous);
    }
}
