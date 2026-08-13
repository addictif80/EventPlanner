<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Ticket extends Model
{
    protected static string $table = 'tickets';

    public static function forEvent(int $eventId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, tc.name AS category_name, tc.event_id
             FROM tickets t JOIN ticket_categories tc ON tc.id = t.ticket_category_id
             WHERE tc.event_id = ? AND t.organization_id = ? ORDER BY t.created_at DESC'
        );
        $stmt->execute([$eventId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    /** Scoped to the current organization: used for authenticated check-in. */
    public static function findByCode(string $code): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, tc.name AS category_name, tc.event_id
             FROM tickets t JOIN ticket_categories tc ON tc.id = t.ticket_category_id
             WHERE t.code = ? AND t.organization_id = ? LIMIT 1'
        );
        $stmt->execute([$code, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    public static function generateCode(): string
    {
        return strtoupper(bin2hex(random_bytes(5)));
    }
}
