<?php

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Client;
use App\Models\Document;

class ClientController
{
    public static function index(): void
    {
        $term = trim($_GET['q'] ?? '');
        $clients = $term !== '' ? Client::search($term) : Client::all('created_at DESC');
        View::render('clients/index', ['title' => 'Clients', 'clients' => $clients, 'term' => $term]);
    }

    public static function create(): void
    {
        View::render('clients/form', ['title' => 'Nouveau client', 'client' => null]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        $data = self::validated();
        $id = Client::create($data);
        ActivityLog::record('Création client', 'client', $id, $data['company_name'] ?: trim($data['first_name'] . ' ' . $data['last_name']));
        Session::flash('success', 'Client créé avec succès.');
        redirect('/clients/' . $id);
    }

    public static function show(string $id): void
    {
        $client = Client::find((int) $id);
        if (!$client) {
            http_response_code(404);
            die('Client introuvable.');
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM events WHERE client_id = ? ORDER BY event_date DESC');
        $stmt->execute([$id]);
        $events = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM quotes WHERE client_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$id]);
        $quotes = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE client_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$id]);
        $invoices = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT n.*, u.name AS author_name FROM event_notes n LEFT JOIN users u ON u.id = n.user_id WHERE n.client_id = ? ORDER BY n.created_at DESC');
        $stmt->execute([$id]);
        $notes = $stmt->fetchAll();

        View::render('clients/show', [
            'title' => Client::displayName($client),
            'client' => $client,
            'events' => $events,
            'quotes' => $quotes,
            'invoices' => $invoices,
            'documents' => Document::forClient((int) $id),
            'notes' => $notes,
            'portalLink' => $_SESSION['portal_link_' . $id] ?? null,
        ]);
        unset($_SESSION['portal_link_' . $id]);
    }

    public static function edit(string $id): void
    {
        $client = Client::find((int) $id);
        if (!$client) {
            http_response_code(404);
            die('Client introuvable.');
        }
        View::render('clients/form', ['title' => 'Modifier le client', 'client' => $client]);
    }

    public static function update(string $id): void
    {
        Csrf::verifyOrFail();
        $data = self::validated();
        Client::update((int) $id, $data);
        ActivityLog::record('Modification client', 'client', (int) $id);
        Session::flash('success', 'Client mis à jour.');
        redirect('/clients/' . $id);
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Client::delete((int) $id);
        ActivityLog::record('Suppression client', 'client', (int) $id);
        Session::flash('success', 'Client supprimé.');
        redirect('/clients');
    }

    private static function validated(): array
    {
        return [
            'type' => in_array(input('type'), ['particulier', 'entreprise'], true) ? input('type') : 'particulier',
            'first_name' => input('first_name', ''),
            'last_name' => input('last_name', ''),
            'company_name' => input('company_name', ''),
            'email' => input('email', ''),
            'phone' => input('phone', ''),
            'address' => input('address', ''),
            'postal_code' => input('postal_code', ''),
            'city' => input('city', ''),
            'country' => input('country', 'France'),
            'tags' => input('tags', ''),
            'notes' => input('notes', ''),
        ];
    }
}
