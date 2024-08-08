<?php

namespace Tiny\Xel\Context;

use Swoole\Coroutine;

class Context
{
    protected static $contexts = [];

    public static function set(string $key, $value)
    {
       $cid = Coroutine::getCid();
       if (!isset(self::$contexts[$cid])) {
        self::$contexts[$cid] = [];
       }

       self::$contexts[$cid][$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        $cid = Coroutine::getuid();
        if (!isset(self::$contexts[$cid])) {
            return $default;
        }

        return self::$contexts[$cid][$key];
    }

    public static function clear()
    {
        $cid = Coroutine::getuid();
        if (isset(self::$contexts[$cid])) {
            unset(self::$contexts[$cid]);
        }
        
    }
}