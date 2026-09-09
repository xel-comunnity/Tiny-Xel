<?php

declare(strict_types=1);

namespace Tiny\Xel\Exception\Contract;

/**
 * Marks an exception as safe and ready to send straight to an API client.
 *
 * Anything thrown from a route handler, middleware, or the model layer that
 * implements this contract is treated as a deliberate, client-safe business
 * error: Tiny\Xel\Exception\ExceptionRenderer sends its statusCode()/
 * errorCode()/message()/context() straight back in the response.
 *
 * Anything thrown that does NOT implement this contract (a raw PDOException,
 * TypeError, or any other unexpected Throwable) is treated as an internal
 * failure instead: the renderer replaces it with a generic 500 message and
 * never exposes its real message or trace to the client - only to the
 * server log and ErrorContext. See ExceptionRenderer::respond().
 */
interface ExceptionContract
{
    /**
     * The HTTP status code this exception should be rendered with.
     */
    public function statusCode(): int;

    /**
     * A short, stable, machine-readable code (e.g. "NOT_FOUND",
     * "VALIDATION_ERROR") for API clients to switch on instead of parsing
     * the human-readable message.
     */
    public function errorCode(): string;

    /**
     * Extra structured data safe to expose to the client - e.g. per-field
     * validation errors. Return [] when there's nothing to add.
     *
     * @return array<string, mixed>
     */
    public function context(): array;

    /**
     * The full response payload ExceptionRenderer sends as the response
     * body (json_encode()'d as-is).
     *
     * @return array<string, mixed>
     */
    public function render(): array;
}
