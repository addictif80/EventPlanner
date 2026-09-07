<?php

/**
 * Cron script: for every organization that configured a day of the month in
 * Paramètres > Rapports > URSSAF (company_settings.urssaf_report_day),
 * emails its admins a PDF of that organization's own client payments for
 * the previous, now-complete calendar month — a reminder for the
 * organizer's (auto-entrepreneur) monthly URSSAF declaration. Mirrors
 * bin/send_urssaf_platform_report.php, but scoped to each org's own
 * revenue rather than the platform's. No-op per organization if its
 * setting is empty or that month's report was already sent.
 * Suggested crontab (once a day; the day-of-month check happens inside):
 *   0 7 * * * php /path/to/EventPlanner/bin/send_urssaf_org_reports.php
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Database;
use App\Core\Mailer;
use App\Core\UrssafOrgReportPdf;
use App\Models\CompanySettings;
use App\Models\Payment;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

$pdo = Database::connection();
$today = (int) date('j');
$targetMonth = date('Y-m', strtotime('first day of last month'));

$stmt = $pdo->prepare(
    "SELECT organization_id, company_name FROM company_settings WHERE urssaf_report_day = ? AND (urssaf_report_last_sent_month IS NULL OR urssaf_report_last_sent_month != ?)"
);
$stmt->execute([$today, $targetMonth]);
$organizations = $stmt->fetchAll();

$sent = 0;

foreach ($organizations as $org) {
    $orgId = (int) $org['organization_id'];

    // This script runs outside any HTTP session and spans every organization,
    // so each iteration manually sets the tenant scope that Auth::organizationId()
    // (and therefore every scoped Model:: / Mailer:: call below) reads from.
    $_SESSION['organization_id'] = $orgId;
    unset($_SESSION['user_id']);

    $stmt = $pdo->prepare("SELECT email FROM users WHERE organization_id = ? AND role = 'admin' AND is_active = 1");
    $stmt->execute([$orgId]);
    $recipients = array_column($stmt->fetchAll(), 'email');
    if (empty($recipients)) {
        continue;
    }

    $payments = Payment::forOrganizationMonth($orgId, $targetMonth);
    $total = Payment::totalForOrganizationMonth($orgId, $targetMonth);
    $pdf = UrssafOrgReportPdf::build($targetMonth, $payments, $total, $org['company_name']);

    $frenchMonths = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    [$targetYear, $targetMonthNum] = explode('-', $targetMonth);
    $monthLabel = $frenchMonths[(int) $targetMonthNum] . ' ' . $targetYear;

    $html = '<p>Bonjour,</p>'
        . '<p>Voici le rapport de chiffre d\'affaires encaissé pour ' . htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') . ', en pièce jointe.</p>'
        . '<p><strong>Pensez à effectuer votre déclaration URSSAF</strong> sur <a href="https://www.autoentrepreneur.urssaf.fr">autoentrepreneur.urssaf.fr</a>.</p>';

    try {
        Mailer::send(
            $recipients,
            'Rapport URSSAF — ' . $monthLabel . ' — pensez à votre déclaration',
            $html,
            null,
            [['filename' => 'rapport-urssaf-' . $targetMonth . '.pdf', 'mimeType' => 'application/pdf', 'content' => $pdf]],
            false // internal report to the org's own admins, not a client-facing email
        );
        CompanySettings::update(['urssaf_report_last_sent_month' => $targetMonth]);
        $sent++;
    } catch (\RuntimeException $e) {
        echo "Organisation {$orgId} : échec de l'envoi ({$e->getMessage()}).\n";
    }
}

echo "{$sent} rapport(s) URSSAF envoyé(s) pour {$targetMonth}.\n";
