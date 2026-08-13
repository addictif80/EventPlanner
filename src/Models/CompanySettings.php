<?php

namespace App\Models;

use App\Core\Database;

class CompanySettings
{
    public static function get(): array
    {
        $row = Database::connection()->query('SELECT * FROM company_settings WHERE id = 1')->fetch();
        return $row ?: [];
    }

    public static function update(array $data): void
    {
        $assignments = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $stmt = Database::connection()->prepare("UPDATE company_settings SET {$assignments} WHERE id = 1");
        $stmt->execute($data);
    }
}
