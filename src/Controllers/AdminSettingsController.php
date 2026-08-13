<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AdminActivityLog;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\SystemSetting;

class AdminSettingsController
{
    public static function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/settings', ['title' => 'Paramètres système', 'settings' => SystemSetting::get()]);
    }

    public static function updateSmtp(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $data = [
            'platform_name' => input('platform_name', 'EventPlanner'),
            'smtp_host' => input('smtp_host', ''),
            'smtp_port' => (int) input('smtp_port', 587),
            'smtp_encryption' => in_array(input('smtp_encryption'), ['none', 'ssl', 'tls'], true) ? input('smtp_encryption') : 'tls',
            'smtp_username' => input('smtp_username', ''),
            'smtp_from_email' => input('smtp_from_email', ''),
            'smtp_from_name' => input('smtp_from_name', ''),
            'smtp_is_configured' => 1,
        ];

        $password = input('smtp_password', '');
        if ($password !== '') {
            $data['smtp_password'] = $password;
        }

        SystemSetting::update($data);
        AdminActivityLog::record('system_smtp_updated');

        Session::flash('success', 'Configuration SMTP système enregistrée.');
        redirect('/admin/settings');
    }

    public static function updateBilling(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $data = [
            'stripe_publishable_key' => input('stripe_publishable_key', ''),
            'stripe_webhook_secret' => input('stripe_webhook_secret', ''),
        ];

        $secretKey = input('stripe_secret_key', '');
        if ($secretKey !== '') {
            $data['stripe_secret_key'] = $secretKey;
        }

        SystemSetting::update($data);
        AdminActivityLog::record('system_billing_updated');

        Session::flash('success', 'Configuration de facturation Stripe enregistrée.');
        redirect('/admin/settings');
    }

    public static function testSmtp(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $recipient = input('test_email', '') ?: (Auth::user()['email'] ?? '');

        try {
            Mailer::sendSystem(
                $recipient,
                'Test SMTP système — EventPlanner',
                '<p>Ceci est un email de test envoyé depuis les paramètres système d\'EventPlanner.</p>'
            );
            Session::flash('success', 'Email de test envoyé à ' . $recipient . '.');
        } catch (\RuntimeException $e) {
            Session::flash('error', "Échec de l'envoi du test : " . $e->getMessage());
        }

        redirect('/admin/settings');
    }
}
