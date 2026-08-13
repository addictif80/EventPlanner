<?php

use App\Core\Auth;

require dirname(__DIR__) . '/src/bootstrap.php';

$router = require dirname(__DIR__) . '/src/routes.php';

$path = $router->normalizedPath($_SERVER['REQUEST_URI'] ?? '/');
$publicPaths = ['/login', '/logout'];

if (!in_array($path, $publicPaths, true)) {
    Auth::requireLogin();
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
