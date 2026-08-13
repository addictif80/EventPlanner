<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;

class NoteController
{
    public static function storeForEvent(string $eventId): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('INSERT INTO event_notes (event_id, user_id, body) VALUES (?, ?, ?)');
        $stmt->execute([$eventId, Auth::id(), input('body', '')]);
        redirect('/events/' . $eventId);
    }

    public static function storeForClient(string $clientId): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('INSERT INTO event_notes (client_id, user_id, body) VALUES (?, ?, ?)');
        $stmt->execute([$clientId, Auth::id(), input('body', '')]);
        redirect('/clients/' . $clientId);
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('SELECT event_id, client_id FROM event_notes WHERE id = ?');
        $stmt->execute([$id]);
        $note = $stmt->fetch();

        Database::connection()->prepare('DELETE FROM event_notes WHERE id = ?')->execute([$id]);

        Session::flash('success', 'Note supprimée.');
        if ($note && $note['event_id']) {
            redirect('/events/' . $note['event_id']);
        }
        redirect('/clients/' . ($note['client_id'] ?? ''));
    }
}
