<?php

declare(strict_types=1);

namespace Tiny\Xel\Exception;

use Swoole\Http\Response;
use Throwable;
use Tiny\Xel\Context\ErrorContext;
use Tiny\Xel\Exception\Contract\ExceptionContract;

/**
 * The single place in the framework that turns a Throwable into an HTTP
 * response. Every error path in the system - the global catch-all around
 * request dispatch, 404/405 routing, boot-time failures replayed on every
 * request - goes through here, so every error response has the same shape
 * and the same safety guarantee: an ExceptionContract's message/context was
 * written by the developer specifically to be client-safe and is sent as
 * given; anything else (a raw PDOException, TypeError, ...) is replaced
 * with a generic message before it ever reaches the client. The real
 * message and trace still go to the server log and ErrorContext.
 */
final class ExceptionRenderer
{
    public static function respond(Response $response, Throwable $e): void
    {
        // ? keep the original exception available for the rest of the
        // ? request (a logging middleware, etc.) even though the response
        // ? is about to end.
        ErrorContext::setError($e);

        // ? the real message/trace is for operators, never for the client.
        error_log((string) $e);

        $exception = $e instanceof ExceptionContract
            ? $e
            : new XelException("Internal Server Error", previous: $e);

        // ? a route handler or middleware may have already ended the
        // ? response before throwing (or a previous error already rendered
        // ? one) - Swoole only allows a response to be ended once.
        if (!$response->isWritable()) {
            return;
        }

        $response->header("Content-Type", "application/json");
        $response->status($exception->statusCode());
        $response->end(json_encode($exception->render()));
    }
}
