<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Push;

/**
 * Central hub for in-app + push notifications, scoped to exactly one of
 * three audiences per row (see the `audience` column): a single staff user,
 * a single client (surfaced in their portal), or the platform's super
 * admins. Each audience is queried independently everywhere in the app —
 * nothing here ever lets one role see another's notifications.
 */
class Notification
{
    public static function toUser(int $userId, int $organizationId, string $type, string $title, string $message, string $link = ''): int
    {
        $id = self::insert(['organization_id' => $organizationId, 'user_id' => $userId, 'audience' => 'user'], $type, $title, $message, $link);
        Push::sendToUser($userId, $title, $message, $link);
        return $id;
    }

    /** @param string[]|null $onlyRoles Restrict to these roles (e.g. ['admin']); null = every active member. */
    public static function toOrganization(int $organizationId, string $type, string $title, string $message, string $link = '', ?array $onlyRoles = null, ?int $excludeUserId = null): void
    {
        $sql = 'SELECT id FROM users WHERE organization_id = ? AND is_active = 1';
        $params = [$organizationId];
        if ($onlyRoles !== null && $onlyRoles !== []) {
            $sql .= ' AND role IN (' . implode(',', array_fill(0, count($onlyRoles), '?')) . ')';
            $params = array_merge($params, $onlyRoles);
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $row) {
            if ($excludeUserId !== null && (int) $row['id'] === $excludeUserId) {
                continue;
            }
            self::toUser((int) $row['id'], $organizationId, $type, $title, $message, $link);
        }
    }

    public static function toClient(int $clientId, int $organizationId, string $type, string $title, string $message, string $link = ''): int
    {
        $id = self::insert(['organization_id' => $organizationId, 'client_id' => $clientId, 'audience' => 'client'], $type, $title, $message, $link);
        Push::sendToClient($clientId, $title, $message, $link);
        return $id;
    }

    public static function toPlatform(string $type, string $title, string $message, string $link = ''): void
    {
        self::insert(['audience' => 'platform'], $type, $title, $message, $link);
        Push::sendToPlatform($title, $message, $link);
    }

    private static function insert(array $scope, string $type, string $title, string $message, string $link): int
    {
        $data = array_merge([
            'organization_id' => null,
            'user_id' => null,
            'client_id' => null,
        ], $scope, [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);

        $stmt = Database::connection()->prepare(
            'INSERT INTO notifications (organization_id, user_id, client_id, audience, type, title, message, link)
             VALUES (:organization_id, :user_id, :client_id, :audience, :type, :title, :message, :link)'
        );
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public static function forUser(int $userId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function unreadCountForUser(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function forClient(int $clientId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM notifications WHERE client_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public static function unreadCountForClient(int $clientId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE client_id = ? AND is_read = 0');
        $stmt->execute([$clientId]);
        return (int) $stmt->fetchColumn();
    }

    public static function forPlatform(int $limit = 20): array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM notifications WHERE audience = 'platform' ORDER BY created_at DESC LIMIT " . (int) $limit);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function unreadCountForPlatform(): int
    {
        $stmt = Database::connection()->query("SELECT COUNT(*) FROM notifications WHERE audience = 'platform' AND is_read = 0");
        return (int) $stmt->fetchColumn();
    }

    public static function markRead(int $id): void
    {
        Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$id]);
    }

    public static function markAllReadForUser(int $userId): void
    {
        Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$userId]);
    }

    public static function markAllReadForClient(int $clientId): void
    {
        Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE client_id = ? AND is_read = 0')->execute([$clientId]);
    }

    public static function markAllReadForPlatform(): void
    {
        Database::connection()->exec("UPDATE notifications SET is_read = 1 WHERE audience = 'platform' AND is_read = 0");
    }
}
