<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class SitePage extends Model
{
    protected static string $table = 'site_pages';
    protected static bool $scoped = false;

    public static function allOrdered(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM site_pages ORDER BY title ASC');
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM site_pages WHERE slug = ? AND is_published = 1 LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM site_pages WHERE slug = ?';
        $params = [$slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = Database::connection()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
}
