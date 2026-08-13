<?php

/**
 * Cron script: sends a payment reminder email for every overdue invoice,
 * across every organization. Suggested crontab (daily at 9am):
 *   0 9 * * * php /path/to/EventPlanner/bin/send_overdue_reminders.php
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Database;
use App\Core\Mailer;
use App\Core\View;
use App\Models\CompanySettings;
use App\Models\EmailTemplate;
use App\Models\Invoice;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

$pdo = Database::connection();
$rows = $pdo->query("SELECT id, organization_id FROM invoices WHERE status = 'overdue'")->fetchAll();

$sent = 0;

foreach ($rows as $row) {
    $id = (int) $row['id'];

    // This script runs outside any HTTP session and spans every organization,
    // so each iteration manually sets the tenant scope that Auth::organizationId()
    // (and therefore every scoped Model:: call below, plus Mailer's SMTP lookup)
    // reads from.
    $_SESSION['organization_id'] = (int) $row['organization_id'];

    $invoice = Invoice::findWithRelations($id);
    if (!$invoice || empty($invoice['client_email'])) {
        continue;
    }

    $company = CompanySettings::get();
    $template = EmailTemplate::get('reminder');
    $subject = str_replace('{number}', $invoice['invoice_number'], $template['subject'] ?? 'Rappel — facture {number} en attente de paiement');

    ob_start();
    View::render('invoices/reminder_email', ['invoice' => $invoice, 'company' => $company, 'intro' => $template['intro'] ?? null], layout: null);
    $html = ob_get_clean();

    try {
        Mailer::send($invoice['client_email'], $subject, $html);
        echo "Relance envoyée pour {$invoice['invoice_number']} ({$invoice['client_email']})\n";
        $sent++;
    } catch (\RuntimeException $e) {
        echo "Échec pour {$invoice['invoice_number']} : {$e->getMessage()}\n";
    }
}

echo "{$sent} relance(s) envoyée(s).\n";
