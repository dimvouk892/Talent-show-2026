<?php

/**
 * Hostinger bridge: place these files in the domain document root (usually public_html).
 * Laravel project must live next to public_html as: ../talent-show
 *
 * domains/vprint.gr/
 *   public_html/     ← document root (this file)
 *   talent-show/     ← full Laravel project
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelRoot = dirname(__DIR__).'/talent-show';

if (! is_dir($laravelRoot)) {
    http_response_code(500);
    echo 'Laravel project not found at: '.$laravelRoot;
    exit(1);
}

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

// Ensure Vite/assets resolve to talent-show/public (not public/public).
$app->usePublicPath($laravelRoot.'/public');

$app->handleRequest(Request::capture());
