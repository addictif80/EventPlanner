<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Invoice extends Model
{
    protected static string $table = 'invoices';

    public static function allWithClient(): array
    {
        $sql = 'SELECT i.*, c.first_name, c.last_name, c.company_name
                FROM invoices i LEFT JOIN clients c ON c.id = i.client_id
                WHERE i.organization_id = ?
                ORDER BY i.issue_date DESC, i.id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $sql = 'SELECT i.*, c.first_name, c.last_name, c.company_name, c.email AS client_email,
                       c.address, c.postal_code, c.city, c.country, e.title AS event_title
                FROM invoices i
                LEFT JOIN clients c ON c.id = i.client_id AND c.organization_id = i.organization_id
                LEFT JOIN events e ON e.id = i.event_id AND e.organization_id = i.organization_id
                WHERE i.id = ? AND i.organization_id = ? LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$id, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    public static function items(int $invoiceId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? AND organization_id = ? ORDER BY position ASC, id ASC');
        $stmt->execute([$invoiceId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function payments(int $invoiceId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM payments WHERE invoice_id = ? AND organization_id = ? ORDER BY payment_date DESC, id DESC');
        $stmt->execute([$invoiceId, Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function nextNumber(): string
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $stmt = $pdo->prepare('SELECT invoice_prefix FROM company_settings WHERE organization_id = ?');
        $stmt->execute([$orgId]);
        $prefix = $stmt->fetchColumn() ?: 'FAC-';
        $year = date('Y');

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM invoices WHERE invoice_number LIKE ? AND organization_id = ?');
        $stmt->execute([$prefix . $year . '-%', $orgId]);
        $count = (int) $stmt->fetchColumn() + 1;

        return sprintf('%s%s-%03d', $prefix, $year, $count);
    }

    public static function replaceItems(int $invoiceId, array $items): void
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = ? AND organization_id = ?')->execute([$invoiceId, $orgId]);

        $stmt = $pdo->prepare(
            'INSERT INTO invoice_items (organization_id, invoice_id, description, quantity, unit_price, total, position) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $i => $item) {
            $stmt->execute([$orgId, $invoiceId, $item['description'], $item['quantity'], $item['unit_price'], $item['total'], $i]);
        }
    }

    public static function recalculatePaidStatus(int $invoiceId): void
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND organization_id = ?');
        $stmt->execute([$invoiceId, $orgId]);
        $paid = (float) $stmt->fetchColumn();

        // Avoirs (credit notes) reduce the amount actually owed, same as a payment would.
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM credit_notes WHERE invoice_id = ? AND organization_id = ?');
        $stmt->execute([$invoiceId, $orgId]);
        $paid += (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT total, due_date, status FROM invoices WHERE id = ? AND organization_id = ?');
        $stmt->execute([$invoiceId, $orgId]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return;
        }

        $status = $invoice['status'];
        if ($status !== 'cancelled') {
            if ($paid >= (float) $invoice['total'] && (float) $invoice['total'] > 0) {
                $status = 'paid';
            } elseif ($paid > 0) {
                $status = 'partially_paid';
            } elseif (in_array($status, ['sent', 'overdue'], true)) {
                // No payment recorded: keep a sent invoice "sent", or flip to
                // "overdue" once past its due date. Drafts stay drafts until sent.
                $status = (!empty($invoice['due_date']) && strtotime($invoice['due_date']) < strtotime('today'))
                    ? 'overdue'
                    : 'sent';
            }
        }

        $update = $pdo->prepare('UPDATE invoices SET amount_paid = ?, status = ? WHERE id = ? AND organization_id = ?');
        $update->execute([$paid, $status, $invoiceId, $orgId]);
    }
}
