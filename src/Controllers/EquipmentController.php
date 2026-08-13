<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Equipment;

class EquipmentController
{
    public static function index(): void
    {
        View::render('equipment/index', ['title' => 'Matériel', 'equipment' => Equipment::all('name ASC')]);
    }

    public static function create(): void
    {
        View::render('equipment/form', ['title' => 'Nouvel article', 'item' => null]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        Equipment::create([
            'name' => input('name', ''),
            'category' => input('category', ''),
            'total_quantity' => (int) input('total_quantity', 1),
            'notes' => input('notes', ''),
        ]);
        Session::flash('success', 'Matériel ajouté.');
        redirect('/equipment');
    }

    public static function edit(string $id): void
    {
        $item = Equipment::find((int) $id);
        if (!$item) { http_response_code(404); die('Article introuvable.'); }
        View::render('equipment/form', ['title' => 'Modifier', 'item' => $item]);
    }

    public static function update(string $id): void
    {
        Csrf::verifyOrFail();
        Equipment::update((int) $id, [
            'name' => input('name', ''),
            'category' => input('category', ''),
            'total_quantity' => (int) input('total_quantity', 1),
            'notes' => input('notes', ''),
        ]);
        Session::flash('success', 'Matériel mis à jour.');
        redirect('/equipment');
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Equipment::delete((int) $id);
        Session::flash('success', 'Matériel supprimé.');
        redirect('/equipment');
    }

    public static function book(string $eventId): void
    {
        Csrf::verifyOrFail();

        $stmt = Database::connection()->prepare('SELECT event_date FROM events WHERE id = ? AND organization_id = ?');
        $stmt->execute([$eventId, Auth::organizationId()]);
        $eventDate = $stmt->fetchColumn();

        if (!$eventDate) { http_response_code(404); die('Événement introuvable.'); }

        $equipmentId = (int) input('equipment_id');
        $quantity = (int) input('quantity', 1);

        $equipment = Equipment::find($equipmentId);
        if (!$equipment) {
            http_response_code(404);
            die('Matériel introuvable.');
        }

        $alreadyBooked = Equipment::bookedQuantity($equipmentId, $eventDate);

        if (($alreadyBooked + $quantity) > (int) $equipment['total_quantity']) {
            Session::flash('error', "Stock insuffisant : {$alreadyBooked}/{$equipment['total_quantity']} déjà réservé pour cette date.");
            redirect('/events/' . $eventId);
        }

        $stmt = Database::connection()->prepare('INSERT INTO equipment_bookings (organization_id, equipment_id, event_id, quantity, notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([Auth::organizationId(), $equipmentId, (int) $eventId, $quantity, input('notes', '')]);

        Session::flash('success', 'Matériel réservé pour cet événement.');
        redirect('/events/' . $eventId);
    }

    public static function unbook(string $eventId, string $bookingId): void
    {
        Csrf::verifyOrFail();
        Database::connection()->prepare('DELETE FROM equipment_bookings WHERE id = ? AND event_id = ? AND organization_id = ?')
            ->execute([$bookingId, $eventId, Auth::organizationId()]);
        Session::flash('success', 'Réservation de matériel supprimée.');
        redirect('/events/' . $eventId);
    }
}
