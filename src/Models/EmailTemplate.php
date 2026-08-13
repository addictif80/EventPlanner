<?php

namespace App\Models;

use App\Core\Database;

class EmailTemplate
{
    public static function get(string $key): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM email_templates WHERE template_key = ? LIMIT 1');
        $stmt->execute([$key]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM email_templates ORDER BY template_key')->fetchAll();
    }

    public static function update(string $key, string $subject, string $intro): void
    {
        $stmt = Database::connection()->prepare('UPDATE email_templates SET subject = ?, intro = ? WHERE template_key = ?');
        $stmt->execute([$subject, $intro, $key]);
    }
}
