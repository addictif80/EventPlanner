<?php

use App\Core\Auth;
use App\Models\BlockedIp;

require dirname(__DIR__) . '/src/bootstrap.php';

$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
if ($remoteIp !== '' && BlockedIp::isBlocked($remoteIp)) {
    http_response_code(403);
    die('Accès refusé.');
}

$router = require dirname(__DIR__) . '/src/routes.php';

$path = $router->normalizedPath($_SERVER['REQUEST_URI'] ?? '/');
$publicPaths = ['/', '/login', '/logout', '/register', '/push/vapid-public-key.json', '/forgot-password'];
$publicPrefixes = ['/rsvp/', '/sign/', '/survey/', '/portal/', '/calendar/', '/stripe/return/', '/page/', '/org-logo/', '/reset-password/', '/invite/'];
$publicPaths[] = '/subscription/webhook';

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
