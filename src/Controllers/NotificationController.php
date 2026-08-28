<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Push;
use App\Models\Notification;

/**
 * In-app notification feed + Web Push subscription endpoints, one set of
 * methods per audience (staff user, super admin, portal client) so each role
 * only ever touches its own rows — see Notification model for the same
 * separation at the query level.
 */
class NotificationController
{
    // --- Staff (organization users) ---

    public static function userFeed(): void
    {
        Auth::requireLogin();
        self::respondFeed(Notification::forUser(Auth::id()), Notification::unreadCountForUser(Auth::id()));
    }

    public static function userMarkRead(string $id): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        Notification::markRead((int) $id);
        redirect($_POST['redirect'] ?? '/');
    }

    public static function userMarkAllRead(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        Notification::markAllReadForUser(Auth::id());
        redirect($_POST['redirect'] ?? '/');
    }

    public static function userSubscribe(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        self::storeSubscription(Auth::id(), null, false);
    }

    public static function userUnsubscribe(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        Push::unsubscribe(input('endpoint', ''));
        http_response_code(204);
    }

    // --- Platform super admins ---

    public static function platformFeed(): void
    {
        Auth::requireSuperAdmin();
        self::respondFeed(Notification::forPlatform(), Notification::unreadCountForPlatform());
    }

    public static function platformMarkRead(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        Notification::markRead((int) $id);
        redirect($_POST['redirect'] ?? '/admin');
    }

    public static function platformMarkAllRead(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        Notification::markAllReadForPlatform();
        redirect($_POST['redirect'] ?? '/admin');
    }

    public static function platformSubscribe(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::storeSubscription(null, null, true);
    }

    // --- Portal clients (token-based, no Auth session) ---

    public static function portalFeed(string $token): void
    {
        $client = self::resolvePortalClient($token);
        if (!$client) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
            return;
        }
        self::respondFeed(Notification::forClient($client['id']), Notification::unreadCountForClient($client['id']));
    }

    public static function portalMarkRead(string $token, string $id): void
    {
        if (!self::resolvePortalClient($token)) {
            http_response_code(404);
            die('Lien invalide ou expiré.');
        }
        Csrf::verifyOrFail();
        Notification::markRead((int) $id);
        redirect('/portal/' . $token);
    }

    public static function portalMarkAllRead(string $token): void
    {
        $client = self::resolvePortalClient($token);
        if (!$client) {
            http_response_code(404);
            die('Lien invalide ou expiré.');
        }
        Csrf::verifyOrFail();
        Notification::markAllReadForClient($client['id']);
        redirect('/portal/' . $token);
    }

    public static function portalSubscribe(string $token): void
    {
        $client = self::resolvePortalClient($token);
        if (!$client) {
            http_response_code(404);
            die('Lien invalide ou expiré.');
        }
        Csrf::verifyOrFail();
        self::storeSubscription(null, $client['id'], false);
    }

    // --- Shared helpers ---

    public static function vapidPublicKey(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['key' => Push::vapidKeys()['public']]);
    }

    private static function storeSubscription(?int $userId, ?int $clientId, bool $isPlatform): void
    {
        $endpoint = input('endpoint', '');
        $p256dh = input('p256dh', '');
        $auth = input('auth', '');

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            http_response_code(400);
            return;
        }

        Push::subscribe($userId, $clientId, $isPlatform, $endpoint, $p256dh, $auth);
        http_response_code(204);
    }

    private static function respondFeed(array $items, int $unreadCount): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'unread' => $unreadCount,
            'items' => array_map(static fn($n) => [
                'id' => (int) $n['id'],
                'type' => $n['type'],
                'title' => $n['title'],
                'message' => $n['message'],
                'link' => $n['link'],
                'is_read' => (bool) $n['is_read'],
                'created_at' => $n['created_at'],
            ], $items),
        ]);
    }

    /** @return array{id:int}|null */
    private static function resolvePortalClient(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT client_id FROM client_portal_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ? ['id' => (int) $row['client_id']] : null;
    }
}
