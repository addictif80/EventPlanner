<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class SupportTicketMessage extends Model
{
    protected static string $table = 'support_ticket_messages';
    protected static bool $scoped = false;

    public static function forTicket(int $ticketId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT support_ticket_messages.*, users.name AS user_name
             FROM support_ticket_messages
             LEFT JOIN users ON users.id = support_ticket_messages.user_id
             WHERE ticket_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }
}
