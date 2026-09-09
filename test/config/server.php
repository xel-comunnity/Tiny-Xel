<?php

use Tiny\Xel\Config\Env;

return [
    "api" => [
        "host" => Env::get("APP_HOST", "http://localhost"),
        "port" => Env::get("APP_PORT", 9501),
        "mode" => SWOOLE_PROCESS,
        "sock" => SWOOLE_SOCK_TCP, // add | SWOOLE_SSL to make it ssl

        "options" => [
            // ? with the default "eloquent" db contract (enable_coroutine=false,
            // ? one request at a time per worker), this needs to scale with
            // ? expected concurrent requests the same way PHP-FPM's worker count
            // ? does - coroutines aren't there to make up for a low number here.
            "worker_num" => Env::get("APP_WORKER_NUM", 1),
            "log_level" => SWOOLE_LOG_ERROR,
            "task_worker_num" => 2,
            "document_root" => __DIR__,
            "enable_static_handler" => true,
            "static_handler_locations" => ["/static/images", "/static/files"],
            "http_parse_post" => true,
            "http_parse_cookie" => false,

            // ? add new setup for experiment
            "max_wait_time" => 10,
            "reload_async" => true,
            "task_enable_coroutine" => true,
        ],
    ],
    "periodic-reload" => [false, 5000],
];
