<?php

namespace Tiny\Xel\Context;

use Swoole\Database\PDOPool;
use Swoole\Coroutine;

class DBContext
{
    protected static $pool;
    protected static $connectionMap = [];

    public static function setPool(PDOPool $pool)
    {
        self::$pool = $pool;
    }

    public static function getConnection()
    {
        $cid = Coroutine::getuid();
        if ($cid < 0) {
            // Not in a coroutine, return a connection directly from the pool
            return self::$pool->get();
        }

        if (!isset(self::$connectionMap[$cid])) {
            self::$connectionMap[$cid] = self::$pool->get();
        }

        return self::$connectionMap[$cid];
    }

    public static function releaseConnection()
    {
        $cid = Coroutine::getuid();
        if ($cid > 0 && isset(self::$connectionMap[$cid])) {
            self::$pool->put(self::$connectionMap[$cid]);
            unset(self::$connectionMap[$cid]);
        }
    }
}