<?php

namespace Tiny\Xel\Context;

use Swoole\Coroutine;
use Throwable;

class ErrorContext
{
    protected static $pool = [];

    public static function setError(Throwable $error)
    {
        self::put('error', $error);
    }

    public static function getError(): ?Throwable
    {
        return self::get('error');
    }

    public static function hasError(): bool
    {
        return self::get('error') !== null;
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

    public static function getErrorDetails(): ?array
    {
        $error = self::getError();
        if ($error) {
            return [
                "error-message" => $error->getMessage(),
                "error-code" => $error->getCode(),
                "error-file" => $error->getFile(),
                "error-line" => $error->getLine(),
                "error-trace" => $error->getTraceAsString()
            ];
        }
        return null;
    }
}
