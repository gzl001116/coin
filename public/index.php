<?php

use Illuminate\Contracts\Http\Kernel;

define('LARAVEL_START', microtime(true));
require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());
