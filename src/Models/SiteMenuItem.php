<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class SiteMenuItem extends Model
{
    protected static string $table = 'site_menu_items';
    protected static bool $scoped = false;

    public static function allOrdered(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM site_menu_items ORDER BY location ASC, sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    public static function activeForLocation(string $location): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM site_menu_items WHERE location = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$location]);
        return $stmt->fetchAll();
    }
}
