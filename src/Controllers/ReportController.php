<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\ModuleAccess;
use App\Core\View;

class ReportController
{
    public static function index(): void
    {
        ModuleAccess::requireModule('reports');
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
             FROM payments
             WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND organization_id = ?
             GROUP BY ym ORDER BY ym ASC"
        );
        $stmt->execute([$orgId]);
        $revenueByMonth = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(et.name, 'Non catégorisé') AS label, SUM(p.amount) AS total
             FROM payments p
             JOIN invoices i ON i.id = p.invoice_id
             LEFT JOIN events e ON e.id = i.event_id
             LEFT JOIN event_types et ON et.id = e.event_type_id
             WHERE p.organization_id = ?
             GROUP BY label ORDER BY total DESC"
        );
        $stmt->execute([$orgId]);
        $revenueByEventType = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(NULLIF(c.company_name, ''), CONCAT(c.first_name, ' ', c.last_name)) AS label, SUM(p.amount) AS total
             FROM payments p JOIN invoices i ON i.id = p.invoice_id JOIN clients c ON c.id = i.client_id
             WHERE p.organization_id = ?
             GROUP BY c.id ORDER BY total DESC LIMIT 8"
        );
        $stmt->execute([$orgId]);
        $topClients = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT p.name AS label, SUM(ep.cost) AS total
             FROM event_providers ep JOIN providers p ON p.id = ep.provider_id
             WHERE ep.cost IS NOT NULL AND ep.organization_id = ?
             GROUP BY p.id ORDER BY total DESC LIMIT 8"
        );
        $stmt->execute([$orgId]);
        $topProviders = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT status, COUNT(*) AS c FROM quotes WHERE organization_id = ? GROUP BY status");
        $stmt->execute([$orgId]);
        $quoteStats = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        $sentOrDecided = ($quoteStats['sent'] ?? 0) + ($quoteStats['accepted'] ?? 0) + ($quoteStats['refused'] ?? 0) + ($quoteStats['expired'] ?? 0);
        $conversionRate = $sentOrDecided > 0 ? round((($quoteStats['accepted'] ?? 0) / $sentOrDecided) * 100, 1) : 0;

        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(due_date, '%Y-%m') AS ym, SUM(total - amount_paid) AS total
             FROM invoices
             WHERE status IN ('sent', 'partially_paid', 'overdue') AND due_date IS NOT NULL AND organization_id = ?
             GROUP BY ym ORDER BY ym ASC LIMIT 12"
        );
        $stmt->execute([$orgId]);
        $forecast = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT AVG(rating) FROM satisfaction_surveys WHERE rating IS NOT NULL AND organization_id = ?");
        $stmt->execute([$orgId]);
        $satisfactionAvg = $stmt->fetchColumn();

        View::render('reports/index', [
            'title' => 'Rapports & statistiques',
            'revenueByMonth' => $revenueByMonth,
            'revenueByEventType' => $revenueByEventType,
            'topClients' => $topClients,
            'topProviders' => $topProviders,
            'quoteStats' => $quoteStats,
            'conversionRate' => $conversionRate,
            'forecast' => $forecast,
            'satisfactionAvg' => $satisfactionAvg ? round((float) $satisfactionAvg, 1) : null,
        ]);
    }
}
