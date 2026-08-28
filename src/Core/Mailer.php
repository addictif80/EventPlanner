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
     * @throws \RuntimeException if neither the organization's nor the platform's SMTP is configured
     */
    public static function send($to, string $subject, string $htmlBody, ?string $textBody = null, array $attachments = []): void
    {
        $settings = self::settings();
        $company = \App\Models\CompanySettings::get();

        if (!empty($settings['host']) && !empty($settings['from_email'])) {
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
