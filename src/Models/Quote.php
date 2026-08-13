<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Quote extends Model
{
    protected static string $table = 'quotes';

    public static function allWithClient(): array
    {
        $sql = 'SELECT q.*, c.first_name, c.last_name, c.company_name
                FROM quotes q LEFT JOIN clients c ON c.id = q.client_id
                WHERE q.organization_id = ?
                ORDER BY q.issue_date DESC, q.id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $sql = 'SELECT q.*, c.first_name, c.last_name, c.company_name, c.email AS client_email,
                       c.address, c.postal_code, c.city, c.country, e.title AS event_title
                FROM quotes q
                LEFT JOIN clients c ON c.id = q.client_id
                LEFT JOIN events e ON e.id = q.event_id
                WHERE q.id = ? AND q.organization_id = ? LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$id, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    public static function items(int $quoteId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM quote_items WHERE quote_id = ? AND organization_id = ? ORDER BY position ASC, id ASC');
        $stmt->execute([$quoteId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function nextNumber(): string
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $stmt = $pdo->prepare('SELECT quote_prefix FROM company_settings WHERE organization_id = ?');
        $stmt->execute([$orgId]);
        $prefix = $stmt->fetchColumn() ?: 'DEV-';
        $year = date('Y');

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM quotes WHERE quote_number LIKE ? AND organization_id = ?');
        $stmt->execute([$prefix . $year . '-%', $orgId]);
        $count = (int) $stmt->fetchColumn() + 1;

        return sprintf('%s%s-%03d', $prefix, $year, $count);
    }

    public static function replaceItems(int $quoteId, array $items): void
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $pdo->prepare('DELETE FROM quote_items WHERE quote_id = ? AND organization_id = ?')->execute([$quoteId, $orgId]);

        $stmt = $pdo->prepare(
            'INSERT INTO quote_items (organization_id, quote_id, description, quantity, unit_price, total, position) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $i => $item) {
            $stmt->execute([$orgId, $quoteId, $item['description'], $item['quantity'], $item['unit_price'], $item['total'], $i]);
        }
    }
}
