<?php

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function organizationId(): ?int
    {
        return $_SESSION['organization_id'] ?? null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        if (!isset($_SESSION['user_cache'])) {
            $stmt = Database::connection()->prepare('SELECT id, organization_id, name, email, role FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([self::id()]);
            $_SESSION['user_cache'] = $stmt->fetch() ?: null;
        }
        return $_SESSION['user_cache'];
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            self::login((int) $user['id'], (int) $user['organization_id']);
            return true;
        }

        return false;
    }

    /** Establishes the session for a given user, e.g. after login or self-service registration. */
    public static function login(int $userId, int $organizationId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['organization_id'] = $organizationId;
        unset($_SESSION['user_cache']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            die('Accès réservé aux administrateurs.');
        }
    }
}
