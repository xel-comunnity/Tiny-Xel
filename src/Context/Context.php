<?php

namespace Tiny\Xel\Context;

use Swoole\Coroutine;

class Context
{
    protected static $pool = [];

    /**
     * The coroutine id when running under Swoole's coroutine scheduler, or a
     * fixed slot (0) when the server runs with enable_coroutine disabled -
     * e.g. Tiny\Xel\Database\Driver\EloquentDriver forces blocking mode
     * because Eloquent's static connection resolver isn't coroutine-safe.
     * In blocking mode a worker only ever processes one request at a time,
     * so the shared slot stays correctly isolated between requests as long
     * as clear() runs before the next request starts (see __flush_context).
     */
    protected static function scopeId(): int
    {
        $cid = Coroutine::getuid();
        return $cid > 0 ? $cid : 0;
    }

    public static function set(string $key, $value)
    {
        self::$pool[self::scopeId()][$key] = $value;
    }

    public static function get(string $key)
    {
        return self::$pool[self::scopeId()][$key] ?? null;
    }

    public static function clear()
    {
        unset(self::$pool[self::scopeId()]);
    }
}
