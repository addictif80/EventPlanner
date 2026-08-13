<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Event extends Model
{
    protected static string $table = 'events';

    public static function allWithClient(): array
    {
        $sql = 'SELECT e.*, c.first_name, c.last_name, c.company_name, c.type AS client_type, et.name AS type_name
                FROM events e
                LEFT JOIN clients c ON c.id = e.client_id
                LEFT JOIN event_types et ON et.id = e.event_type_id
                WHERE e.organization_id = ?
                ORDER BY e.event_date DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $sql = 'SELECT e.*, c.first_name, c.last_name, c.company_name, c.type AS client_type,
                       et.name AS type_name, v.name AS venue_name
                FROM events e
                LEFT JOIN clients c ON c.id = e.client_id AND c.organization_id = e.organization_id
                LEFT JOIN event_types et ON et.id = e.event_type_id AND et.organization_id = e.organization_id
                LEFT JOIN venues v ON v.id = e.venue_id AND v.organization_id = e.organization_id
                WHERE e.id = ? AND e.organization_id = ? LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$id, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    public static function upcoming(int $limit = 5): array
    {
        $sql = 'SELECT e.*, c.first_name, c.last_name, c.company_name
                FROM events e LEFT JOIN clients c ON c.id = e.client_id
                WHERE e.event_date >= CURDATE() AND e.status != "cancelled" AND e.organization_id = ?
                ORDER BY e.event_date ASC LIMIT ' . (int) $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }
}
