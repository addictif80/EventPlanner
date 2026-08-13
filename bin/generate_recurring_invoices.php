<?php

/**
 * Cron script: generates the next invoice for every recurring invoice whose
 * recurrence_next_date has arrived. Suggested crontab (daily at 6am):
 *   0 6 * * * php /path/to/EventPlanner/bin/generate_recurring_invoices.php
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Database;
use App\Models\Invoice;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

$pdo = Database::connection();
$stmt = $pdo->query(
    "SELECT id, organization_id FROM invoices WHERE is_recurring = 1 AND recurrence_next_date IS NOT NULL AND recurrence_next_date <= CURDATE()"
);
$rows = $stmt->fetchAll();

$map = ['monthly' => '+1 month', 'quarterly' => '+3 months', 'yearly' => '+1 year'];
$generated = 0;

foreach ($rows as $row) {
    $id = (int) $row['id'];

    // This script runs outside any HTTP session and spans every organization,
    // so each iteration manually sets the tenant scope that Auth::organizationId()
    // (and therefore every scoped Model:: call below) reads from.
    $_SESSION['organization_id'] = (int) $row['organization_id'];

    $invoice = Invoice::find($id);
    if (!$invoice) {
        continue;
    }

    $items = Invoice::items((int) $id);
    $number = Invoice::nextNumber();

    $newId = Invoice::create([
        'invoice_number' => $number,
        'quote_id' => $invoice['quote_id'],
        'client_id' => $invoice['client_id'],
        'event_id' => $invoice['event_id'],
        'type' => $invoice['type'],
        'status' => 'draft',
        'issue_date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+30 days')),
        'subtotal' => $invoice['subtotal'],
        'tax_rate' => $invoice['tax_rate'],
        'tax_amount' => $invoice['tax_amount'],
        'total' => $invoice['total'],
        'notes' => $invoice['notes'],
        'is_recurring' => $invoice['is_recurring'],
        'recurrence_interval' => $invoice['recurrence_interval'],
        'recurrence_parent_id' => $invoice['id'],
    ]);

    Invoice::replaceItems($newId, $items);

    if (!empty($invoice['recurrence_interval'])) {
        Invoice::update((int) $id, ['recurrence_next_date' => date('Y-m-d', strtotime($map[$invoice['recurrence_interval']]))]);
    }

    echo "Généré : {$number} (facture #{$newId}, à partir de #{$id})\n";
    $generated++;
}

echo "{$generated} facture(s) générée(s).\n";
