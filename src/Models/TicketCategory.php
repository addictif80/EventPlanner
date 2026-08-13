<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class TicketCategory extends Model
{
    protected static string $table = 'ticket_categories';

    public static function forEvent(int $eventId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT tc.*, (SELECT COUNT(*) FROM tickets WHERE ticket_category_id = tc.id AND status != "cancelled") AS sold_count
             FROM ticket_categories tc WHERE tc.event_id = ? AND tc.organization_id = ? ORDER BY tc.name ASC'
        );
        $stmt->execute([$eventId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }
}
