<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketCategory;

class TicketController
{
    public static function index(string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        View::render('tickets/index', [
            'title' => 'Billetterie — ' . $event['title'],
            'event' => $event,
            'categories' => TicketCategory::forEvent((int) $eventId),
            'tickets' => Ticket::forEvent((int) $eventId),
        ]);
    }

    public static function storeCategory(string $eventId): void
    {
        Csrf::verifyOrFail();
        TicketCategory::create([
            'event_id' => (int) $eventId,
            'name' => input('name', ''),
            'price' => (float) str_replace(',', '.', input('price', '0')),
            'quantity_available' => (int) input('quantity_available', 0),
        ]);
        Session::flash('success', 'Catégorie de billet créée.');
        redirect('/events/' . $eventId . '/tickets');
    }

    public static function destroyCategory(string $eventId, string $categoryId): void
    {
        Csrf::verifyOrFail();
        TicketCategory::delete((int) $categoryId);
        Session::flash('success', 'Catégorie supprimée.');
        redirect('/events/' . $eventId . '/tickets');
    }

    public static function generate(string $eventId): void
    {
        Csrf::verifyOrFail();
        $categoryId = (int) input('ticket_category_id');

        Ticket::create([
            'ticket_category_id' => $categoryId,
            'code' => Ticket::generateCode(),
            'holder_name' => input('holder_name', ''),
            'holder_email' => input('holder_email', ''),
            'status' => 'valid',
        ]);
        Session::flash('success', 'Billet généré.');
        redirect('/events/' . $eventId . '/tickets');
    }

    public static function cancel(string $eventId, string $ticketId): void
    {
        Csrf::verifyOrFail();
        Ticket::update((int) $ticketId, ['status' => 'cancelled']);
        Session::flash('success', 'Billet annulé.');
        redirect('/events/' . $eventId . '/tickets');
    }

    public static function checkinForm(string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        View::render('tickets/checkin', [
            'title' => 'Check-in — ' . $event['title'],
            'event' => $event,
        ]);
    }

    public static function checkinSubmit(string $eventId): void
    {
        Csrf::verifyOrFail();
        $code = strtoupper(trim(input('code', '')));
        $ticket = Ticket::findByCode($code);

        $event = Event::find((int) $eventId);
        $message = null;
        $status = null;

        if (!$ticket || (int) $ticket['event_id'] !== (int) $eventId) {
            $message = 'Billet introuvable pour cet événement.';
            $status = 'error';
        } elseif ($ticket['status'] === 'cancelled') {
            $message = 'Ce billet a été annulé.';
            $status = 'error';
        } elseif ($ticket['status'] === 'checked_in') {
            $message = 'Ce billet a déjà été validé le ' . $ticket['checked_in_at'] . '.';
            $status = 'warning';
        } else {
            Database::connection()->prepare('UPDATE tickets SET status = "checked_in", checked_in_at = NOW() WHERE id = ?')->execute([$ticket['id']]);
            $message = 'Accès validé pour ' . ($ticket['holder_name'] ?: $ticket['code']) . '.';
            $status = 'success';
        }

        View::render('tickets/checkin', [
            'title' => 'Check-in — ' . $event['title'],
            'event' => $event,
            'message' => $message,
            'messageStatus' => $status,
        ]);
    }
}
