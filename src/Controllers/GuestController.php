<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ModuleAccess;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\Event;
use App\Models\EventTable;
use App\Models\Guest;

class GuestController
{
    public static function index(string $eventId): void
    {
        ModuleAccess::requireModule('guests');
        $event = Event::find((int) $eventId);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        View::render('guests/index', [
            'title' => 'Invités — ' . $event['title'],
            'event' => $event,
            'guests' => Guest::forEvent((int) $eventId),
            'tables' => EventTable::forEvent((int) $eventId),
        ]);
    }

    public static function store(string $eventId): void
    {
        ModuleAccess::requireModule('guests');
        Csrf::verifyOrFail();
        if (!Event::find((int) $eventId)) { http_response_code(404); die('Événement introuvable.'); }
        Guest::create([
            'event_id' => (int) $eventId,
            'first_name' => input('first_name', ''),
            'last_name' => input('last_name', ''),
            'email' => input('email', ''),
            'phone' => input('phone', ''),
            'plus_ones' => (int) input('plus_ones', 0),
            'dietary_notes' => input('dietary_notes', ''),
            'notes' => input('notes', ''),
        ]);
        Session::flash('success', 'Invité ajouté.');
        redirect('/events/' . $eventId . '/guests');
    }

    public static function update(string $id): void
    {
        ModuleAccess::requireModule('guests');
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('SELECT event_id FROM guests WHERE id = ? AND organization_id = ?');
        $stmt->execute([$id, Auth::organizationId()]);
        $eventId = $stmt->fetchColumn();

        Guest::update((int) $id, [
            'first_name' => input('first_name', ''),
            'last_name' => input('last_name', ''),
            'email' => input('email', ''),
            'phone' => input('phone', ''),
            'table_id' => input('table_id') !== '' ? (int) input('table_id') : null,
            'rsvp_status' => in_array(input('rsvp_status'), ['pending', 'confirmed', 'declined'], true) ? input('rsvp_status') : 'pending',
            'plus_ones' => (int) input('plus_ones', 0),
            'dietary_notes' => input('dietary_notes', ''),
            'notes' => input('notes', ''),
        ]);
        Session::flash('success', 'Invité mis à jour.');
        redirect('/events/' . $eventId . '/guests');
    }

    public static function destroy(string $id): void
    {
        ModuleAccess::requireModule('guests');
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('SELECT event_id FROM guests WHERE id = ? AND organization_id = ?');
        $stmt->execute([$id, Auth::organizationId()]);
        $eventId = $stmt->fetchColumn();

        Guest::delete((int) $id);
        Session::flash('success', 'Invité supprimé.');
        redirect('/events/' . $eventId . '/guests');
    }

    public static function sendInvite(string $id): void
    {
        ModuleAccess::requireModule('guests');
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('SELECT * FROM guests WHERE id = ? AND organization_id = ?');
        $stmt->execute([$id, Auth::organizationId()]);
        $guest = $stmt->fetch();

        if (!$guest || empty($guest['email'])) {
            Session::flash('error', "Impossible d'envoyer : cet invité n'a pas d'adresse email.");
            redirect('/events/' . ($guest['event_id'] ?? '') . '/guests');
        }

        $token = bin2hex(random_bytes(24));
        Guest::update((int) $id, ['rsvp_token' => $token, 'invited_at' => date('Y-m-d H:i:s')]);

        $event = Event::find((int) $guest['event_id']);
        $link = url('/rsvp/' . $token);
        $fullLink = (rtrim($_SERVER['REQUEST_SCHEME'] ?? 'https', '/') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $link);

        $html = '<p>Bonjour ' . View::e(Guest::displayName($guest)) . ',</p>'
            . '<p>Vous êtes invité(e) à l\'événement <strong>' . View::e($event['title']) . '</strong> le ' . View::date($event['event_date']) . '.</p>'
            . '<p><a href="' . View::e($fullLink) . '">Merci de confirmer votre présence en cliquant ici</a></p>';

        try {
            Mailer::send($guest['email'], 'Invitation — ' . $event['title'], $html);
            Session::flash('success', 'Invitation envoyée à ' . $guest['email']);
        } catch (\RuntimeException $e) {
            Session::flash('error', "Échec de l'envoi : " . $e->getMessage());
        }

        redirect('/events/' . $guest['event_id'] . '/guests');
    }

    public static function rsvpForm(string $token): void
    {
        $guest = Guest::findByToken($token);
        if (!$guest) { http_response_code(404); die('Lien invalide ou expiré.'); }
        View::render('guests/rsvp', ['guest' => $guest], layout: null);
    }

    public static function rsvpSubmit(string $token): void
    {
        $guest = Guest::findByToken($token);
        if (!$guest) { http_response_code(404); die('Lien invalide ou expiré.'); }
        Csrf::verifyOrFail();

        // Public, unauthenticated action: no organization session exists here,
        // so we bypass the tenant-scoped Model::update() and target the row
        // directly — its identity was already established via the RSVP token.
        $stmt = Database::connection()->prepare(
            'UPDATE guests SET rsvp_status = ?, plus_ones = ?, dietary_notes = ?, responded_at = NOW() WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([
            in_array(input('rsvp_status'), ['confirmed', 'declined'], true) ? input('rsvp_status') : 'pending',
            (int) input('plus_ones', 0),
            input('dietary_notes', ''),
            $guest['id'],
            $guest['organization_id'],
        ]);

        View::render('guests/rsvp_thanks', [], layout: null);
    }

    /** Self-service erasure: the guest removes their own RSVP data via their invite link. */
    public static function rsvpDelete(string $token): void
    {
        $guest = Guest::findByToken($token);
        if (!$guest) { http_response_code(404); die('Lien invalide ou expiré.'); }
        Csrf::verifyOrFail();

        Database::connection()
            ->prepare('DELETE FROM guests WHERE id = ? AND organization_id = ?')
            ->execute([$guest['id'], $guest['organization_id']]);

        View::render('guests/rsvp_deleted', [], layout: null);
    }

    public static function storeTable(string $eventId): void
    {
        ModuleAccess::requireModule('guests');
        Csrf::verifyOrFail();
        if (!Event::find((int) $eventId)) { http_response_code(404); die('Événement introuvable.'); }
        EventTable::create([
            'event_id' => (int) $eventId,
            'name' => input('name', ''),
            'capacity' => (int) input('capacity', 8),
            'notes' => input('notes', ''),
        ]);
        Session::flash('success', 'Table ajoutée.');
        redirect('/events/' . $eventId . '/guests');
    }

    public static function destroyTable(string $eventId, string $tableId): void
    {
        ModuleAccess::requireModule('guests');
        Csrf::verifyOrFail();
        Database::connection()->prepare('DELETE FROM event_tables WHERE id = ? AND event_id = ? AND organization_id = ?')
            ->execute([$tableId, $eventId, Auth::organizationId()]);
        Session::flash('success', 'Table supprimée.');
        redirect('/events/' . $eventId . '/guests');
    }
}
