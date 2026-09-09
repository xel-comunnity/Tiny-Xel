<?php

declare(strict_types=1);

namespace Tiny\Xel\Exception;

use Throwable;

class ForbiddenException extends XelException
{
    protected int $statusCode = 403;
    protected string $errorCode = "FORBIDDEN";

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = "You do not have permission to access this resource.",
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, context: $context, previous: $previous);
    }
}
