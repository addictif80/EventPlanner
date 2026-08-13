<?php

namespace App\Models;

use App\Core\Database;

class SystemSetting
{
    public static function get(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM system_settings WHERE id = 1');
        return $stmt->fetch() ?: [];
    }

    public static function update(array $data): bool
    {
        $assignments = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $stmt = Database::connection()->prepare("UPDATE system_settings SET {$assignments} WHERE id = 1");
        return $stmt->execute($data);
    }
}
