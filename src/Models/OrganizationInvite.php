<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Platform-level, not tenant-scoped: an invite exists before any organization does. */
class OrganizationInvite extends Model
{
    protected static string $table = 'organization_invites';
    protected static bool $scoped = false;

    public static function allWithRelations(): array
    {
        $stmt = Database::connection()->query(
            'SELECT oi.*, p.name AS plan_name, u.name AS invited_by_name, o.name AS organization_name
             FROM organization_invites oi
             LEFT JOIN plans p ON p.id = oi.plan_id
             LEFT JOIN users u ON u.id = oi.invited_by
             LEFT JOIN organizations o ON o.id = oi.accepted_organization_id
             ORDER BY oi.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM organization_invites WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function isPending(array $invite): bool
    {
        return $invite['status'] === 'pending' && strtotime($invite['expires_at']) > time();
    }
}
