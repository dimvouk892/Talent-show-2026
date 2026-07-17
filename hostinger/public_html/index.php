<?php

/**
 * Place this file in: domains/vprint.gr/public_html/index.php
 * Laravel app must be at: domains/vprint.gr/talent-show
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$candidates = [
    dirname(__DIR__).'/talent-show',
    __DIR__.'/../talent-show',
    __DIR__.'/talent-show',
    '/home/u758690321/domains/vprint.gr/talent-show',
];

$laravelRoot = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate.'/bootstrap/app.php') && is_file($candidate.'/vendor/autoload.php')) {
        $laravelRoot = $candidate;
        break;
    }
}

if ($laravelRoot === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Laravel not found.\nChecked:\n- ".implode("\n- ", $candidates)."\n";
    exit(1);
}

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

// public_html IS the web public directory on Hostinger.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
