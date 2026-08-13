<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Guest extends Model
{
    protected static string $table = 'guests';

    public static function forEvent(int $eventId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT g.*, t.name AS table_name FROM guests g LEFT JOIN event_tables t ON t.id = g.table_id
             WHERE g.event_id = ? ORDER BY g.last_name ASC, g.first_name ASC'
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT g.*, e.title AS event_title, e.event_date FROM guests g JOIN events e ON e.id = g.event_id WHERE g.rsvp_token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function displayName(array $guest): string
    {
        return trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? ''));
    }
}
