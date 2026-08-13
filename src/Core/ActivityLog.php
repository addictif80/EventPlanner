<?php

namespace App\Core;

class ActivityLog
{
    public static function record(string $action, string $entityType = '', ?int $entityId = null, string $details = ''): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO activity_log (organization_id, user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([Auth::organizationId(), Auth::id(), $action, $entityType, $entityId, $details]);
    }
}
