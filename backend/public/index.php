<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = __DIR__.'/../';

if (! is_dir($appPath.'vendor')) {
    $appPath = __DIR__.'/../laravel-app/';
}

require $appPath.'vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'bootstrap/app.php';

$app->handleRequest(Request::capture());