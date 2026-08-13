<?php

namespace App\Controllers;

use App\Core\Database;
use App\Models\CompanySettings;

class CalendarController
{
    public static function feed(string $token): void
    {
        $company = CompanySettings::get();
        if (empty($company['ics_feed_token']) || !hash_equals($company['ics_feed_token'], $token)) {
            http_response_code(404);
            die('Flux invalide.');
        }

        $events = Database::connection()->query(
            "SELECT e.*, COALESCE(NULLIF(c.company_name, ''), CONCAT(c.first_name, ' ', c.last_name)) AS client_name
             FROM events e LEFT JOIN clients c ON c.id = e.client_id
             WHERE e.status != 'cancelled'"
        )->fetchAll();

        header('Content-Type: text/calendar; charset=UTF-8');
        header('Content-Disposition: inline; filename="eventplanner.ics"');

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//EventPlanner//FR\r\n";
        echo "CALSCALE:GREGORIAN\r\n";

        foreach ($events as $e) {
            $start = str_replace('-', '', $e['event_date']);
            $end = str_replace('-', '', $e['end_date'] ?: $e['event_date']);
            $end = date('Ymd', strtotime($end . ' +1 day'));

            echo "BEGIN:VEVENT\r\n";
            echo 'UID:event-' . $e['id'] . "@eventplanner\r\n";
            echo 'DTSTAMP:' . gmdate('Ymd\THis\Z') . "\r\n";
            echo 'DTSTART;VALUE=DATE:' . $start . "\r\n";
            echo 'DTEND;VALUE=DATE:' . $end . "\r\n";
            echo 'SUMMARY:' . self::escape($e['title'] . ' — ' . $e['client_name']) . "\r\n";
            if (!empty($e['location'])) {
                echo 'LOCATION:' . self::escape($e['location']) . "\r\n";
            }
            echo 'DESCRIPTION:' . self::escape($e['description'] ?? '') . "\r\n";
            echo "END:VEVENT\r\n";
        }

        echo "END:VCALENDAR\r\n";
        exit;
    }

    private static function escape(string $text): string
    {
        return str_replace(["\\", ",", ";", "\n"], ["\\\\", "\\,", "\\;", "\\n"], $text);
    }
}
