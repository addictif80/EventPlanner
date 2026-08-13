<?php

use App\Core\Auth;

require dirname(__DIR__) . '/src/bootstrap.php';

$router = require dirname(__DIR__) . '/src/routes.php';

$path = $router->normalizedPath($_SERVER['REQUEST_URI'] ?? '/');
$publicPaths = ['/login', '/logout', '/register'];
$publicPrefixes = ['/rsvp/', '/sign/', '/survey/', '/portal/', '/calendar/', '/stripe/return/'];

$isPublic = in_array($path, $publicPaths, true);
foreach ($publicPrefixes as $prefix) {
    if (str_starts_with($path, $prefix)) {
        $isPublic = true;
        break;
    }
}

if (!$isPublic) {
    Auth::requireLogin();
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
