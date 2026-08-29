<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class PosSession extends Model
{
    protected static string $table = 'pos_sessions';

    public static function currentOpen(): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM pos_sessions WHERE organization_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1"
        );
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    public static function allWithRelations(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT s.*, e.title AS event_title, u1.name AS opened_by_name, u2.name AS closed_by_name,
                    (SELECT COALESCE(SUM(total), 0) FROM pos_sales WHERE pos_session_id = s.id AND status = 'completed') AS sales_total
             FROM pos_sessions s
             LEFT JOIN events e ON e.id = s.event_id
             LEFT JOIN users u1 ON u1.id = s.opened_by
             LEFT JOIN users u2 ON u2.id = s.closed_by
             WHERE s.organization_id = ?
             ORDER BY s.opened_at DESC"
        );
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT s.*, e.title AS event_title, u1.name AS opened_by_name, u2.name AS closed_by_name
             FROM pos_sessions s
             LEFT JOIN events e ON e.id = s.event_id
             LEFT JOIN users u1 ON u1.id = s.opened_by
             LEFT JOIN users u2 ON u2.id = s.closed_by
             WHERE s.id = ? AND s.organization_id = ? LIMIT 1"
        );
        $stmt->execute([$id, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    /** Cash sales + opening float + manual cash-in − manual cash-out = expected cash in the drawer. */
    public static function expectedCash(int $sessionId): float
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $session = self::find($sessionId);
        if (!$session) {
            return 0.0;
        }

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM pos_sales
             WHERE pos_session_id = ? AND organization_id = ? AND status = 'completed' AND payment_method = 'cash'"
        );
        $stmt->execute([$sessionId, $orgId]);
        $cashSales = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'in' THEN amount ELSE -amount END), 0) FROM pos_cash_movements WHERE pos_session_id = ? AND organization_id = ?");
        $stmt->execute([$sessionId, $orgId]);
        $movements = (float) $stmt->fetchColumn();

        return (float) $session['opening_float'] + $cashSales + $movements;
    }

    public static function movements(int $sessionId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT m.*, u.name AS created_by_name FROM pos_cash_movements m
             LEFT JOIN users u ON u.id = m.created_by
             WHERE m.pos_session_id = ? AND m.organization_id = ? ORDER BY m.created_at DESC"
        );
        $stmt->execute([$sessionId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function totalsByPaymentMethod(int $sessionId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT payment_method, COALESCE(SUM(total), 0) AS total FROM pos_sales
             WHERE pos_session_id = ? AND organization_id = ? AND status = 'completed'
             GROUP BY payment_method"
        );
        $stmt->execute([$sessionId, Auth::organizationId()]);
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
