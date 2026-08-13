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
            $stmt = Database::connection()->prepare('SELECT id, organization_id, name, email, role, is_super_admin FROM users WHERE id = ? LIMIT 1');
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

    /** Platform-level administrator, distinct from a per-organization "admin" role. */
    public static function isSuperAdmin(): bool
    {
        return self::check() && !empty(self::user()['is_super_admin']);
    }

    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT users.*, organizations.status AS organization_status
             FROM users JOIN organizations ON organizations.id = users.organization_id
             WHERE users.email = ? AND users.is_active = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['organization_status'] === 'suspended' && empty($user['is_super_admin'])) {
            return false;
        }

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

    public static function requireSuperAdmin(): void
    {
        self::requireLogin();
        if (!self::isSuperAdmin()) {
            http_response_code(403);
            die('Accès réservé aux administrateurs de la plateforme.');
        }
    }

    /**
     * Switches the active session to another user's identity while keeping
     * track of the impersonating super admin, so support staff can see the
     * app exactly as a tenant user does without knowing their password.
     */
    public static function impersonate(int $userId): bool
    {
        if (!self::isSuperAdmin()) {
            return false;
        }

        $stmt = Database::connection()->prepare('SELECT id, organization_id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $target = $stmt->fetch();
        if (!$target) {
            return false;
        }

        $impersonatorId = $_SESSION['impersonator_id'] ?? self::id();

        $_SESSION['user_id'] = (int) $target['id'];
        $_SESSION['organization_id'] = (int) $target['organization_id'];
        $_SESSION['impersonator_id'] = $impersonatorId;
        unset($_SESSION['user_cache']);

        return true;
    }

    public static function isImpersonating(): bool
    {
        return !empty($_SESSION['impersonator_id']);
    }

    public static function impersonatorId(): ?int
    {
        return $_SESSION['impersonator_id'] ?? null;
    }

    /** Returns the impersonated session to the original super admin's own identity. */
    public static function stopImpersonating(): bool
    {
        $impersonatorId = self::impersonatorId();
        if (!$impersonatorId) {
            return false;
        }

        $stmt = Database::connection()->prepare('SELECT id, organization_id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$impersonatorId]);
        $admin = $stmt->fetch();
        if (!$admin) {
            return false;
        }

        $_SESSION['user_id'] = (int) $admin['id'];
        $_SESSION['organization_id'] = (int) $admin['organization_id'];
        unset($_SESSION['impersonator_id'], $_SESSION['user_cache']);

        return true;
    }
}
