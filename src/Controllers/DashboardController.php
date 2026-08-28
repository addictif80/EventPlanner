<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Event;

class DashboardController
{
    public static function index(): void
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $count = function (string $sql) use ($pdo, $orgId) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$orgId]);
            return $stmt->fetchColumn();
        };

        $stats = [
            'clients_count' => (int) $count('SELECT COUNT(*) FROM clients WHERE organization_id = ?'),
            'upcoming_events_count' => (int) $count('SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status != "cancelled" AND organization_id = ?'),
            'quotes_pending' => (int) $count('SELECT COUNT(*) FROM quotes WHERE status = "sent" AND organization_id = ?'),
            'invoices_unpaid_amount' => (float) $count('SELECT COALESCE(SUM(total - amount_paid), 0) FROM invoices WHERE status IN ("sent", "partially_paid", "overdue") AND organization_id = ?'),
            'invoices_overdue_count' => (int) $count('SELECT COUNT(*) FROM invoices WHERE status = "overdue" AND organization_id = ?'),
            'revenue_year' => (float) $count('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE YEAR(payment_date) = YEAR(CURDATE()) AND organization_id = ?'),
            'revenue_month' => (float) $count('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE()) AND organization_id = ?'),
        ];

        $stmt = $pdo->prepare(
            'SELECT q.*, c.first_name, c.last_name, c.company_name FROM quotes q LEFT JOIN clients c ON c.id = q.client_id WHERE q.organization_id = ? ORDER BY q.created_at DESC LIMIT 5'
        );
        $stmt->execute([$orgId]);
        $recentQuotes = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT i.*, c.first_name, c.last_name, c.company_name FROM invoices i LEFT JOIN clients c ON c.id = i.client_id WHERE i.status = "overdue" AND i.organization_id = ? ORDER BY i.due_date ASC LIMIT 5'
        );
        $stmt->execute([$orgId]);
        $overdueInvoices = $stmt->fetchAll();

        View::render('dashboard/index', [
            'title' => 'Tableau de bord',
            'stats' => $stats,
            'upcomingEvents' => Event::upcoming(6),
            'recentQuotes' => $recentQuotes,
            'overdueInvoices' => $overdueInvoices,
            'requiredActions' => self::requiredActions($pdo, $orgId),
        ]);
    }

    /** "Actions requises" widget: everything currently waiting on staff attention, across the app. */
    private static function requiredActions(\PDO $pdo, int $orgId): array
    {
        $actions = [];

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM quotes WHERE organization_id = ? AND status = 'sent' AND updated_at <= DATE_SUB(NOW(), INTERVAL 5 DAY)"
        );
        $stmt->execute([$orgId]);
        if ($n = (int) $stmt->fetchColumn()) {
            $actions[] = ['label' => 'Devis à relancer (sans réponse depuis 5+ jours)', 'count' => $n, 'url' => url('/quotes'), 'icon' => 'bi-file-earmark-text'];
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE organization_id = ? AND status = 'overdue'");
        $stmt->execute([$orgId]);
        if ($n = (int) $stmt->fetchColumn()) {
            $actions[] = ['label' => 'Factures en retard de paiement', 'count' => $n, 'url' => url('/invoices'), 'icon' => 'bi-receipt'];
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE organization_id = ? AND deletion_requested_at IS NOT NULL');
        $stmt->execute([$orgId]);
        if ($n = (int) $stmt->fetchColumn()) {
            $actions[] = ['label' => 'Demandes de suppression de données (RGPD) en attente', 'count' => $n, 'url' => url('/clients'), 'icon' => 'bi-shield-lock'];
        }

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM events e
             WHERE e.organization_id = ? AND e.status = 'confirmed'
               AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
               AND NOT EXISTS (SELECT 1 FROM invoices i WHERE i.event_id = e.id AND i.type = 'deposit' AND i.status IN ('paid', 'partially_paid'))"
        );
        $stmt->execute([$orgId]);
        if ($n = (int) $stmt->fetchColumn()) {
            $actions[] = ['label' => 'Événements proches sans acompte encaissé', 'count' => $n, 'url' => url('/events'), 'icon' => 'bi-calendar-x'];
        }

        return $actions;
    }
}
