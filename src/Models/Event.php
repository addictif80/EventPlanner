<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Event extends Model
{
    protected static string $table = 'events';

    public static function allWithClient(): array
    {
        $sql = 'SELECT e.*, c.first_name, c.last_name, c.company_name, c.type AS client_type, et.name AS type_name
                FROM events e
                LEFT JOIN clients c ON c.id = e.client_id
                LEFT JOIN event_types et ON et.id = e.event_type_id
                WHERE e.organization_id = ?
                ORDER BY e.event_date DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $sql = 'SELECT e.*, c.first_name, c.last_name, c.company_name, c.type AS client_type,
                       et.name AS type_name, v.name AS venue_name
                FROM events e
                LEFT JOIN clients c ON c.id = e.client_id AND c.organization_id = e.organization_id
                LEFT JOIN event_types et ON et.id = e.event_type_id AND et.organization_id = e.organization_id
                LEFT JOIN venues v ON v.id = e.venue_id AND v.organization_id = e.organization_id
                WHERE e.id = ? AND e.organization_id = ? LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$id, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    public static function upcoming(int $limit = 5): array
    {
        $sql = 'SELECT e.*, c.first_name, c.last_name, c.company_name
                FROM events e LEFT JOIN clients c ON c.id = e.client_id
                WHERE e.event_date >= CURDATE() AND e.status != "cancelled" AND e.organization_id = ?
                ORDER BY e.event_date ASC LIMIT ' . (int) $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    /**
     * Real margin for one event: revenue is what has actually been invoiced
     * to the client (not just quoted — a quote can change before it's
     * billed), cost is what event_providers.cost carries for that event
     * (manually entered, or auto-synced from a confirmed purchase order —
     * see PurchaseOrder::syncEventProvider()). Equipment/matériel bookings
     * aren't priced in this app, so they're not part of the cost side.
     *
     * @return array{invoiced:float, collected:float, cost:float, margin:float, marginPercent:?float}
     */
    public static function profitability(int $eventId): array
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE event_id = ? AND organization_id = ? AND status != 'cancelled'");
        $stmt->execute([$eventId, $orgId]);
        $invoiced = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM invoices WHERE event_id = ? AND organization_id = ? AND status != 'cancelled'");
        $stmt->execute([$eventId, $orgId]);
        $collected = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(cost), 0) FROM event_providers WHERE event_id = ? AND organization_id = ? AND status != 'cancelled'");
        $stmt->execute([$eventId, $orgId]);
        $cost = (float) $stmt->fetchColumn();

        $margin = $invoiced - $cost;

        return [
            'invoiced' => $invoiced,
            'collected' => $collected,
            'cost' => $cost,
            'margin' => $margin,
            'marginPercent' => $invoiced > 0 ? round($margin / $invoiced * 100, 1) : null,
        ];
    }

    /** Same computation across every event, for the org-wide profitability report. */
    public static function profitabilityForAll(): array
    {
        $sql = "SELECT e.id, e.title, e.event_date, e.status,
                       c.first_name, c.last_name, c.company_name,
                       COALESCE(inv.invoiced, 0) AS invoiced,
                       COALESCE(prov.cost, 0) AS cost
                FROM events e
                LEFT JOIN clients c ON c.id = e.client_id
                LEFT JOIN (
                    SELECT event_id, SUM(total) AS invoiced FROM invoices
                    WHERE organization_id = ? AND status != 'cancelled' GROUP BY event_id
                ) inv ON inv.event_id = e.id
                LEFT JOIN (
                    SELECT event_id, SUM(cost) AS cost FROM event_providers
                    WHERE organization_id = ? AND status != 'cancelled' GROUP BY event_id
                ) prov ON prov.event_id = e.id
                WHERE e.organization_id = ? AND e.status != 'cancelled'
                ORDER BY e.event_date DESC";
        $stmt = Database::connection()->prepare($sql);
        $orgId = Auth::organizationId();
        $stmt->execute([$orgId, $orgId, $orgId]);

        return array_map(function ($row) {
            $invoiced = (float) $row['invoiced'];
            $cost = (float) $row['cost'];
            $margin = $invoiced - $cost;
            $row['invoiced'] = $invoiced;
            $row['cost'] = $cost;
            $row['margin'] = $margin;
            $row['marginPercent'] = $invoiced > 0 ? round($margin / $invoiced * 100, 1) : null;
            return $row;
        }, $stmt->fetchAll());
    }
}
