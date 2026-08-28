<?php

/**
 * Cron script: suspends an organization's access once its subscription has
 * been unpaid (Stripe status "past_due") for longer than the grace period
 * configured by the super admin (Paramètres système > Facturation). Opt-in:
 * does nothing unless subscription_auto_suspend_enabled is set.
 *
 * Suspension takes effect immediately for logins (Auth::attempt blocks any
 * non-super-admin user of a suspended organization) — see src/Core/Auth.php.
 * Payment recovering afterwards (Stripe webhook: subscription becomes
 * active again) automatically lifts a suspension caused by this script,
 * see SubscriptionController::clearPastDueAndReactivate() — never a
 * manually-suspended organization (suspension_reason = 'manual').
 *
 * Suggested crontab (once a day, e.g. 7am):
 *   0 7 * * * php /path/to/EventPlanner/bin/suspend_unpaid_organizations.php
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\AdminActivityLog;
use App\Core\Database;
use App\Core\Mailer;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\SystemSetting;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

$settings = SystemSetting::get();
if (empty($settings['subscription_auto_suspend_enabled'])) {
    echo "Suspension automatique désactivée (Paramètres système > Facturation) — rien à faire.\n";
    exit;
}

$graceDays = (int) ($settings['subscription_grace_period_days'] ?? 7);
$pdo = Database::connection();

$stmt = $pdo->prepare(
    "SELECT o.id, o.name, os.past_due_since
     FROM organizations o
     JOIN organization_subscriptions os ON os.organization_id = o.id
     WHERE os.status = 'past_due'
       AND os.past_due_since IS NOT NULL
       AND os.past_due_since <= DATE_SUB(NOW(), INTERVAL ? DAY)
       AND o.status != 'suspended'"
);
$stmt->execute([$graceDays]);
$organizations = $stmt->fetchAll();

$suspended = 0;

foreach ($organizations as $org) {
    Organization::update((int) $org['id'], ['status' => 'suspended', 'suspension_reason' => 'non_payment']);

    AdminActivityLog::record('organization_auto_suspended', 'organization', (int) $org['id'], $org['name']);

    Notification::toPlatform(
        'system',
        'Organisation suspendue (impayé)',
        $org['name'] . " a été suspendue automatiquement après {$graceDays} jours d'impayé.",
        '/admin/organizations/' . $org['id']
    );

    // Best-effort: the organization's own login is now blocked, so an email
    // to its admins is the only channel left to reach them (the in-app bell
    // is unreachable once they can no longer sign in).
    $adminStmt = $pdo->prepare("SELECT email FROM users WHERE organization_id = ? AND role = 'admin' AND is_active = 1");
    $adminStmt->execute([$org['id']]);
    foreach ($adminStmt->fetchAll() as $admin) {
        try {
            Mailer::sendSystem(
                $admin['email'],
                'Votre accès EventPlanner a été suspendu',
                '<p>Bonjour,</p>'
                . '<p>L\'abonnement de votre organisation <strong>' . htmlspecialchars($org['name']) . '</strong> est impayé depuis plus de ' . $graceDays . ' jours. '
                . 'Votre accès a été suspendu.</p>'
                . '<p>Régularisez votre moyen de paiement pour retrouver l\'accès automatiquement dès la confirmation du paiement.</p>'
            );
        } catch (\RuntimeException $e) {
            // Best-effort notice: suspension itself must not depend on email delivery succeeding.
        }
    }

    $suspended++;
}

echo "{$suspended} organisation(s) suspendue(s) pour impayé (délai de grâce : {$graceDays} jours).\n";
