<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Equipment extends Model
{
    protected static string $table = 'equipment';

    public static function bookingsForEvent(int $eventId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT eb.*, e.name, e.total_quantity FROM equipment_bookings eb JOIN equipment e ON e.id = eb.equipment_id
             WHERE eb.event_id = ? AND eb.organization_id = ?'
        );
        $stmt->execute([$eventId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    /** Quantity already booked for other events overlapping the given date. */
    public static function bookedQuantity(int $equipmentId, string $eventDate, ?int $excludeEventId = null): int
    {
        $sql = 'SELECT COALESCE(SUM(eb.quantity), 0) FROM equipment_bookings eb
                JOIN events e ON e.id = eb.event_id
                WHERE eb.equipment_id = ? AND e.event_date = ? AND eb.organization_id = ?';
        $params = [$equipmentId, $eventDate, Auth::organizationId()];
        if ($excludeEventId) {
            $sql .= ' AND e.id != ?';
            $params[] = $excludeEventId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
