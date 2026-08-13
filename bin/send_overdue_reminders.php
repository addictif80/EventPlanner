<?php

/**
 * Cron script: sends a payment reminder email for every overdue invoice.
 * Suggested crontab (daily at 9am):
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
$ids = $pdo->query("SELECT id FROM invoices WHERE status = 'overdue'")->fetchAll(\PDO::FETCH_COLUMN);

$company = CompanySettings::get();
$template = EmailTemplate::get('reminder');
$sent = 0;

foreach ($ids as $id) {
    $invoice = Invoice::findWithRelations((int) $id);
    if (!$invoice || empty($invoice['client_email'])) {
        continue;
    }

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
