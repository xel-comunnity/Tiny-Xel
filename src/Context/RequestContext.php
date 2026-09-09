<?php

namespace Tiny\Xel\Context;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Coroutine;
use Tiny\Xel\Validation\ValidatorFactory;

class RequestContext
{
    protected static $pool = [];

    public static function setRequest(Request $request)
    {
        self::put('request', $request);
    }

    public static function getRequest(): ?Request
    {
        return self::get('request');
    }

    public static function setResponse(Response $response)
    {
        self::put('response', $response);
    }

    public static function getResponse(): ?Response
    {
        return self::get('response');
    }

    /**
     * The coroutine id, or a fixed slot (0) when the server runs with
     * enable_coroutine disabled (see Context::scopeId for why that's still
     * safely isolated between requests).
     */
    protected static function scopeId(): int
    {
        $cid = Coroutine::getuid();
        return $cid > 0 ? $cid : 0;
    }

    protected static function get($key)
    {
        return self::$pool[self::scopeId()][$key] ?? null;
    }

    protected static function put($key, $item)
    {
        self::$pool[self::scopeId()][$key] = $item;
    }

    public static function clear()
    {
        unset(self::$pool[self::scopeId()]);
    }

    public static function json($data, $statusCode = 200)
    {
        $response = self::getResponse();
        if ($response) {
            $response->header('Content-Type', 'application/json');
            $response->status($statusCode);
            $response->end(json_encode($data));
        }
    }

    public static function text($content, $statusCode = 200)
    {
        $response = self::getResponse();
        if ($response) {
            $response->header('Content-Type', 'text/plain');
            $response->status($statusCode);
            $response->end($content);
        }
    }

    public static function download($filePath, $fileName = null)
    {
        $response = self::getResponse();
        if ($response && file_exists($filePath)) {
            $fileName = $fileName ?: basename($filePath);
            $response->header('Content-Type', 'application/octet-stream');
            $response->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            $response->sendfile($filePath);
        }
    }

    public static function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = true)
    {
        $response = self::getResponse();
        if ($response) {
            $response->cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        }
    }


    public static function getQueryParams(): array
    {
        $request = self::getRequest();
        return $request ? $request->get ?? [] : [];
    }

    /**
     * The current request's body: JSON-decoded when Content-Type is
     * application/json, otherwise Swoole's own parsed form-encoded/
     * multipart $request->post.
     *
     * @return array<string, mixed>
     */
    public static function getInput(): array
    {
        $request = self::getRequest();
        if (!$request) {
            return [];
        }

        $contentType = $request->header['content-type'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($request->rawContent() ?: '{}', true);
            return is_array($decoded) ? $decoded : [];
        }

        return $request->post ?? [];
    }

    /**
     * Validates the current request's input (see getInput()) against
     * $rules and returns only the validated fields. Throws
     * Tiny\Xel\Exception\ValidationException on failure, which the
     * framework's global exception handler already renders as a 422 with
     * per-field errors - no try/catch needed in a route handler.
     *
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $customAttributes
     * @return array<string, mixed>
     */
    public static function validate(
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): array {
        return ValidatorFactory::validate(self::getInput(), $rules, $messages, $customAttributes);
    }
}
