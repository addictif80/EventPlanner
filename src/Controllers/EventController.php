<?php

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Client;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Provider;
use App\Models\Venue;

class EventController
{
    public static function index(): void
    {
        View::render('events/index', ['title' => 'Événements', 'events' => Event::allWithClient()]);
    }

    public static function create(): void
    {
        View::render('events/form', [
            'title' => 'Nouvel événement',
            'event' => null,
            'clients' => Client::all('created_at DESC'),
            'eventTypes' => EventType::all('name ASC'),
            'venues' => Venue::all('name ASC'),
            'selectedClientId' => $_GET['client_id'] ?? null,
        ]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        $data = self::validated();
        $data['created_by'] = \App\Core\Auth::id();
        $id = Event::create($data);
        ActivityLog::record('Création événement', 'event', $id, $data['title']);
        Session::flash('success', 'Événement créé.');
        redirect('/events/' . $id);
    }

    public static function show(string $id): void
    {
        $event = Event::findWithRelations((int) $id);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE event_id = ? AND organization_id = ? ORDER BY (status = "done"), due_date ASC');
        $stmt->execute([$id, $orgId]);
        $tasks = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT ep.*, p.name AS provider_name, p.category FROM event_providers ep JOIN providers p ON p.id = ep.provider_id WHERE ep.event_id = ? AND ep.organization_id = ?');
        $stmt->execute([$id, $orgId]);
        $eventProviders = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM quotes WHERE event_id = ? AND organization_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$id, $orgId]);
        $quotes = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE event_id = ? AND organization_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$id, $orgId]);
        $invoices = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE is_active = 1 AND organization_id = ? ORDER BY name');
        $stmt->execute([$orgId]);
        $users = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT n.*, u.name AS author_name FROM event_notes n LEFT JOIN users u ON u.id = n.user_id WHERE n.event_id = ? AND n.organization_id = ? ORDER BY n.created_at DESC');
        $stmt->execute([$id, $orgId]);
        $notes = $stmt->fetchAll();

        View::render('events/show', [
            'title' => $event['title'],
            'event' => $event,
            'tasks' => $tasks,
            'eventProviders' => $eventProviders,
            'providers' => Provider::all('name ASC'),
            'quotes' => $quotes,
            'invoices' => $invoices,
            'users' => $users,
            'documents' => Document::forEvent((int) $id),
            'equipmentBookings' => Equipment::bookingsForEvent((int) $id),
            'equipmentList' => Equipment::all('name ASC'),
            'notes' => $notes,
        ]);
    }

    public static function edit(string $id): void
    {
        $event = Event::find((int) $id);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        View::render('events/form', [
            'title' => 'Modifier l\'événement',
            'event' => $event,
            'clients' => Client::all('created_at DESC'),
            'eventTypes' => EventType::all('name ASC'),
            'venues' => Venue::all('name ASC'),
            'selectedClientId' => null,
        ]);
    }

    public static function update(string $id): void
    {
        Csrf::verifyOrFail();
        Event::update((int) $id, self::validated());
        ActivityLog::record('Modification événement', 'event', (int) $id);
        Session::flash('success', 'Événement mis à jour.');
        redirect('/events/' . $id);
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Event::delete((int) $id);
        ActivityLog::record('Suppression événement', 'event', (int) $id);
        Session::flash('success', 'Événement supprimé.');
        redirect('/events');
    }

    public static function attachProvider(string $id): void
    {
        Csrf::verifyOrFail();

        if (!Event::find((int) $id) || !Provider::find((int) input('provider_id'))) {
            http_response_code(404);
            die('Introuvable.');
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO event_providers (organization_id, event_id, provider_id, cost, status, notes) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            Auth::organizationId(),
            (int) $id,
            (int) input('provider_id'),
            input('cost') !== '' ? (float) str_replace(',', '.', input('cost')) : null,
            input('status', 'pending'),
            input('notes', ''),
        ]);
        Session::flash('success', 'Prestataire lié à l\'événement.');
        redirect('/events/' . $id);
    }

    public static function detachProvider(string $id, string $providerLinkId): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('DELETE FROM event_providers WHERE id = ? AND event_id = ? AND organization_id = ?');
        $stmt->execute([(int) $providerLinkId, (int) $id, Auth::organizationId()]);
        Session::flash('success', 'Prestataire retiré de l\'événement.');
        redirect('/events/' . $id);
    }

    private static function validated(): array
    {
        return [
            'client_id' => (int) input('client_id'),
            'event_type_id' => input('event_type_id') !== '' ? (int) input('event_type_id') : null,
            'venue_id' => input('venue_id') !== '' ? (int) input('venue_id') : null,
            'title' => input('title', ''),
            'description' => input('description', ''),
            'event_date' => input('event_date'),
            'end_date' => input('end_date') !== '' ? input('end_date') : null,
            'location' => input('location', ''),
            'guests_count' => input('guests_count') !== '' ? (int) input('guests_count') : null,
            'status' => input('status', 'draft'),
            'budget' => input('budget') !== '' ? (float) str_replace(',', '.', input('budget')) : null,
        ];
    }
}
