<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class CreditNote extends Model
{
    protected static string $table = 'credit_notes';

    public static function forInvoice(int $invoiceId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM credit_notes WHERE invoice_id = ? AND organization_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$invoiceId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function nextNumber(): string
    {
        $pdo = Database::connection();
        $year = date('Y');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM credit_notes WHERE credit_note_number LIKE ? AND organization_id = ?');
        $stmt->execute(['AV-' . $year . '-%', Auth::organizationId()]);
        return sprintf('AV-%s-%03d', $year, (int) $stmt->fetchColumn() + 1);
    }

    public static function findWithRelations(int $id): ?array
    {
        $sql = 'SELECT cn.*, i.invoice_number, c.first_name, c.last_name, c.company_name
                FROM credit_notes cn
                JOIN invoices i ON i.id = cn.invoice_id
                JOIN clients c ON c.id = cn.client_id
                WHERE cn.id = ? AND cn.organization_id = ? LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$id, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }
}
