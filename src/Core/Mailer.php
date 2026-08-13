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
     * @throws \RuntimeException
     */
    public static function send($to, string $subject, string $htmlBody, ?string $textBody = null): void
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

        $client->send(
            $settings['from_email'],
            $settings['from_name'] ?: $settings['from_email'],
            $recipients,
            $subject,
            $htmlBody,
            $textBody
        );
    }
}
