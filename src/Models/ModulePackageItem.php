<?php

namespace App\Models;

use App\Core\Database;

class ModulePackageItem
{
    public static function forPackage(int $packageId): array
    {
        $stmt = Database::connection()->prepare('SELECT module_id FROM module_package_items WHERE package_id = ?');
        $stmt->execute([$packageId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'module_id'));
    }

    public static function set(int $packageId, array $moduleIds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM module_package_items WHERE package_id = ?')->execute([$packageId]);

        $stmt = $pdo->prepare('INSERT INTO module_package_items (package_id, module_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $moduleIds)) as $moduleId) {
            $stmt->execute([$packageId, $moduleId]);
        }
    }

    public static function modulesForPackage(int $packageId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT modules.* FROM modules JOIN module_package_items ON module_package_items.module_id = modules.id
             WHERE module_package_items.package_id = ? ORDER BY modules.name ASC'
        );
        $stmt->execute([$packageId]);
        return $stmt->fetchAll();
    }
}
