<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class PurchaseOrder extends Model
{
    protected static string $table = 'purchase_orders';

    public static function allWithProvider(): array
    {
        $sql = 'SELECT po.*, p.name AS provider_name FROM purchase_orders po LEFT JOIN providers p ON p.id = po.provider_id
                WHERE po.organization_id = ? ORDER BY po.issue_date DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $sql = 'SELECT po.*, p.name AS provider_name, p.email AS provider_email, e.title AS event_title
                FROM purchase_orders po
                LEFT JOIN providers p ON p.id = po.provider_id
                LEFT JOIN events e ON e.id = po.event_id
                WHERE po.id = ? AND po.organization_id = ? LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$id, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    public static function items(int $poId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM purchase_order_items WHERE purchase_order_id = ? AND organization_id = ? ORDER BY id ASC');
        $stmt->execute([$poId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function nextNumber(): string
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $year = date('Y');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM purchase_orders WHERE po_number LIKE ? AND organization_id = ?');
        $stmt->execute(['BC-' . $year . '-%', $orgId]);
        return sprintf('BC-%s-%03d', $year, (int) $stmt->fetchColumn() + 1);
    }

    public static function replaceItems(int $poId, array $items): void
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $pdo->prepare('DELETE FROM purchase_order_items WHERE purchase_order_id = ? AND organization_id = ?')->execute([$poId, $orgId]);
        $stmt = $pdo->prepare('INSERT INTO purchase_order_items (organization_id, purchase_order_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            $stmt->execute([$orgId, $poId, $item['description'], $item['quantity'], $item['unit_price'], $item['total']]);
        }
    }
}
