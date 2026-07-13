<?php

$mysqlHost = gethostbyname('mysql') !== 'mysql' ? 'mysql' : '127.0.0.1';

$testingEnv = [
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => $mysqlHost,
    'DB_PORT' => '3306',
    'DB_DATABASE' => 'testing',
    'DB_USERNAME' => 'sail',
    'DB_PASSWORD' => 'password',
    'DB_URL' => '',
];

foreach ($testingEnv as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
