<?php

declare(strict_types=1);

namespace Tiny\Xel\Exception;

use Throwable;

class NotFoundException extends XelException
{
    protected int $statusCode = 404;
    protected string $errorCode = "NOT_FOUND";

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = "The requested resource could not be found.",
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, context: $context, previous: $previous);
    }
}
