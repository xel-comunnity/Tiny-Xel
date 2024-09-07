<?php

use Tiny\Xel\Server\Applications;

require __DIR__ . "/../vendor/autoload.php";
$provider = require __DIR__ . "/config/provider.php";

// ? fire instance
$app = new Applications();

$app->__setProvider($provider)->__init();
