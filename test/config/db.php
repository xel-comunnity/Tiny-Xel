<?php

// return [
//     'driver'   => 'pgsql',
//     "host" => "localhost",
//     "port" => 5432,
//     "db" => "blogs",
//     "username" => "postgres",
//     "password" => "admin1234",
//     "charset" => "UTF8",
//     "pool" => 10,
// ];

// mysql driver by default

// return [
//     'driver'=> 'mysql',
//     "host" => "153.92.15.29",
//     "port" => 3306,
//     "db" => "u925035917_tokyonight",
//     "username" => "u925035917_yogidev",
//     "password" => "C0#pc*=[gBx",
//     "charset" => "utf8mb4",
//     "pool" => 10,
// ];

return [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => 3306,
    "db" => "sample",
    "username" => "root",
    "password" => "Todokana1ko!",
    "charset" => "utf8mb4",
    "pool" => 20,
];

// ? sqlite
// return
// [
//     'driver'   => 'sqlite',
//     'database' => __DIR__."/../database/xel.sqlite",
//     "pool" => 10,
// ];

// ? support multi-DB (available soon)
// return [
//     "mysql"=> [
//         'driver'   => 'mysql',
//         "host" => "localhost",
//         "port" => 5432,
//         "db" => "blogs",
//         "username" => "postgres",
//         "password" => "admin1234",
//         "charset" => "UTF8",
//         "pool" => 10,
//     ],

//     "sqlite"=> [
//         'driver'   => 'sqlite',
//         'database' => __DIR__."/../database/xel.sqlite",
//         "pool" => 10,
//     ],

//     "pgsql"=> [
//     'driver'   => 'pgsql',
//     "host" => "localhost",
//     "port" => 5432,
//     "db" => "blogs",
//     "username" => "postgres",
//     "password" => "admin1234",
//     "charset" => "UTF8",
//     "pool" => 10,
//     ],
// ];
