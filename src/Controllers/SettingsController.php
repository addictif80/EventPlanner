<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\ModuleAccess;
use App\Core\Session;
use App\Core\View;
use App\Models\CompanySettings;
use App\Models\EmailTemplate;

class SettingsController
{
    public static function index(): void
    {
        Auth::requireAdmin();

        $stmt = Database::connection()->prepare('SELECT * FROM smtp_settings WHERE organization_id = ?');
        $stmt->execute([Auth::organizationId()]);
        $smtp = $stmt->fetch() ?: [];

        View::render('settings/index', [
            'title' => 'Paramètres',
            'company' => CompanySettings::get(),
            'smtp' => $smtp,
            'templates' => EmailTemplate::all(),
        ]);
    }

    public static function updateEmailTemplates(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        foreach (['quote', 'invoice', 'reminder'] as $key) {
            if (isset($_POST['subject'][$key])) {
                EmailTemplate::update($key, trim($_POST['subject'][$key]), trim($_POST['intro'][$key] ?? ''));
            }
        }

        Session::flash('success', 'Modèles d\'emails mis à jour.');
        redirect('/settings');
    }

    public static function generateIcsToken(): void
    {
        Auth::requireAdmin();
        ModuleAccess::requireModule('calendar_ics');
        Csrf::verifyOrFail();

        CompanySettings::update(['ics_feed_token' => bin2hex(random_bytes(24))]);
        Session::flash('success', 'Lien du flux calendrier généré.');
        redirect('/settings');
    }

    public static function activityLog(): void
    {
        Auth::requireAdmin();

        $stmt = Database::connection()->prepare(
            'SELECT al.*, u.name AS user_name FROM activity_log al LEFT JOIN users u ON u.id = al.user_id WHERE al.organization_id = ? ORDER BY al.created_at DESC LIMIT 200'
        );
        $stmt->execute([Auth::organizationId()]);

        View::render('settings/activity_log', ['title' => "Journal d'activité", 'logs' => $stmt->fetchAll()]);
    }

    public static function updateCompany(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        CompanySettings::update([
            'company_name' => input('company_name', ''),
            'legal_form' => input('legal_form', ''),
            'address' => input('address', ''),
            'postal_code' => input('postal_code', ''),
            'city' => input('city', ''),
            'country' => input('country', ''),
            'phone' => input('phone', ''),
            'email' => input('email', ''),
            'website' => input('website', ''),
            'siret' => input('siret', ''),
            'vat_number' => input('vat_number', ''),
            'default_tax_rate' => (float) str_replace(',', '.', input('default_tax_rate', '20')),
            'currency' => input('currency', 'EUR'),
            'quote_prefix' => input('quote_prefix', 'DEV-'),
            'invoice_prefix' => input('invoice_prefix', 'FAC-'),
            'invoice_footer' => input('invoice_footer', ''),
        ]);

        Session::flash('success', 'Informations de l\'entreprise mises à jour.');
        redirect('/settings');
    }

    public static function updateSmtp(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        $password = input('password', '');

        $pdo = Database::connection();
        $orgId = Auth::organizationId();
        if ($password === '') {
            // Keep the previously stored password if the field is left blank.
            $stmt = $pdo->prepare(
                'UPDATE smtp_settings SET host = ?, port = ?, encryption = ?, username = ?, from_email = ?, from_name = ?, is_configured = 1 WHERE organization_id = ?'
            );
            $stmt->execute([
                input('host', ''),
                (int) input('port', 587),
                input('encryption', 'tls'),
                input('username', ''),
                input('from_email', ''),
                input('from_name', ''),
                $orgId,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE smtp_settings SET host = ?, port = ?, encryption = ?, username = ?, password = ?, from_email = ?, from_name = ?, is_configured = 1 WHERE organization_id = ?'
            );
            $stmt->execute([
                input('host', ''),
                (int) input('port', 587),
                input('encryption', 'tls'),
                input('username', ''),
                $password,
                input('from_email', ''),
                input('from_name', ''),
                $orgId,
            ]);
        }

        Session::flash('success', 'Configuration SMTP enregistrée.');
        redirect('/settings');
    }

    public static function testSmtp(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        $recipient = input('test_email', '');
        if ($recipient === '') {
            $recipient = Auth::user()['email'] ?? '';
        }

        try {
            Mailer::send(
                $recipient,
                'Test de configuration SMTP — EventPlanner',
                '<p>Ceci est un email de test envoyé depuis votre panel EventPlanner.</p><p>Si vous recevez ce message, votre configuration SMTP fonctionne correctement.</p>'
            );
            Session::flash('success', 'Email de test envoyé avec succès à ' . $recipient . '.');
        } catch (\RuntimeException $e) {
            Session::flash('error', "Échec de l'envoi du test : " . $e->getMessage());
        }

        redirect('/settings');
    }

    /**
     * Danger zone: permanently deletes the whole organization and every
     * record attached to it. Every tenant table's organization_id foreign
     * key is declared ON DELETE CASCADE (see database/schema.sql), so
     * removing the organizations row is sufficient to erase everything —
     * clients, events, quotes, invoices, users, etc.
     */
    public static function destroyOrganization(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        $stmt = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $passwordHash = $stmt->fetchColumn();

        if (!$passwordHash || !password_verify(input('password', ''), $passwordHash)) {
            Session::flash('error', 'Mot de passe incorrect.');
            redirect('/settings');
        }

        $stmt = Database::connection()->prepare('SELECT name FROM organizations WHERE id = ?');
        $stmt->execute([Auth::organizationId()]);
        $orgName = $stmt->fetchColumn();

        if (trim(input('confirm_name', '')) !== $orgName) {
            Session::flash('error', "Le nom saisi ne correspond pas au nom de l'organisation. Suppression annulée.");
            redirect('/settings');
        }

        Database::connection()->prepare('DELETE FROM organizations WHERE id = ?')->execute([Auth::organizationId()]);

        Auth::logout();
        redirect('/');
    }
}
