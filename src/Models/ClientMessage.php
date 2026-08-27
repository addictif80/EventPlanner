<?php

namespace App\Models;

use App\Core\Database;

class ClientMessage
{
    public static function forClient(int $organizationId, int $clientId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, u.name AS user_name FROM client_messages m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.organization_id = ? AND m.client_id = ?
             ORDER BY m.created_at ASC, m.id ASC'
        );
        $stmt->execute([$organizationId, $clientId]);
        return $stmt->fetchAll();
    }

    public static function send(int $organizationId, int $clientId, string $senderType, ?int $userId, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO client_messages (organization_id, client_id, sender_type, user_id, body) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$organizationId, $clientId, $senderType, $userId, $body]);
        return (int) Database::connection()->lastInsertId();
    }
}
