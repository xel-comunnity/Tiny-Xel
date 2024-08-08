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
//     'driver'   => 'mysql',
//     "host" => "localhost",
//     "port" => 3306,
//     "db" => "blogs",
//     "username" => "root",
//     "password" => "Todokana1ko!",
//     "charset" => "utf8mb4",
//     "pool" => 10,

// ];


// ? sqlite
return 
[
    'driver'   => 'sqlite',
    'database' => __DIR__."/../database/xel.sqlite",
    "pool" => 10,
];


// ? support multi-DB (available soon)