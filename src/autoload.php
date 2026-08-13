<?php

/**
 * Minimal PSR-4-style autoloader for the App\ namespace.
 * No Composer required, which keeps deployment on shared hosting (CyberPanel) simple.
 */
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
