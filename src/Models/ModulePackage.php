<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ModulePackage extends Model
{
    protected static string $table = 'module_packages';
    protected static bool $scoped = false;

    public static function allOrdered(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM module_packages ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function activeOrdered(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM module_packages WHERE is_active = 1 ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function withModules(int $id): ?array
    {
        $package = self::find($id);
        if (!$package) {
            return null;
        }
        $package['module_ids'] = ModulePackageItem::forPackage($id);
        return $package;
    }
}
