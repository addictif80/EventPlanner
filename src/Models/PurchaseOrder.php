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
                LEFT JOIN providers p ON p.id = po.provider_id AND p.organization_id = po.organization_id
                LEFT JOIN events e ON e.id = po.event_id AND e.organization_id = po.organization_id
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

    /**
     * Keeps the event's "Prestataires" list (event_providers) in sync with a
     * purchase order linked to an event: one auto-managed event_providers
     * row per PO (matched by purchase_order_id), its cost mirroring the PO
     * total and its status mirroring the PO status, so the confirmed cost is
     * automatically reflected on the event page and can be picked up when
     * building a quote for that event (see QuoteController::create()).
     * No-op if the PO has no event_id — a standalone PO isn't tied to any
     * event's provider list.
     */
    public static function syncEventProvider(int $poId): void
    {
        $po = self::find($poId);
        if (!$po || empty($po['event_id'])) {
            return;
        }

        $statusMap = ['confirmed' => 'confirmed', 'cancelled' => 'cancelled'];
        $status = $statusMap[$po['status']] ?? 'pending';

        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $notes = 'Bon de commande ' . $po['po_number'] . ' (généré automatiquement)';

        $stmt = $pdo->prepare(
            'INSERT INTO event_providers (organization_id, event_id, provider_id, purchase_order_id, cost, status, notes)
             VALUES (:org_id, :event_id, :provider_id, :po_id, :cost, :status, :notes)
             ON DUPLICATE KEY UPDATE event_id = VALUES(event_id), provider_id = VALUES(provider_id),
                 cost = VALUES(cost), status = VALUES(status), notes = VALUES(notes)'
        );
        $stmt->execute([
            'org_id' => $orgId,
            'event_id' => $po['event_id'],
            'provider_id' => $po['provider_id'],
            'po_id' => $poId,
            'cost' => $po['total'],
            'status' => $status,
            'notes' => $notes,
        ]);
    }

    /** Confirmed purchase orders for an event, used to prefill a quote's line items. */
    public static function confirmedForEvent(int $eventId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT po.*, p.name AS provider_name FROM purchase_orders po
             JOIN providers p ON p.id = po.provider_id
             WHERE po.event_id = ? AND po.organization_id = ? AND po.status = 'confirmed'
             ORDER BY po.issue_date ASC"
        );
        $stmt->execute([$eventId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }
}
