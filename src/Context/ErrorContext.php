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

    protected static function get($key)
    {
        $cid = Coroutine::getuid();
        if ($cid < 0) {
            return null;
        }
        return self::$pool[$cid][$key] ?? null;
    }

    protected static function put($key, $item)
    {
        $cid = Coroutine::getuid();
        if ($cid > 0) {
            self::$pool[$cid][$key] = $item;
        }
    }

    public static function clear()
    {
        $cid = Coroutine::getuid();
        if ($cid > 0) {
            unset(self::$pool[$cid]);
        }
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
