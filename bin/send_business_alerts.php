<?php

/**
 * Cron script: proactive business alerts, pushed to organization staff via
 * the in-app/Web Push notification system (App\Models\Notification).
 * Suggested crontab (once a day, e.g. 8am):
 *   0 8 * * * php /path/to/EventPlanner/bin/send_business_alerts.php
 *
 * Each check is deduplicated by (organization, type, link): it won't
 * re-notify about the same quote/invoice/event more than once every 3 days,
 * so this can safely run daily without spamming staff.
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Database;
use App\Models\Notification;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

$pdo = Database::connection();
$sent = 0;

function alreadyAlerted(\PDO $pdo, int $organizationId, string $type, string $link): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM notifications
         WHERE organization_id = ? AND type = ? AND link = ? AND created_at > DATE_SUB(NOW(), INTERVAL 3 DAY)
         LIMIT 1"
    );
    $stmt->execute([$organizationId, $type, $link]);
    return (bool) $stmt->fetchColumn();
}

// --- 1. Devis envoyés sans réponse depuis plus de 5 jours ---
$stmt = $pdo->query(
    "SELECT q.id, q.organization_id, q.quote_number, c.first_name, c.last_name, c.company_name
     FROM quotes q JOIN clients c ON c.id = q.client_id
     WHERE q.status = 'sent' AND q.updated_at <= DATE_SUB(NOW(), INTERVAL 5 DAY)"
);
foreach ($stmt->fetchAll() as $row) {
    $link = '/quotes/' . $row['id'];
    if (alreadyAlerted($pdo, (int) $row['organization_id'], 'alert', $link)) {
        continue;
    }
    $clientName = $row['company_name'] ?: trim($row['first_name'] . ' ' . $row['last_name']);
    Notification::toOrganization(
        (int) $row['organization_id'],
        'alert',
        'Devis sans réponse',
        'Le devis ' . $row['quote_number'] . ' (' . $clientName . ') est en attente de réponse depuis plus de 5 jours.',
        $link
    );
    $sent++;
}

// --- 2. Factures bientôt échues (dans les 3 prochains jours) ---
$stmt = $pdo->query(
    "SELECT id, organization_id, invoice_number, due_date
     FROM invoices
     WHERE status IN ('sent', 'overdue') AND due_date IS NOT NULL
       AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)"
);
foreach ($stmt->fetchAll() as $row) {
    $link = '/invoices/' . $row['id'];
    if (alreadyAlerted($pdo, (int) $row['organization_id'], 'alert', $link)) {
        continue;
    }
    Notification::toOrganization(
        (int) $row['organization_id'],
        'alert',
        'Facture bientôt échue',
        'La facture ' . $row['invoice_number'] . ' arrive à échéance le ' . date('d/m/Y', strtotime($row['due_date'])) . '.',
        $link
    );
    $sent++;
}

// --- 3. Acompte manquant pour un événement confirmé proche (moins de 15 jours) ---
$stmt = $pdo->query(
    "SELECT e.id, e.organization_id, e.title, e.event_date
     FROM events e
     WHERE e.status = 'confirmed'
       AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
       AND NOT EXISTS (
           SELECT 1 FROM invoices i
           WHERE i.event_id = e.id AND i.type = 'deposit' AND i.status IN ('paid', 'partially_paid')
       )"
);
foreach ($stmt->fetchAll() as $row) {
    $link = '/events/' . $row['id'];
    if (alreadyAlerted($pdo, (int) $row['organization_id'], 'alert', $link)) {
        continue;
    }
    Notification::toOrganization(
        (int) $row['organization_id'],
        'alert',
        'Acompte manquant',
        "L'événement « " . $row['title'] . ' » a lieu le ' . date('d/m/Y', strtotime($row['event_date'])) . " sans acompte encaissé.",
        $link
    );
    $sent++;
}

// --- 4. Double réservation d'un même lieu ---
$stmt = $pdo->query(
    "SELECT e1.id AS event1_id, e2.id AS event2_id, e1.organization_id, e1.title AS title1, e2.title AS title2, e1.event_date, v.name AS venue_name
     FROM events e1
     JOIN events e2 ON e2.organization_id = e1.organization_id
         AND e2.venue_id = e1.venue_id
         AND e2.event_date = e1.event_date
         AND e2.id > e1.id
     JOIN venues v ON v.id = e1.venue_id
     WHERE e1.venue_id IS NOT NULL
       AND e1.status != 'cancelled' AND e2.status != 'cancelled'
       AND e1.event_date >= CURDATE()"
);
foreach ($stmt->fetchAll() as $row) {
    $link = '/events/' . $row['event1_id'];
    if (alreadyAlerted($pdo, (int) $row['organization_id'], 'alert', $link)) {
        continue;
    }
    Notification::toOrganization(
        (int) $row['organization_id'],
        'alert',
        'Double réservation de lieu',
        $row['venue_name'] . ' est réservé à la fois pour « ' . $row['title1'] . ' » et « ' . $row['title2'] . ' » le ' . date('d/m/Y', strtotime($row['event_date'])) . '.',
        $link
    );
    $sent++;
}

echo "{$sent} alerte(s) envoyée(s).\n";
