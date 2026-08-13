<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\Event;

class DashboardController
{
    public static function index(): void
    {
        $pdo = Database::connection();

        $stats = [
            'clients_count' => (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
            'upcoming_events_count' => (int) $pdo->query('SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status != "cancelled"')->fetchColumn(),
            'quotes_pending' => (int) $pdo->query('SELECT COUNT(*) FROM quotes WHERE status = "sent"')->fetchColumn(),
            'invoices_unpaid_amount' => (float) $pdo->query('SELECT COALESCE(SUM(total - amount_paid), 0) FROM invoices WHERE status IN ("sent", "partially_paid", "overdue")')->fetchColumn(),
            'invoices_overdue_count' => (int) $pdo->query('SELECT COUNT(*) FROM invoices WHERE status = "overdue"')->fetchColumn(),
            'revenue_year' => (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE YEAR(payment_date) = YEAR(CURDATE())')->fetchColumn(),
            'revenue_month' => (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE())')->fetchColumn(),
        ];

        $recentQuotes = $pdo->query(
            'SELECT q.*, c.first_name, c.last_name, c.company_name FROM quotes q LEFT JOIN clients c ON c.id = q.client_id ORDER BY q.created_at DESC LIMIT 5'
        )->fetchAll();

        $overdueInvoices = $pdo->query(
            'SELECT i.*, c.first_name, c.last_name, c.company_name FROM invoices i LEFT JOIN clients c ON c.id = i.client_id WHERE i.status = "overdue" ORDER BY i.due_date ASC LIMIT 5'
        )->fetchAll();

        View::render('dashboard/index', [
            'title' => 'Tableau de bord',
            'stats' => $stats,
            'upcomingEvents' => Event::upcoming(6),
            'recentQuotes' => $recentQuotes,
            'overdueInvoices' => $overdueInvoices,
        ]);
    }
}
