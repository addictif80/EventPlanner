<?php

/**
 * Cron script: on the day of the month configured in Administration >
 * Paramètres système (system_settings.urssaf_report_day), emails every
 * super admin a PDF report of the platform's own Stripe revenue for the
 * previous, now-complete calendar month — a reminder to file the operator's
 * (auto-entrepreneur) monthly URSSAF declaration. No-op if the setting is
 * empty (feature disabled) or if that month's report was already sent.
 * Suggested crontab (once a day; the day-of-month check happens inside):
 *   0 7 * * * php /path/to/EventPlanner/bin/send_urssaf_platform_report.php
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Mailer;
use App\Core\UrssafPlatformReportPdf;
use App\Models\PlatformRevenueTransaction;
use App\Models\SystemSetting;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

$settings = SystemSetting::get();
$reportDay = $settings['urssaf_report_day'] ?? null;

if (empty($reportDay)) {
    exit(0);
}

if ((int) date('j') !== (int) $reportDay) {
    exit(0);
}

$targetMonth = date('Y-m', strtotime('first day of last month'));

if (($settings['urssaf_report_last_sent_month'] ?? null) === $targetMonth) {
    exit(0);
}

$transactions = PlatformRevenueTransaction::forMonth($targetMonth);
$total = PlatformRevenueTransaction::totalForMonth($targetMonth);
$platformName = $settings['platform_name'] ?? 'EventPlanner';

$pdf = UrssafPlatformReportPdf::build($targetMonth, $transactions, $total, $platformName);

$stmt = \App\Core\Database::connection()->prepare("SELECT email FROM users WHERE is_super_admin = 1 AND is_active = 1");
$stmt->execute();
$recipients = array_column($stmt->fetchAll(), 'email');

if (!empty($recipients)) {
    $frenchMonths = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    [$targetYear, $targetMonthNum] = explode('-', $targetMonth);
    $monthLabel = $frenchMonths[(int) $targetMonthNum] . ' ' . $targetYear;
    $html = '<p>Bonjour,</p>'
        . '<p>Voici le rapport de chiffre d\'affaires encaissé pour ' . htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') . ', en pièce jointe.</p>'
        . '<p><strong>Pensez à effectuer votre déclaration URSSAF</strong> sur <a href="https://www.autoentrepreneur.urssaf.fr">autoentrepreneur.urssaf.fr</a>.</p>';

    try {
        Mailer::sendSystem(
            $recipients,
            'Rapport URSSAF — ' . $monthLabel . ' — pensez à votre déclaration',
            $html,
            null,
            [['filename' => 'rapport-urssaf-' . $targetMonth . '.pdf', 'mimeType' => 'application/pdf', 'content' => $pdf]]
        );
        SystemSetting::update(['urssaf_report_last_sent_month' => $targetMonth]);
        echo "Rapport URSSAF envoyé pour {$targetMonth} à " . implode(', ', $recipients) . ".\n";
    } catch (\RuntimeException $e) {
        echo "Échec de l'envoi du rapport URSSAF : " . $e->getMessage() . "\n";
    }
} else {
    echo "Aucun super admin actif à notifier.\n";
}
