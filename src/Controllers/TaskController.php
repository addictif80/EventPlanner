<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Models\Task;

class TaskController
{
    public static function store(string $eventId): void
    {
        Csrf::verifyOrFail();
        Task::create([
            'event_id' => (int) $eventId,
            'title' => input('title', ''),
            'description' => input('description', ''),
            'due_date' => input('due_date') !== '' ? input('due_date') : null,
            'assigned_to' => input('assigned_to') !== '' ? (int) input('assigned_to') : null,
            'status' => 'todo',
            'priority' => input('priority', 'normal'),
        ]);
        Session::flash('success', 'Tâche ajoutée.');
        redirect('/events/' . $eventId);
    }

    public static function updateStatus(string $id): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('SELECT event_id FROM tasks WHERE id = ? AND organization_id = ?');
        $stmt->execute([$id, Auth::organizationId()]);
        $eventId = $stmt->fetchColumn();

        Task::update((int) $id, ['status' => input('status', 'todo')]);
        redirect('/events/' . $eventId);
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        $stmt = Database::connection()->prepare('SELECT event_id FROM tasks WHERE id = ? AND organization_id = ?');
        $stmt->execute([$id, Auth::organizationId()]);
        $eventId = $stmt->fetchColumn();

        Task::delete((int) $id);
        redirect('/events/' . $eventId);
    }
}
