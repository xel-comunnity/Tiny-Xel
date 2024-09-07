<?php

namespace Tiny\Xel\Context;

use Swoole\Coroutine;

class Context
{
    protected static $pool = [];

    public static function set(string $key, $value)
    {
        $cid = Coroutine::getuid();
        if ($cid > 0) {
            self::$pool[$cid][$key] = $value;
        }
    }

    public static function get(string $key)
    {
        $cid = Coroutine::getuid();
        if ($cid < 0) {
            return null;
        }
        return self::$pool[$cid][$key] ?? null;
    }

    public static function clear()
    {
        $cid = Coroutine::getuid();
        if ($cid > 0) {
            unset(self::$pool[$cid]);
        }
    }
}
