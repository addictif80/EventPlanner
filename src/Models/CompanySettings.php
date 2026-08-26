<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

class CompanySettings
{
    public static function get(): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM company_settings WHERE organization_id = ?');
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetch() ?: [];
    }

    public static function update(array $data): void
    {
        // Upsert rather than a bare UPDATE: if the organization's row is
        // somehow missing, a plain UPDATE matches zero rows and silently
        // no-ops (no PDO error), so the save "succeeds" but nothing is
        // ever persisted and the form reverts to blank on the next load.
        unset($data['organization_id']);
        if (empty($data)) {
            return;
        }

        $columns = array_keys($data);
        $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $columns));
        $columnList = implode(', ', $columns);
        $updates = implode(', ', array_map(fn($c) => "{$c} = VALUES({$c})", $columns));

        $data['organization_id'] = Auth::organizationId();
        $stmt = Database::connection()->prepare(
            "INSERT INTO company_settings (organization_id, {$columnList}) VALUES (:organization_id, {$placeholders})
             ON DUPLICATE KEY UPDATE {$updates}"
        );
        $stmt->execute($data);
    }

    public static function createDefaults(int $organizationId): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO company_settings (organization_id) VALUES (?)');
        $stmt->execute([$organizationId]);
    }
}
