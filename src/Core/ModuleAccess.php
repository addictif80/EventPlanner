<?php

namespace App\Core;

/**
 * Computes which paid modules an organization currently has unlocked, from
 * its subscribed plan plus any individually purchased modules/packages —
 * and enforces plan limits (member count). Core CRM features (clients,
 * events, quotes, invoices, settings, users) are never gated: only the
 * modules seeded in the `modules` table are subject to subscription.
 */
class ModuleAccess
{
    private static array $cache = [];

    public static function activeModuleKeys(?int $organizationId = null): array
    {
        $organizationId = $organizationId ?? Auth::organizationId();
        if ($organizationId === null) {
            return [];
        }
        if (isset(self::$cache[$organizationId])) {
            return self::$cache[$organizationId];
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT status FROM organization_subscriptions WHERE organization_id = ? LIMIT 1');
        $stmt->execute([$organizationId]);
        $status = $stmt->fetchColumn();

        if (!in_array($status, ['active', 'trialing'], true)) {
            return self::$cache[$organizationId] = [];
        }

        $stmt = $pdo->prepare(
            "SELECT m.module_key
             FROM organization_subscriptions os
             JOIN plan_modules pm ON pm.plan_id = os.plan_id
             JOIN modules m ON m.id = pm.module_id
             WHERE os.organization_id = ?
             UNION
             SELECT m.module_key
             FROM organization_subscriptions os
             JOIN organization_subscription_items osi ON osi.organization_subscription_id = os.id AND osi.item_type = 'module'
             JOIN modules m ON m.id = osi.module_id
             WHERE os.organization_id = ?
             UNION
             SELECT m.module_key
             FROM organization_subscriptions os
             JOIN organization_subscription_items osi ON osi.organization_subscription_id = os.id AND osi.item_type = 'package'
             JOIN module_package_items mpi ON mpi.package_id = osi.package_id
             JOIN modules m ON m.id = mpi.module_id
             WHERE os.organization_id = ?"
        );
        $stmt->execute([$organizationId, $organizationId, $organizationId]);

        return self::$cache[$organizationId] = array_column($stmt->fetchAll(), 'module_key');
    }

    public static function has(string $moduleKey, ?int $organizationId = null): bool
    {
        return in_array($moduleKey, self::activeModuleKeys($organizationId), true);
    }

    public static function requireModule(string $moduleKey): void
    {
        Auth::requireLogin();
        if (!self::has($moduleKey)) {
            http_response_code(403);
            die("Ce module n'est pas inclus dans votre abonnement actuel. Rendez-vous dans Paramètres > Abonnement pour l'activer.");
        }
    }

    /** True once the organization has reached its plan's max_members (unlimited if null or no subscription row). */
    public static function memberLimitReached(?int $organizationId = null): bool
    {
        $organizationId = $organizationId ?? Auth::organizationId();
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT p.max_members FROM organization_subscriptions os JOIN plans p ON p.id = os.plan_id WHERE os.organization_id = ? LIMIT 1'
        );
        $stmt->execute([$organizationId]);
        $maxMembers = $stmt->fetchColumn();

        if ($maxMembers === false || $maxMembers === null) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE organization_id = ?');
        $stmt->execute([$organizationId]);

        return (int) $stmt->fetchColumn() >= (int) $maxMembers;
    }

    public static function currentSubscription(?int $organizationId = null): ?array
    {
        $organizationId = $organizationId ?? Auth::organizationId();

        $stmt = Database::connection()->prepare(
            'SELECT os.*, p.name AS plan_name, p.monthly_price AS plan_price, p.max_members
             FROM organization_subscriptions os
             LEFT JOIN plans p ON p.id = os.plan_id
             WHERE os.organization_id = ? LIMIT 1'
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetch() ?: null;
    }
}
