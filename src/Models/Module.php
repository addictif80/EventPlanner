<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Module extends Model
{
    protected static string $table = 'modules';
    protected static bool $scoped = false;

    public static function allOrdered(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM modules ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function activeOrdered(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM modules WHERE is_active = 1 ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function findByKey(string $key): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM modules WHERE module_key = ? LIMIT 1');
        $stmt->execute([$key]);
        return $stmt->fetch() ?: null;
    }
}
