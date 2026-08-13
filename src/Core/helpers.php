<?php

use App\Core\Csrf;
use App\Core\Session;

function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($scriptName));
        $base = $dir === '/' ? '' : $dir;
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path() . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function flashes(): array
{
    return Session::getFlashes();
}

function csrf_field(): string
{
    return Csrf::field();
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = null)
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function require_post_field(array $errors, string $key, string $label): array
{
    if (empty($_POST[$key])) {
        $errors[] = "Le champ « {$label} » est obligatoire.";
    }
    return $errors;
}
