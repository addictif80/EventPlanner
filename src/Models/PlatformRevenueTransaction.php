<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Platform-level (not tenant-scoped): the operator's own Stripe revenue ledger. */
class PlatformRevenueTransaction extends Model
{
    protected static string $table = 'platform_revenue_transactions';
    protected static bool $scoped = false;

    /** @param string $month "YYYY-MM" */
    public static function forMonth(string $month): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM platform_revenue_transactions WHERE DATE_FORMAT(paid_at, '%Y-%m') = ? ORDER BY paid_at ASC"
        );
        $stmt->execute([$month]);
        return $stmt->fetchAll();
    }

    public static function totalForMonth(string $month): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM platform_revenue_transactions WHERE DATE_FORMAT(paid_at, '%Y-%m') = ?"
        );
        $stmt->execute([$month]);
        return (float) $stmt->fetchColumn();
    }
}
