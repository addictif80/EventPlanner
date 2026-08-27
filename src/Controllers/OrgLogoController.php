<?php

namespace App\Controllers;

use App\Core\Database;

/**
 * Public, unauthenticated: serves an organization's uploaded logo so it can
 * be referenced from client-facing surfaces that have no session of their
 * own — outbound emails (quote/invoice/reminder), the client portal, and
 * printed quotes/invoices/contracts. A company logo isn't sensitive data,
 * so no auth/token check is needed here, only that a logo is actually set.
 */
class OrgLogoController
{
    private const LOGO_DIR = __DIR__ . '/../../storage/uploads/logos';

    private const MIME_TYPES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
    ];

    public static function show(string $organizationId): void
    {
        $stmt = Database::connection()->prepare('SELECT logo_path FROM company_settings WHERE organization_id = ?');
        $stmt->execute([$organizationId]);
        $logoPath = $stmt->fetchColumn();

        if (!$logoPath) {
            http_response_code(404);
            return;
        }

        $fullPath = self::LOGO_DIR . '/' . basename($logoPath);
        if (!is_file($fullPath)) {
            http_response_code(404);
            return;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        header('Content-Type: ' . (self::MIME_TYPES[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($fullPath);
    }
}
