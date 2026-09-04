<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\Appointment;
use App\Models\BookingSettings;

/**
 * Public, unauthenticated booking flow: a prospect picks a free slot on the
 * organization's page and confirms it themselves — no email back-and-forth
 * with staff to land a first appointment. Same token/slug pattern as
 * PortalController — there is no Auth session here, so every query is
 * scoped manually via the row already resolved from the public identifier.
 */
class PublicBookingController
{
    public static function show(string $slug): void
    {
        $settings = BookingSettings::findBySlug($slug);
        if (!$settings) { http_response_code(404); die('Page de réservation introuvable.'); }

        $companyStmt = Database::connection()->prepare('SELECT * FROM company_settings WHERE organization_id = ?');
        $companyStmt->execute([$settings['organization_id']]);

        View::render('booking/public_show', [
            'settings' => $settings,
            'company' => $companyStmt->fetch() ?: [],
            'slug' => $slug,
        ], layout: null);
    }

    public static function slotsJson(string $slug): void
    {
        $settings = BookingSettings::findBySlug($slug);
        header('Content-Type: application/json');
        if (!$settings) { http_response_code(404); echo json_encode(['error' => 'not_found']); return; }

        $date = $_GET['date'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['slots' => []]);
            return;
        }

        echo json_encode(['slots' => Appointment::availableSlots((int) $settings['organization_id'], $settings, $date)]);
    }

    public static function store(string $slug): void
    {
        Csrf::verifyOrFail();
        $settings = BookingSettings::findBySlug($slug);
        if (!$settings) { http_response_code(404); die('Page de réservation introuvable.'); }

        $date = input('date', '');
        $time = input('time', '');
        $name = trim(input('name', ''));
        $email = trim(input('email', ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time) || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Merci de renseigner un créneau, votre nom et un email valide.');
            redirect('/booking/' . $slug);
        }

        $available = Appointment::availableSlots((int) $settings['organization_id'], $settings, $date);
        if (!in_array($time, $available, true)) {
            Session::flash('error', "Ce créneau n'est plus disponible. Merci d'en choisir un autre.");
            redirect('/booking/' . $slug);
        }

        $orgId = (int) $settings['organization_id'];
        $startsAt = $date . ' ' . $time . ':00';
        $endsAt = date('Y-m-d H:i:s', strtotime($startsAt) + (int) $settings['slot_duration_minutes'] * 60);

        $clientStmt = Database::connection()->prepare('SELECT id FROM clients WHERE organization_id = ? AND email = ? LIMIT 1');
        $clientStmt->execute([$orgId, $email]);
        $clientId = $clientStmt->fetchColumn() ?: null;

        $token = bin2hex(random_bytes(32));
        $stmt = Database::connection()->prepare(
            'INSERT INTO appointments (organization_id, client_id, prospect_name, prospect_email, prospect_phone, subject, starts_at, ends_at, status, notes, cancel_token)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "confirmed", ?, ?)'
        );
        $stmt->execute([$orgId, $clientId, $name, $email, input('phone', ''), input('subject', ''), $startsAt, $endsAt, input('notes', ''), $token]);
        $appointmentId = (int) Database::connection()->lastInsertId();

        \App\Models\Notification::toOrganization(
            $orgId,
            'appointment',
            'Nouveau rendez-vous réservé',
            $name . ' a réservé un rendez-vous le ' . date('d/m/Y à H:i', strtotime($startsAt)) . '.',
            '/appointments'
        );

        self::sendConfirmationEmail($orgId, $settings, $name, $email, $startsAt, $token);

        View::render('booking/public_confirmed', [
            'settings' => $settings,
            'startsAt' => $startsAt,
            'token' => $token,
        ], layout: null);
    }

    public static function cancel(string $token): void
    {
        $appointment = Appointment::findByToken($token);
        if (!$appointment) { http_response_code(404); die('Lien invalide.'); }

        if ($appointment['status'] === 'confirmed') {
            Database::connection()->prepare('UPDATE appointments SET status = "cancelled" WHERE id = ?')->execute([$appointment['id']]);
        }

        View::render('booking/public_cancelled', ['appointment' => $appointment], layout: null);
    }

    private static function sendConfirmationEmail(int $orgId, array $settings, string $name, string $email, string $startsAt, string $token): void
    {
        $previousOrgId = $_SESSION['organization_id'] ?? null;
        $_SESSION['organization_id'] = $orgId;

        $cancelUrl = full_url('/booking/cancel/' . $token);
        $html = '<p>Bonjour ' . View::e($name) . ',</p>'
            . '<p>Votre rendez-vous est confirmé pour le <strong>' . View::e(date('d/m/Y à H:i', strtotime($startsAt))) . '</strong> (' . View::e($settings['location_type']) . ').</p>'
            . (!empty($settings['meeting_instructions']) ? '<p>' . nl2br(View::e($settings['meeting_instructions'])) . '</p>' : '')
            . '<p style="margin:20px 0;"><a href="' . View::e($cancelUrl) . '">Annuler ce rendez-vous</a></p>';

        try {
            Mailer::send($email, 'Confirmation de votre rendez-vous', $html);
        } catch (\RuntimeException $e) {
            // Best-effort: the appointment is already recorded either way; staff sees it via the in-app notification above.
        }
    }
}
