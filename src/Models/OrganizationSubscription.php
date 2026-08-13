<?php

namespace App\Models;

use App\Core\Database;

class OrganizationSubscription
{
    public static function forOrganization(int $organizationId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT os.*, p.name AS plan_name, p.monthly_price AS plan_price, p.max_members
             FROM organization_subscriptions os
             LEFT JOIN plans p ON p.id = os.plan_id
             WHERE os.organization_id = ? LIMIT 1'
        );
        $stmt->execute([$organizationId]);
        return $stmt->fetch() ?: null;
    }

    public static function findByStripeSubscriptionId(string $stripeSubscriptionId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM organization_subscriptions WHERE stripe_subscription_id = ? LIMIT 1');
        $stmt->execute([$stripeSubscriptionId]);
        return $stmt->fetch() ?: null;
    }

    /** Creates the row on first checkout, or updates it if a (failed/incomplete) one already exists for this org. */
    public static function upsertForOrganization(int $organizationId, array $data): int
    {
        $pdo = Database::connection();
        $existing = self::forOrganization($organizationId);

        if ($existing) {
            $assignments = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
            $data['__id'] = $existing['id'];
            $pdo->prepare("UPDATE organization_subscriptions SET {$assignments} WHERE id = :__id")->execute($data);
            return (int) $existing['id'];
        }

        $data['organization_id'] = $organizationId;
        $columns = array_keys($data);
        $stmt = $pdo->prepare(
            'INSERT INTO organization_subscriptions (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_map(fn($c) => ':' . $c, $columns)) . ')'
        );
        $stmt->execute($data);
        return (int) $pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status, ?string $currentPeriodEnd = null): void
    {
        $pdo = Database::connection();
        if ($currentPeriodEnd !== null) {
            $pdo->prepare('UPDATE organization_subscriptions SET status = ?, current_period_end = ? WHERE id = ?')
                ->execute([$status, $currentPeriodEnd, $id]);
        } else {
            $pdo->prepare('UPDATE organization_subscriptions SET status = ? WHERE id = ?')->execute([$status, $id]);
        }
    }
}
