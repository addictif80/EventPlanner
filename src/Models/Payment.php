<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Payment extends Model
{
    protected static string $table = 'payments';

    /**
     * Every payment received by this organization for one calendar month —
     * the cash-basis detail behind ReportController::urssaf()'s monthly
     * total, reused for the org's own monthly URSSAF report PDF.
     *
     * @param string $month "YYYY-MM"
     */
    public static function forOrganizationMonth(int $organizationId, string $month): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT p.*, i.invoice_number, c.first_name, c.last_name, c.company_name
             FROM payments p
             JOIN invoices i ON i.id = p.invoice_id AND i.organization_id = p.organization_id
             JOIN clients c ON c.id = i.client_id AND c.organization_id = p.organization_id
             WHERE p.organization_id = ? AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?
             ORDER BY p.payment_date ASC"
        );
        $stmt->execute([$organizationId, $month]);
        return $stmt->fetchAll();
    }

    public static function totalForOrganizationMonth(int $organizationId, string $month): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE organization_id = ? AND DATE_FORMAT(payment_date, '%Y-%m') = ?"
        );
        $stmt->execute([$organizationId, $month]);
        return (float) $stmt->fetchColumn();
    }
}
