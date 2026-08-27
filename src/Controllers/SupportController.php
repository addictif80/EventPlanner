<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;

/**
 * Tenant-facing side of the support ticket system: any user of an
 * organization can open a ticket and exchange messages with the platform's
 * super admins (see AdminTicketController for the staff side).
 */
class SupportController
{
    public static function index(): void
    {
        View::render('support/index', ['title' => 'Support', 'tickets' => SupportTicket::all('updated_at DESC')]);
    }

    public static function create(): void
    {
        View::render('support/form', ['title' => 'Nouveau ticket']);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();

        $subject = input('subject', '');
        $body = input('body', '');
        if ($subject === '' || $body === '') {
            Session::flash('error', 'Le sujet et le message sont obligatoires.');
            redirect('/support/create');
        }

        $id = SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $subject,
            'priority' => in_array(input('priority'), ['low', 'normal', 'high'], true) ? input('priority') : 'normal',
            'status' => 'open',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $id,
            'user_id' => Auth::id(),
            'is_staff_reply' => 0,
            'body' => $body,
        ]);

        \App\Models\Notification::toPlatform('ticket', 'Nouveau ticket support', $subject, '/admin/tickets/' . $id);

        Session::flash('success', 'Ticket envoyé, notre équipe vous répondra rapidement.');
        redirect('/support/' . $id);
    }

    public static function show(string $id): void
    {
        $ticket = SupportTicket::find((int) $id);
        if (!$ticket) { http_response_code(404); die('Ticket introuvable.'); }

        View::render('support/show', [
            'title' => $ticket['subject'],
            'ticket' => $ticket,
            'messages' => SupportTicketMessage::forTicket((int) $id),
        ]);
    }

    public static function reply(string $id): void
    {
        Csrf::verifyOrFail();

        $ticket = SupportTicket::find((int) $id);
        if (!$ticket) { http_response_code(404); die('Ticket introuvable.'); }

        $body = input('body', '');
        if ($body === '') {
            Session::flash('error', 'Le message ne peut pas être vide.');
            redirect('/support/' . $id);
        }

        SupportTicketMessage::create([
            'ticket_id' => (int) $id,
            'user_id' => Auth::id(),
            'is_staff_reply' => 0,
            'body' => $body,
        ]);

        SupportTicket::update((int) $id, ['status' => 'open']);

        Session::flash('success', 'Message envoyé.');
        redirect('/support/' . $id);
    }
}
