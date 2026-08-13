<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Client;

class PortalController
{
    public static function generateLink(string $clientId): void
    {
        Csrf::verifyOrFail();

        $token = bin2hex(random_bytes(24));
        $stmt = Database::connection()->prepare(
            'INSERT INTO client_portal_tokens (client_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))'
        );
        $stmt->execute([$clientId, $token]);

        $link = (($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('/portal/' . $token));
        Session::flash('success', 'Lien portail client généré.');
        $_SESSION['portal_link_' . $clientId] = $link;
        redirect('/clients/' . $clientId);
    }

    public static function show(string $token): void
    {
        $stmt = Database::connection()->prepare('SELECT * FROM client_portal_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $portalToken = $stmt->fetch();

        if (!$portalToken) { http_response_code(404); die('Lien invalide ou expiré.'); }

        $client = Client::find((int) $portalToken['client_id']);
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM events WHERE client_id = ? ORDER BY event_date DESC');
        $stmt->execute([$client['id']]);
        $events = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM quotes WHERE client_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$client['id']]);
        $quotes = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE client_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$client['id']]);
        $invoices = $stmt->fetchAll();

        View::render('portal/show', [
            'client' => $client,
            'events' => $events,
            'quotes' => $quotes,
            'invoices' => $invoices,
        ], layout: null);
    }
}
