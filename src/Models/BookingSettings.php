<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

class BookingSettings
{
    public static function get(): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM booking_settings WHERE organization_id = ?');
        $stmt->execute([Auth::organizationId()]);
        $row = $stmt->fetch();
        if (!$row) {
            return [];
        }
        $row['weekly_hours'] = json_decode($row['weekly_hours'] ?? '', true) ?: [];
        return $row;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM booking_settings WHERE public_slug = ? AND is_enabled = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['weekly_hours'] = json_decode($row['weekly_hours'] ?? '', true) ?: [];
        return $row;
    }

    public static function save(array $data): void
    {
        $orgId = Auth::organizationId();
        $data['weekly_hours'] = json_encode($data['weekly_hours'], JSON_UNESCAPED_UNICODE);

        $columns = array_keys($data);
        $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $columns));
        $updates = implode(', ', array_map(fn($c) => "{$c} = VALUES({$c})", $columns));
        $data['organization_id'] = $orgId;

        $stmt = Database::connection()->prepare(
            "INSERT INTO booking_settings (organization_id, " . implode(', ', $columns) . ")
             VALUES (:organization_id, {$placeholders})
             ON DUPLICATE KEY UPDATE {$updates}"
        );
        $stmt->execute($data);
    }

    /** Ensures a stable, globally-unique public URL slug, generated once from the company name. */
    public static function ensureSlug(): string
    {
        $existing = self::get();
        if (!empty($existing['public_slug'])) {
            return $existing['public_slug'];
        }

        $company = CompanySettings::get();
        $base = self::slugify($company['company_name'] ?? '') ?: 'organisation';
        $pdo = Database::connection();
        $slug = $base;
        $suffix = 1;
        $stmt = $pdo->prepare('SELECT 1 FROM booking_settings WHERE public_slug = ? LIMIT 1');
        do {
            $stmt->execute([$slug]);
            if (!$stmt->fetchColumn()) {
                break;
            }
            $suffix++;
            $slug = $base . '-' . $suffix;
        } while (true);

        self::save([
            'is_enabled' => $existing['is_enabled'] ?? 0,
            'public_slug' => $slug,
            'slot_duration_minutes' => $existing['slot_duration_minutes'] ?? 30,
            'buffer_minutes' => $existing['buffer_minutes'] ?? 0,
            'min_notice_hours' => $existing['min_notice_hours'] ?? 24,
            'max_advance_days' => $existing['max_advance_days'] ?? 60,
            'weekly_hours' => $existing['weekly_hours'] ?? [],
            'location_type' => $existing['location_type'] ?? 'Téléphone',
            'meeting_instructions' => $existing['meeting_instructions'] ?? '',
        ]);

        return $slug;
    }

    private static function slugify(string $text): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $ascii));
        return trim($slug, '-');
    }
}
