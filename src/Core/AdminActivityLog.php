<?php

namespace App\Core;

class AdminActivityLog
{
    public static function record(string $action, string $targetType = '', ?int $targetId = null, string $details = ''): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO admin_activity_log (admin_user_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([Auth::id(), $action, $targetType, $targetId, $details]);
    }
}
