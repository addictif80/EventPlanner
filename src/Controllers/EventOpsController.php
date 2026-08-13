<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Event;

class EventOpsController
{
    public static function index(string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM run_sheet_items WHERE event_id = ? ORDER BY time_slot ASC, position ASC');
        $stmt->execute([$eventId]);
        $runSheet = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM emergency_contacts WHERE event_id = ? ORDER BY id ASC');
        $stmt->execute([$eventId]);
        $emergencyContacts = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT i.*, u.name AS reporter_name FROM incidents i LEFT JOIN users u ON u.id = i.reported_by WHERE i.event_id = ? ORDER BY i.created_at DESC');
        $stmt->execute([$eventId]);
        $incidents = $stmt->fetchAll();

        View::render('event_ops/index', [
            'title' => 'Jour-J — ' . $event['title'],
            'event' => $event,
            'runSheet' => $runSheet,
            'emergencyContacts' => $emergencyContacts,
            'incidents' => $incidents,
        ]);
    }

    public static function storeRunSheetItem(string $eventId): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare(
            'INSERT INTO run_sheet_items (event_id, time_slot, title, responsible, notes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$eventId, input('time_slot') ?: null, input('title', ''), input('responsible', ''), input('notes', '')]);
        Session::flash('success', 'Étape ajoutée à la feuille de route.');
        redirect('/events/' . $eventId . '/dayof');
    }

    public static function destroyRunSheetItem(string $eventId, string $itemId): void
    {
        Csrf::verifyOrFail();
        Database::connection()->prepare('DELETE FROM run_sheet_items WHERE id = ? AND event_id = ?')->execute([$itemId, $eventId]);
        redirect('/events/' . $eventId . '/dayof');
    }

    public static function storeEmergencyContact(string $eventId): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare(
            'INSERT INTO emergency_contacts (event_id, name, role, phone, notes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$eventId, input('name', ''), input('role', ''), input('phone', ''), input('notes', '')]);
        Session::flash('success', 'Contact d\'urgence ajouté.');
        redirect('/events/' . $eventId . '/dayof');
    }

    public static function destroyEmergencyContact(string $eventId, string $contactId): void
    {
        Csrf::verifyOrFail();
        Database::connection()->prepare('DELETE FROM emergency_contacts WHERE id = ? AND event_id = ?')->execute([$contactId, $eventId]);
        redirect('/events/' . $eventId . '/dayof');
    }

    public static function storeIncident(string $eventId): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare(
            'INSERT INTO incidents (event_id, title, description, severity, status, reported_by) VALUES (?, ?, ?, ?, "open", ?)'
        );
        $stmt->execute([
            $eventId,
            input('title', ''),
            input('description', ''),
            in_array(input('severity'), ['low', 'medium', 'high'], true) ? input('severity') : 'medium',
            Auth::id(),
        ]);
        Session::flash('success', 'Incident signalé.');
        redirect('/events/' . $eventId . '/dayof');
    }

    public static function updateIncidentStatus(string $eventId, string $incidentId): void
    {
        Csrf::verifyOrFail();
        $status = in_array(input('status'), ['open', 'resolved'], true) ? input('status') : 'open';
        Database::connection()->prepare('UPDATE incidents SET status = ? WHERE id = ? AND event_id = ?')->execute([$status, $incidentId, $eventId]);
        redirect('/events/' . $eventId . '/dayof');
    }
}
