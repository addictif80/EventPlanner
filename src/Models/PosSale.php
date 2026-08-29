<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class PosSale extends Model
{
    protected static string $table = 'pos_sales';

    public static function forSession(int $sessionId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, c.first_name, c.last_name, c.company_name FROM pos_sales s
             LEFT JOIN clients c ON c.id = s.client_id
             WHERE s.pos_session_id = ? AND s.organization_id = ? ORDER BY s.created_at DESC'
        );
        $stmt->execute([$sessionId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function items(int $saleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM pos_sale_items WHERE pos_sale_id = ? AND organization_id = ? ORDER BY id ASC');
        $stmt->execute([$saleId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function insertItems(int $saleId, array $items): void
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $stmt = $pdo->prepare(
            'INSERT INTO pos_sale_items (organization_id, pos_sale_id, product_id, description, quantity, unit_price, total)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $stmt->execute([$orgId, $saleId, $item['product_id'] ?? null, $item['description'], $item['quantity'], $item['unit_price'], $item['total']]);
        }
    }

    public static function nextNumber(): string
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $today = date('Ymd');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pos_sales WHERE sale_number LIKE ? AND organization_id = ?");
        $stmt->execute(['V-' . $today . '-%', $orgId]);
        return sprintf('V-%s-%03d', $today, (int) $stmt->fetchColumn() + 1);
    }

    /**
     * The public token page (PosReceiptController) has no Auth session, so it
     * cannot use the scoped Model helpers above — the token row itself is the
     * only source of truth for which organization the sale belongs to.
     */
    public static function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, ps.event_id FROM pos_sales s JOIN pos_sessions ps ON ps.id = s.pos_session_id WHERE s.access_token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function itemsUnscoped(int $saleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM pos_sale_items WHERE pos_sale_id = ? ORDER BY id ASC');
        $stmt->execute([$saleId]);
        return $stmt->fetchAll();
    }
}
