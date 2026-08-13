<?php

namespace App\Models;

use App\Core\Database;

class OrganizationSubscriptionItem
{
    public static function forSubscription(int $organizationSubscriptionId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT osi.*, modules.name AS module_name, module_packages.name AS package_name
             FROM organization_subscription_items osi
             LEFT JOIN modules ON modules.id = osi.module_id
             LEFT JOIN module_packages ON module_packages.id = osi.package_id
             WHERE osi.organization_subscription_id = ?
             ORDER BY osi.created_at ASC'
        );
        $stmt->execute([$organizationSubscriptionId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM organization_subscription_items WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $columns = array_keys($data);
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO organization_subscription_items (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_map(fn($c) => ':' . $c, $columns)) . ')'
        );
        $stmt->execute($data);
        return (int) $pdo->lastInsertId();
    }

    public static function updatePrice(int $id, string $stripePriceId): void
    {
        Database::connection()->prepare('UPDATE organization_subscription_items SET stripe_price_id = ? WHERE id = ?')->execute([$stripePriceId, $id]);
    }

    public static function findByStripeItemId(string $stripeSubscriptionItemId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM organization_subscription_items WHERE stripe_subscription_item_id = ? LIMIT 1');
        $stmt->execute([$stripeSubscriptionItemId]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM organization_subscription_items WHERE id = ?')->execute([$id]);
    }
}
