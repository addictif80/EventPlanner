<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\ModuleAccess;
use App\Core\Session;
use App\Core\View;
use App\Models\Client;

class PortalController
{
    public static function generateLink(string $clientId): void
    {
        ModuleAccess::requireModule('client_portal');
        Csrf::verifyOrFail();

        if (!Client::find((int) $clientId)) {
            http_response_code(404);
            die('Client introuvable.');
        }

        $token = bin2hex(random_bytes(24));
        $stmt = Database::connection()->prepare(
            'INSERT INTO client_portal_tokens (organization_id, client_id, token, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))'
        );
        $stmt->execute([Auth::organizationId(), $clientId, $token]);

        $link = (($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('/portal/' . $token));
        Session::flash('success', 'Lien portail client généré.');
        $_SESSION['portal_link_' . $clientId] = $link;
        redirect('/clients/' . $clientId);
    }

    public static function show(string $token): void
    {
        // Public, unauthenticated route: there is no Auth session here, so we
        // cannot rely on the tenant-scoped models. The token row itself carries
        // the organization_id, which becomes the sole scope for every query below.
        $stmt = Database::connection()->prepare('SELECT * FROM client_portal_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $portalToken = $stmt->fetch();

        if (!$portalToken) { http_response_code(404); die('Lien invalide ou expiré.'); }

        $orgId = $portalToken['organization_id'];
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$portalToken['client_id'], $orgId]);
        $client = $stmt->fetch();

        if (!$client) { http_response_code(404); die('Lien invalide ou expiré.'); }

        $stmt = $pdo->prepare('SELECT * FROM events WHERE client_id = ? AND organization_id = ? ORDER BY event_date DESC');
        $stmt->execute([$client['id'], $orgId]);
        $events = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM quotes WHERE client_id = ? AND organization_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$client['id'], $orgId]);
        $quotes = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE client_id = ? AND organization_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$client['id'], $orgId]);
        $invoices = $stmt->fetchAll();

        View::render('portal/show', [
            'client' => $client,
            'events' => $events,
            'quotes' => $quotes,
            'invoices' => $invoices,
            'token' => $token,
            'stripeAvailable' => \App\Core\ModuleAccess::has('stripe_payments', $orgId),
        ], layout: null);
    }

    public static function acceptQuote(string $token, string $quoteId): void
    {
        self::updateQuoteStatus($token, $quoteId, 'accepted');
    }

    public static function refuseQuote(string $token, string $quoteId): void
    {
        self::updateQuoteStatus($token, $quoteId, 'refused');
    }

    private static function updateQuoteStatus(string $token, string $quoteId, string $status): void
    {
        Csrf::verifyOrFail();

        $client = self::resolveClient($token);
        if (!$client) {
            http_response_code(404);
            die('Lien invalide ou expiré.');
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT id, status FROM quotes WHERE id = ? AND client_id = ? AND organization_id = ? LIMIT 1");
        $stmt->execute([$quoteId, $client['id'], $client['organization_id']]);
        $quote = $stmt->fetch();

        if ($quote && $quote['status'] === 'sent') {
            $pdo->prepare('UPDATE quotes SET status = ? WHERE id = ? AND organization_id = ?')
                ->execute([$status, $quoteId, $client['organization_id']]);
        }

        redirect('/portal/' . $token);
    }

    /** JSON export of everything the portal itself shows for this client — their own data, portability-style. */
    public static function exportData(string $token): void
    {
        $client = self::resolveClient($token);
        if (!$client) {
            http_response_code(404);
            die('Lien invalide ou expiré.');
        }

        $pdo = Database::connection();
        $orgId = $client['organization_id'];

        $stmt = $pdo->prepare('SELECT * FROM events WHERE client_id = ? AND organization_id = ? ORDER BY event_date DESC');
        $stmt->execute([$client['id'], $orgId]);
        $events = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM quotes WHERE client_id = ? AND organization_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$client['id'], $orgId]);
        $quotes = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE client_id = ? AND organization_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$client['id'], $orgId]);
        $invoices = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT sender_type, body, created_at FROM client_messages WHERE client_id = ? AND organization_id = ? ORDER BY created_at ASC');
        $stmt->execute([$client['id'], $orgId]);
        $messages = $stmt->fetchAll();

        unset($client['organization_id']);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="mes-donnees.json"');
        echo json_encode([
            'profil' => $client,
            'evenements' => $events,
            'devis' => $quotes,
            'factures' => $invoices,
            'messages' => $messages,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * The client can only *request* erasure, not trigger it directly: their
     * invoices may be subject to the organizer's own legal accounting
     * retention duty, so a human at the organization must review and action
     * the request (see the banner on the client's staff-side detail page).
     */
    public static function requestErasure(string $token): void
    {
        Csrf::verifyOrFail();

        $client = self::resolveClient($token);
        if (!$client) {
            http_response_code(404);
            die('Lien invalide ou expiré.');
        }

        Database::connection()
            ->prepare('UPDATE clients SET deletion_requested_at = NOW() WHERE id = ? AND organization_id = ?')
            ->execute([$client['id'], $client['organization_id']]);

        View::render('portal/erasure_requested', [], layout: null);
    }

    /** @return array{id:int,organization_id:int}|null */
    private static function resolveClient(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM client_portal_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $portalToken = $stmt->fetch();
        if (!$portalToken) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM clients WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$portalToken['client_id'], $portalToken['organization_id']]);
        $client = $stmt->fetch();

        return $client ?: null;
    }
}
