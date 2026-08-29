<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Appointment extends Model
{
    protected static string $table = 'appointments';

    public static function upcoming(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM appointments WHERE organization_id = ? AND status = 'confirmed' AND starts_at >= NOW() ORDER BY starts_at ASC"
        );
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function history(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM appointments WHERE organization_id = ? ORDER BY starts_at DESC LIMIT 100"
        );
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    /**
     * Free slots for one calendar day, derived from the org's weekly opening
     * hours minus any already-booked confirmed appointment and the
     * configured notice/buffer — computed fresh on every request rather than
     * stored, so there is nothing to keep in sync when settings change.
     *
     * @return string[] "HH:MM" start times
     */
    public static function availableSlots(int $organizationId, array $bookingSettings, string $date): array
    {
        $dayOfWeek = (string) date('N', strtotime($date)); // 1 (Mon) .. 7 (Sun)
        $window = $bookingSettings['weekly_hours'][$dayOfWeek] ?? null;
        if (empty($window['start']) || empty($window['end'])) {
            return [];
        }

        $duration = (int) $bookingSettings['slot_duration_minutes'];
        $buffer = (int) $bookingSettings['buffer_minutes'];
        $step = $duration + $buffer;

        $dayStart = strtotime($date . ' ' . $window['start']);
        $dayEnd = strtotime($date . ' ' . $window['end']);
        $earliest = time() + (int) $bookingSettings['min_notice_hours'] * 3600;
        $latest = strtotime('+' . (int) $bookingSettings['max_advance_days'] . ' days');

        if ($dayStart === false || $dayEnd === false || $dayStart >= $latest || $dayEnd <= $earliest) {
            return [];
        }

        $stmt = Database::connection()->prepare(
            "SELECT starts_at, ends_at FROM appointments WHERE organization_id = ? AND status = 'confirmed' AND starts_at < ? AND ends_at > ?"
        );
        $stmt->execute([$organizationId, $date . ' 23:59:59', $date . ' 00:00:00']);
        $booked = $stmt->fetchAll();

        $slots = [];
        for ($start = $dayStart; $start + $duration * 60 <= $dayEnd; $start += $step * 60) {
            if ($start < $earliest) {
                continue;
            }
            $end = $start + $duration * 60;
            $overlaps = false;
            foreach ($booked as $b) {
                if ($start < strtotime($b['ends_at']) && $end > strtotime($b['starts_at'])) {
                    $overlaps = true;
                    break;
                }
            }
            if (!$overlaps) {
                $slots[] = date('H:i', $start);
            }
        }

        return $slots;
    }

    /** Public cancel link (see PublicBookingController) has no Auth session, so the token row is the sole scope. */
    public static function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM appointments WHERE cancel_token = ? LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }
}
