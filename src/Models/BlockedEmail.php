<?php

namespace App\Models;

use App\Core\Model;

class BlockedEmail extends Model
{
    protected static string $table = 'blocked_emails';
    protected static bool $scoped = false;

    public static function isBlocked(string $email): bool
    {
        if ($email === '') {
            return false;
        }
        $stmt = \App\Core\Database::connection()->prepare('SELECT id FROM blocked_emails WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower($email)]);
        return (bool) $stmt->fetch();
    }
}
