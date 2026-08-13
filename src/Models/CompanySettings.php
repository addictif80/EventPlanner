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
        $assignments = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $data['__org_id'] = Auth::organizationId();
        $stmt = Database::connection()->prepare("UPDATE company_settings SET {$assignments} WHERE organization_id = :__org_id");
        $stmt->execute($data);
    }

    public static function createDefaults(int $organizationId): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO company_settings (organization_id) VALUES (?)');
        $stmt->execute([$organizationId]);
    }
}
