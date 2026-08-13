<?php

require_once __DIR__ . '/env.php';

return [
    'app' => [
        'name' => env('APP_NAME', 'EventPlanner'),
        'url' => rtrim(env('APP_URL', 'http://localhost'), '/'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
        'key' => env('APP_KEY', ''),
    ],
    'db' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', '3306'),
        'name' => env('DB_NAME', 'eventplanner'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
    ],
];
