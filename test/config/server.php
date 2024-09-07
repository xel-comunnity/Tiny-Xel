<?php

return [
    "api" => [
        "host" => "http://localhost",
        "port" => 9501,
        "mode" => SWOOLE_PROCESS,
        "sock" => SWOOLE_SOCK_TCP, // add | SWOOLE_SSL to make it ssl

        "options" => [
            "worker_num" => 1,
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
