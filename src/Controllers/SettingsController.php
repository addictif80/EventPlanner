<?php

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Demo;
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

        ActivityLog::record('Modification modèles emails', 'company_settings');
        Session::flash('success', 'Modèles d\'emails mis à jour.');
        redirect('/settings');
    }

    public static function generateIcsToken(): void
    {
        Auth::requireAdmin();
        ModuleAccess::requireModule('calendar_ics');
        Csrf::verifyOrFail();

        CompanySettings::update(['ics_feed_token' => bin2hex(random_bytes(24))]);
        ActivityLog::record('Régénération lien calendrier', 'company_settings');
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

    private const LOGO_UPLOAD_DIR = __DIR__ . '/../../storage/uploads/logos';

    public static function updateCompany(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        $logoData = [];
        if (input('remove_logo') === '1') {
            self::deleteStoredLogo(CompanySettings::get()['logo_path'] ?? '');
            $logoData['logo_path'] = '';
        } elseif (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $newLogoPath = self::storeLogo($_FILES['logo']);
            if ($newLogoPath !== null) {
                self::deleteStoredLogo(CompanySettings::get()['logo_path'] ?? '');
                $logoData['logo_path'] = $newLogoPath;
            } else {
                Session::flash('error', 'Logo non enregistré : format ou taille invalide (PNG/JPG/WebP/SVG, 2 Mo max).');
            }
        }

        // Secret-style credentials (Stripe, Claude/Anthropic) are only saved when
        // re-entered: an empty field means "keep the previously stored value",
        // never "erase it" — the same convention as the SMTP password field.
        $secrets = [];
        foreach (['stripe_secret_key' => 'stripe_secret_key', 'anthropic_api_key' => 'anthropic_api_key'] as $field => $column) {
            $value = input($field, '');
            if ($value !== '') {
                $secrets[$column] = $value;
            }
        }

        CompanySettings::update(array_merge([
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
            'brand_color' => preg_match('/^#[0-9a-fA-F]{6}$/', input('brand_color', '')) ? input('brand_color') : '#3b56d9',
            'stripe_publishable_key' => input('stripe_publishable_key', ''),
        ], $secrets, $logoData));

        ActivityLog::record('Modification entreprise', 'company_settings');
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

        ActivityLog::record('Modification SMTP', 'smtp_settings');
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
                '<p>Ceci est un email de test envoyé depuis votre panel EventPlanner.</p><p>Si vous recevez ce message, votre configuration SMTP fonctionne correctement.</p>',
                null,
                [],
                false
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

        if (Demo::isActive()) {
            Session::flash('error', "La suppression de l'organisation est désactivée en mode démo.");
            redirect('/settings');
        }

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

    private static function storeLogo(array $file): ?string
    {
        if ($file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
        if (!isset($allowed[$ext])) {
            return null;
        }

        if (!is_dir(self::LOGO_UPLOAD_DIR)) {
            mkdir(self::LOGO_UPLOAD_DIR, 0755, true);
        }

        $storedName = Auth::organizationId() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], self::LOGO_UPLOAD_DIR . '/' . $storedName)) {
            return null;
        }

        return $storedName;
    }

    private static function deleteStoredLogo(string $storedName): void
    {
        if ($storedName === '') {
            return;
        }
        $path = self::LOGO_UPLOAD_DIR . '/' . basename($storedName);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
