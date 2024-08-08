<?php

return [
    "api" => [
        "host" => "http://localhost",
        "port" => 9501,
        "mode" => SWOOLE_PROCESS,
        "sock" => SWOOLE_SOCK_TCP, // add | SWOOLE_SSL to make it ssl

        "options" => [
            "worker_num" => swoole_cpu_num(),
            "document_root" => __DIR__,
            "enable_static_handler" => true,
            "static_handler_locations" => ['/static/images', '/static/files'],
            'http_parse_post' => true,
        ]
    ],
];
