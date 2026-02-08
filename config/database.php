<?php

return [
    'driver' => env('DB_DRIVER', 'mysql'),
    'mysql' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_NAME', 'trading_login'),
        'username' => env('DB_USER', 'root'),
        'password' => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'sqlite' => [
        'database' => env('DB_DATABASE', __DIR__ . '/../storage/database.sqlite'),
    ],
];


