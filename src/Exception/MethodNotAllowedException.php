<?php

declare(strict_types=1);

namespace Tiny\Xel\Exception;

use Throwable;

class MethodNotAllowedException extends XelException
{
    protected int $statusCode = 405;
    protected string $errorCode = "METHOD_NOT_ALLOWED";

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = "This HTTP method is not allowed for this route.",
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, context: $context, previous: $previous);
    }
}
