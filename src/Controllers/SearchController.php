<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Client;

/** Global quick-search (Ctrl/Cmd+K): clients, events, quotes, invoices, matched by name/number, scoped to the organization. */
class SearchController
{
    public static function search(): void
    {
        Auth::requireLogin();

        $term = trim($_GET['q'] ?? '');
        header('Content-Type: application/json; charset=utf-8');

        if (mb_strlen($term) < 2) {
            echo json_encode(['results' => []]);
            return;
        }

        $orgId = Auth::organizationId();
        $like = '%' . $term . '%';
        $pdo = Database::connection();
        $results = [];

        $stmt = $pdo->prepare(
            'SELECT id, first_name, last_name, company_name FROM clients
             WHERE organization_id = ? AND (first_name LIKE ? OR last_name LIKE ? OR company_name LIKE ? OR email LIKE ?)
             ORDER BY created_at DESC LIMIT 5'
        );
        $stmt->execute([$orgId, $like, $like, $like, $like]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = ['category' => 'Clients', 'label' => Client::displayName($row), 'url' => url('/clients/' . $row['id'])];
        }

        $stmt = $pdo->prepare('SELECT id, title FROM events WHERE organization_id = ? AND title LIKE ? ORDER BY event_date DESC LIMIT 5');
        $stmt->execute([$orgId, $like]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = ['category' => 'Événements', 'label' => $row['title'], 'url' => url('/events/' . $row['id'])];
        }

        $stmt = $pdo->prepare('SELECT id, quote_number FROM quotes WHERE organization_id = ? AND quote_number LIKE ? ORDER BY created_at DESC LIMIT 5');
        $stmt->execute([$orgId, $like]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = ['category' => 'Devis', 'label' => $row['quote_number'], 'url' => url('/quotes/' . $row['id'])];
        }

        $stmt = $pdo->prepare('SELECT id, invoice_number FROM invoices WHERE organization_id = ? AND invoice_number LIKE ? ORDER BY created_at DESC LIMIT 5');
        $stmt->execute([$orgId, $like]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = ['category' => 'Factures', 'label' => $row['invoice_number'], 'url' => url('/invoices/' . $row['id'])];
        }

        echo json_encode(['results' => $results]);
    }
}
