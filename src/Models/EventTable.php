<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class EventTable extends Model
{
    protected static string $table = 'event_tables';

    public static function forEvent(int $eventId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, (SELECT COUNT(*) + COALESCE(SUM(plus_ones), 0) FROM guests WHERE table_id = t.id) AS seated
             FROM event_tables t WHERE t.event_id = ? AND t.organization_id = ? ORDER BY t.name ASC'
        );
        $stmt->execute([$eventId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }
}
