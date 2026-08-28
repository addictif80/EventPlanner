<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Models\Client;
use App\Models\ClientMessage;

/**
 * Messaging thread between an organization's staff and one of its clients.
 * The staff side is reached from the authenticated app (Auth-scoped); the
 * client side is reached through the same unauthenticated portal token used
 * by PortalController — there is no client login, so the token is the only
 * proof of identity. Polling (not WebSockets) keeps this simple: both sides
 * just re-fetch the JSON thread every few seconds.
 */
class ClientMessageController
{
    public static function staffPoll(string $clientId): void
    {
        Auth::requireLogin();
        $client = Client::find((int) $clientId);
        if (!$client) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
            return;
        }

        self::respondJson(ClientMessage::forClient(Auth::organizationId(), (int) $clientId), Client::displayName($client));
    }

    public static function staffSend(string $clientId): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();

        $client = Client::find((int) $clientId);
        if (!$client) {
            http_response_code(404);
            die('Client introuvable.');
        }

        $body = trim(input('body', ''));
        if ($body !== '') {
            ClientMessage::send(Auth::organizationId(), (int) $clientId, 'staff', Auth::id(), $body);
            \App\Models\Notification::toClient((int) $clientId, Auth::organizationId(), 'message', 'Nouveau message', $body);
        }

        redirect('/clients/' . $clientId . '#messages');
    }

    public static function portalPoll(string $token): void
    {
        $portalClient = self::resolvePortalClient($token);
        if (!$portalClient) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
            return;
        }

        self::respondJson(ClientMessage::forClient($portalClient['organization_id'], $portalClient['id']), 'Vous');
    }

    public static function portalSend(string $token): void
    {
        $portalClient = self::resolvePortalClient($token);
        if (!$portalClient) {
            http_response_code(404);
            die('Lien invalide ou expiré.');
        }
        Csrf::verifyOrFail();

        $body = trim(input('body', ''));
        if ($body !== '') {
            ClientMessage::send($portalClient['organization_id'], $portalClient['id'], 'client', null, $body);
            $stmt = Database::connection()->prepare('SELECT * FROM clients WHERE id = ? AND organization_id = ? LIMIT 1');
            $stmt->execute([$portalClient['id'], $portalClient['organization_id']]);
            $client = $stmt->fetch();
            \App\Models\Notification::toOrganization(
                $portalClient['organization_id'],
                'message',
                'Nouveau message client',
                ($client ? Client::displayName($client) : 'Un client') . ' : ' . $body,
                '/clients/' . $portalClient['id'] . '#messages'
            );
        }

        redirect('/portal/' . $token . '#messages');
    }

    /** $clientLabel is how a message sent by the client should be labelled for the current viewer (their own name for staff, "Vous" for the client themselves in the portal). */
    private static function respondJson(array $messages, string $clientLabel): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_map(static function ($m) use ($clientLabel) {
            return [
                'id' => (int) $m['id'],
                'sender_type' => $m['sender_type'],
                'author' => $m['sender_type'] === 'staff' ? ($m['user_name'] ?? 'Organisateur') : $clientLabel,
                'body' => $m['body'],
                'created_at' => $m['created_at'],
            ];
        }, $messages));
    }

    /** @return array{organization_id:int,id:int}|null */
    private static function resolvePortalClient(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM client_portal_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $portalToken = $stmt->fetch();
        if (!$portalToken) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT id, organization_id FROM clients WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$portalToken['client_id'], $portalToken['organization_id']]);
        $client = $stmt->fetch();

        return $client ?: null;
    }
}
