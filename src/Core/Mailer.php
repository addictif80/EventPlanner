<?php

namespace App\Core;

class Mailer
{
    public static function settings(): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM smtp_settings WHERE organization_id = ?');
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetch() ?: [];
    }

    /** True if the organization can send email at all — its own SMTP, or the platform's as fallback. */
    public static function isConfigured(): bool
    {
        $s = self::settings();
        if (!empty($s['is_configured']) && !empty($s['host']) && !empty($s['from_email'])) {
            return true;
        }
        return self::isSystemConfigured();
    }

    /**
     * Sends via the organization's own SMTP server; if it hasn't configured
     * one (Paramètres > Email), transparently falls back to the platform's
     * system SMTP (Administration > Paramètres système) so features like
     * password reset or team invitations still work for organizations that
     * never set up their own mail server. The organization's branding
     * (display name, logo, brand color) is kept in the email either way —
     * only the underlying mail transport and technical "from" address change.
     *
     * @param string|array $to
     * @param array<int,array{filename:string,mimeType:string,content:string}> $attachments
     * @param bool $clientFacing Whether $to is a client/guest of the organization rather
     *             than the organization's own staff (invitations, password resets, SMTP
     *             test emails). Only client-facing sends on the system-SMTP fallback path
     *             warn the organization's admins that their clients are receiving emails
     *             from the platform's address instead of their own — see self::warnAdminsOfSmtpFallback().
     * @throws \RuntimeException if neither the organization's nor the platform's SMTP is configured
     */
    public static function send($to, string $subject, string $htmlBody, ?string $textBody = null, array $attachments = [], bool $clientFacing = true): void
    {
        if (Demo::isActive()) {
            Session::flash('info', "Mode démo : aucun email n'est réellement envoyé (le reste de l'action s'exécute normalement).");
            return;
        }

        $settings = self::settings();
        $company = \App\Models\CompanySettings::get();
        $usingFallback = empty($settings['host']) || empty($settings['from_email']);

        if (!$usingFallback) {
            $transport = [
                'host' => $settings['host'],
                'port' => $settings['port'],
                'encryption' => $settings['encryption'],
                'username' => $settings['username'],
                'password' => $settings['password'],
            ];
            $fromEmail = $settings['from_email'];
            $senderName = $settings['from_name'] ?: $fromEmail;
        } else {
            $systemSettings = \App\Models\SystemSetting::get();
            if (empty($systemSettings['smtp_is_configured']) || empty($systemSettings['smtp_host']) || empty($systemSettings['smtp_from_email'])) {
                throw new \RuntimeException("Aucun serveur SMTP n'est configuré (ni pour l'organisation, ni pour la plateforme). Rendez-vous dans Paramètres > Email.");
            }
            $transport = [
                'host' => $systemSettings['smtp_host'],
                'port' => $systemSettings['smtp_port'],
                'encryption' => $systemSettings['smtp_encryption'],
                'username' => $systemSettings['smtp_username'],
                'password' => $systemSettings['smtp_password'],
            ];
            // Technical sending address must be one the platform's SMTP server
            // is authorized for, but the display name still reads as the
            // organization so recipients see its identity, not the platform's.
            $fromEmail = $systemSettings['smtp_from_email'];
            $senderName = $company['company_name'] ?: $systemSettings['smtp_from_name'] ?: $fromEmail;
        }

        $client = new SmtpClient($transport);
        $recipients = is_array($to) ? $to : [$to];

        $logoUrl = org_logo_url(Auth::organizationId(), $company) ?: null;
        $brandColor = $company['brand_color'] ?? null;

        $client->send(
            $fromEmail,
            $senderName,
            $recipients,
            $subject,
            EmailDesign::wrap($htmlBody, $senderName, $logoUrl, $brandColor),
            $textBody,
            $attachments
        );

        if ($usingFallback && $clientFacing) {
            self::warnAdminsOfSmtpFallback((int) Auth::organizationId(), $company);
        }
    }

    /**
     * Warns an organization's admins, at most once a week, that their clients
     * are currently receiving emails via EventPlanner's own SMTP server
     * (because the organization hasn't configured its own) — in-app
     * notification + email, with a link to the setup guide. Only called for
     * client-facing sends (see self::send()); internal emails (invitations,
     * password resets, SMTP test) never trigger it since the admin already
     * knows in those cases.
     */
    private static function warnAdminsOfSmtpFallback(int $organizationId, array $company): void
    {
        $link = '/page/guide-configuration-smtp';

        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM notifications
             WHERE organization_id = ? AND type = 'smtp_fallback' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
             LIMIT 1"
        );
        $stmt->execute([$organizationId]);
        if ($stmt->fetchColumn()) {
            return;
        }

        $title = "Vos emails clients partent sans votre propre adresse";
        $message = "Votre organisation n'a pas encore de serveur d'envoi d'email configuré : les emails à vos clients (devis, factures, relances...) sont donc envoyés depuis l'adresse technique d'EventPlanner plutôt que depuis votre propre domaine. Configurez votre serveur SMTP pour que vos emails partent en votre nom.";

        \App\Models\Notification::toOrganization($organizationId, 'smtp_fallback', $title, $message, $link, ['admin']);

        try {
            $stmt = Database::connection()->prepare("SELECT email FROM users WHERE organization_id = ? AND role = 'admin' AND is_active = 1");
            $stmt->execute([$organizationId]);
            $adminEmails = array_column($stmt->fetchAll(), 'email');
            if (empty($adminEmails)) {
                return;
            }

            $guideUrl = full_url($link);
            $html = '<p>Bonjour,</p>'
                . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p style="margin:24px 0;"><a href="' . htmlspecialchars($guideUrl, ENT_QUOTES, 'UTF-8') . '" style="background-color:#14213d; color:#ffffff; padding:12px 22px; text-decoration:none; font-family:Helvetica,Arial,sans-serif; font-size:14px;">Configurer mon serveur d\'envoi d\'email</a></p>'
                . '<p style="color:#8a909c; font-size:12px;">Vous recevez cet email au maximum une fois par semaine tant que ce point n\'est pas réglé.</p>';

            self::sendSystem($adminEmails, "Configurez votre serveur d'envoi d'email", $html);
        } catch (\Throwable $e) {
            // Best-effort: never let the admin-warning email break the actual send that triggered it.
        }
    }

    /**
     * Platform-level emails (support ticket replies, account notices, org
     * suspension, ...) use the system SMTP server configured by the super
     * admin in Administration > Paramètres système, not a tenant's own SMTP.
     *
     * @param string|array $to
     * @throws \RuntimeException
     */
    public static function sendSystem($to, string $subject, string $htmlBody, ?string $textBody = null): void
    {
        if (Demo::isActive()) {
            Session::flash('info', "Mode démo : aucun email n'est réellement envoyé (le reste de l'action s'exécute normalement).");
            return;
        }

        $settings = \App\Models\SystemSetting::get();

        if (empty($settings['smtp_is_configured']) || empty($settings['smtp_host']) || empty($settings['smtp_from_email'])) {
            throw new \RuntimeException("Aucun serveur SMTP système n'est configuré. Rendez-vous dans Administration > Paramètres système.");
        }

        $client = new SmtpClient([
            'host' => $settings['smtp_host'],
            'port' => $settings['smtp_port'],
            'encryption' => $settings['smtp_encryption'],
            'username' => $settings['smtp_username'],
            'password' => $settings['smtp_password'],
        ]);

        $recipients = is_array($to) ? $to : [$to];
        $senderName = $settings['smtp_from_name'] ?: $settings['smtp_from_email'];

        $client->send(
            $settings['smtp_from_email'],
            $senderName,
            $recipients,
            $subject,
            EmailDesign::wrap($htmlBody, $senderName),
            $textBody
        );
    }

    public static function isSystemConfigured(): bool
    {
        $s = \App\Models\SystemSetting::get();
        return !empty($s['smtp_is_configured']) && !empty($s['smtp_host']) && !empty($s['smtp_from_email']);
    }
}
