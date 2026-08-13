<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Plan extends Model
{
    protected static string $table = 'plans';
    protected static bool $scoped = false;

    public static function allOrdered(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM plans ORDER BY sort_order ASC, monthly_price ASC');
        return $stmt->fetchAll();
    }

    public static function activeOrdered(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, monthly_price ASC');
        return $stmt->fetchAll();
    }

    public static function defaultSignupPlanId(): ?int
    {
        $stmt = Database::connection()->query('SELECT id FROM plans WHERE is_default_signup = 1 ORDER BY id LIMIT 1');
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    public static function withModules(int $id): ?array
    {
        $plan = self::find($id);
        if (!$plan) {
            return null;
        }
        $plan['module_ids'] = PlanModule::forPlan($id);
        return $plan;
    }
}
