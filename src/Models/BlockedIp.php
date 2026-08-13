<?php

namespace App\Models;

use App\Core\Model;

class BlockedIp extends Model
{
    protected static string $table = 'blocked_ips';
    protected static bool $scoped = false;

    public static function isBlocked(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }
        $stmt = \App\Core\Database::connection()->prepare('SELECT id FROM blocked_ips WHERE ip_address = ? LIMIT 1');
        $stmt->execute([$ip]);
        return (bool) $stmt->fetch();
    }
}
