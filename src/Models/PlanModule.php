<?php

namespace App\Models;

use App\Core\Database;

class PlanModule
{
    public static function forPlan(int $planId): array
    {
        $stmt = Database::connection()->prepare('SELECT module_id FROM plan_modules WHERE plan_id = ?');
        $stmt->execute([$planId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'module_id'));
    }

    public static function set(int $planId, array $moduleIds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM plan_modules WHERE plan_id = ?')->execute([$planId]);

        $stmt = $pdo->prepare('INSERT INTO plan_modules (plan_id, module_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $moduleIds)) as $moduleId) {
            $stmt->execute([$planId, $moduleId]);
        }
    }
}
