<?php

namespace App\Controllers;

use App\Core\Database;

class ExportController
{
    public static function invoicesCsv(): void
    {
        $rows = Database::connection()->query(
            "SELECT i.invoice_number, i.issue_date, i.due_date, i.status,
                    COALESCE(NULLIF(c.company_name, ''), CONCAT(c.first_name, ' ', c.last_name)) AS client,
                    i.subtotal, i.tax_rate, i.tax_amount, i.total, i.amount_paid, (i.total - i.amount_paid) AS remaining
             FROM invoices i JOIN clients c ON c.id = i.client_id
             ORDER BY i.issue_date ASC"
        )->fetchAll();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="factures_export.csv"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, ['Numéro', 'Date émission', 'Échéance', 'Statut', 'Client', 'Sous-total HT', 'TVA %', 'Montant TVA', 'Total TTC', 'Payé', 'Restant dû'], ';', '"', '\\');

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['invoice_number'], $r['issue_date'], $r['due_date'], $r['status'], $r['client'],
                $r['subtotal'], $r['tax_rate'], $r['tax_amount'], $r['total'], $r['amount_paid'], $r['remaining'],
            ], ';', '"', '\\');
        }

        fclose($out);
        exit;
    }

    public static function clientsCsv(): void
    {
        $rows = Database::connection()->query(
            "SELECT COALESCE(NULLIF(company_name, ''), CONCAT(first_name, ' ', last_name)) AS name, email, phone, city, tags
             FROM clients WHERE email != '' ORDER BY name ASC"
        )->fetchAll();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="clients_export.csv"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Nom', 'Email', 'Téléphone', 'Ville', 'Tags'], ';', '"', '\\');

        foreach ($rows as $r) {
            fputcsv($out, [$r['name'], $r['email'], $r['phone'], $r['city'], $r['tags']], ';', '"', '\\');
        }

        fclose($out);
        exit;
    }
}
