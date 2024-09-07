<?php

$data = [
    "penaldo" => [
        "auth" => "a",
        "middleware" => "b",
    ],

    "pessi" => [
        "auth" => "b",
        "middleware" => "c",
    ],
];

function xf($x)
{
    foreach ($x as $key => $value) {
        echo $key . $value;
    }
}

foreach ($data as $key => $value) {
    if ($key == "pessi") {
        xf($value);
    }
}
