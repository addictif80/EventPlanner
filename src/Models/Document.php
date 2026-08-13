<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Document extends Model
{
    protected static string $table = 'documents';

    public static function forClient(int $clientId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM documents WHERE client_id = ? AND organization_id = ? ORDER BY created_at DESC');
        $stmt->execute([$clientId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function forEvent(int $eventId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM documents WHERE event_id = ? AND organization_id = ? ORDER BY created_at DESC');
        $stmt->execute([$eventId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }
}
