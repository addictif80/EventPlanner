<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class SupportTicket extends Model
{
    protected static string $table = 'support_tickets';

    public static function allWithOrganization(): array
    {
        $stmt = Database::connection()->query(
            'SELECT support_tickets.*, organizations.name AS organization_name
             FROM support_tickets
             JOIN organizations ON organizations.id = support_tickets.organization_id
             ORDER BY support_tickets.updated_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function findWithOrganizationForAdmin(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT support_tickets.*, organizations.name AS organization_name
             FROM support_tickets
             JOIN organizations ON organizations.id = support_tickets.organization_id
             WHERE support_tickets.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
