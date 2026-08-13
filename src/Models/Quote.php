<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Quote extends Model
{
    protected static string $table = 'quotes';

    public static function allWithClient(): array
    {
        $sql = 'SELECT q.*, c.first_name, c.last_name, c.company_name
                FROM quotes q LEFT JOIN clients c ON c.id = q.client_id
                ORDER BY q.issue_date DESC, q.id DESC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $sql = 'SELECT q.*, c.first_name, c.last_name, c.company_name, c.email AS client_email,
                       c.address, c.postal_code, c.city, c.country, e.title AS event_title
                FROM quotes q
                LEFT JOIN clients c ON c.id = q.client_id
                LEFT JOIN events e ON e.id = q.event_id
                WHERE q.id = ? LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function items(int $quoteId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY position ASC, id ASC');
        $stmt->execute([$quoteId]);
        return $stmt->fetchAll();
    }

    public static function nextNumber(): string
    {
        $pdo = Database::connection();
        $prefix = $pdo->query('SELECT quote_prefix FROM company_settings WHERE id = 1')->fetchColumn() ?: 'DEV-';
        $year = date('Y');

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE quote_number LIKE ?");
        $stmt->execute([$prefix . $year . '-%']);
        $count = (int) $stmt->fetchColumn() + 1;

        return sprintf('%s%s-%03d', $prefix, $year, $count);
    }

    public static function replaceItems(int $quoteId, array $items): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM quote_items WHERE quote_id = ?')->execute([$quoteId]);

        $stmt = $pdo->prepare(
            'INSERT INTO quote_items (quote_id, description, quantity, unit_price, total, position) VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $i => $item) {
            $stmt->execute([$quoteId, $item['description'], $item['quantity'], $item['unit_price'], $item['total'], $i]);
        }
    }
}
