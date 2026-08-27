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

    public static function isConfigured(): bool
    {
        $s = self::settings();
        return !empty($s['is_configured']) && !empty($s['host']) && !empty($s['from_email']);
    }

    /**
     * @param string|array $to
     * @param array<int,array{filename:string,mimeType:string,content:string}> $attachments
     * @throws \RuntimeException
     */
    public static function send($to, string $subject, string $htmlBody, ?string $textBody = null, array $attachments = []): void
    {
        $settings = self::settings();

        if (empty($settings['host']) || empty($settings['from_email'])) {
            throw new \RuntimeException("Aucun serveur SMTP n'est configuré. Rendez-vous dans Paramètres > Email.");
        }

        $client = new SmtpClient([
            'host' => $settings['host'],
            'port' => $settings['port'],
            'encryption' => $settings['encryption'],
            'username' => $settings['username'],
            'password' => $settings['password'],
        ]);

        $recipients = is_array($to) ? $to : [$to];
        $senderName = $settings['from_name'] ?: $settings['from_email'];

        $company = \App\Models\CompanySettings::get();
        $logoUrl = org_logo_url(Auth::organizationId(), $company) ?: null;
        $brandColor = $company['brand_color'] ?? null;

        $client->send(
            $settings['from_email'],
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
