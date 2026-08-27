<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AdminActivityLog;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;

class AdminTicketController
{
    public static function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/tickets', ['title' => 'Tickets support', 'tickets' => SupportTicket::allWithOrganization()]);
    }

    public static function show(string $id): void
    {
        Auth::requireSuperAdmin();

        $ticket = SupportTicket::findWithOrganizationForAdmin((int) $id);
        if (!$ticket) { http_response_code(404); die('Ticket introuvable.'); }

        View::render('admin/ticket_show', [
            'title' => $ticket['subject'],
            'ticket' => $ticket,
            'messages' => SupportTicketMessage::forTicket((int) $id),
            'smtpConfigured' => Mailer::isSystemConfigured(),
        ]);
    }

    public static function reply(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $ticket = SupportTicket::findWithOrganizationForAdmin((int) $id);
        if (!$ticket) { http_response_code(404); die('Ticket introuvable.'); }

        $body = input('body', '');
        if ($body === '') {
            Session::flash('error', 'Le message ne peut pas être vide.');
            redirect('/admin/tickets/' . $id);
        }

        SupportTicketMessage::create([
            'ticket_id' => (int) $id,
            'user_id' => Auth::id(),
            'is_staff_reply' => 1,
            'body' => $body,
        ]);

        Database::connection()->prepare("UPDATE support_tickets SET status = 'pending', updated_at = NOW() WHERE id = ?")->execute([$id]);

        $stmt = Database::connection()->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$ticket['user_id']]);
        $recipientEmail = $stmt->fetchColumn();

        if ($recipientEmail) {
            try {
                Mailer::sendSystem(
                    $recipientEmail,
                    'Réponse à votre ticket : ' . $ticket['subject'],
                    '<p>Une réponse a été apportée à votre ticket de support :</p><blockquote>' . nl2br(View::e($body)) . '</blockquote>'
                );
            } catch (\RuntimeException $e) {
                // Notification best-effort: the reply is still recorded even if the system SMTP isn't configured.
            }
        }

        if (!empty($ticket['user_id'])) {
            \App\Models\Notification::toUser(
                (int) $ticket['user_id'],
                (int) $ticket['organization_id'],
                'ticket',
                'Réponse à votre ticket',
                $ticket['subject'],
                '/support/' . $id
            );
        }

        AdminActivityLog::record('ticket_reply', 'support_ticket', (int) $id, $ticket['subject']);
        Session::flash('success', 'Réponse envoyée.');
        redirect('/admin/tickets/' . $id);
    }

    public static function updateStatus(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $status = input('status', '');
        if (!in_array($status, ['open', 'pending', 'closed'], true)) {
            Session::flash('error', 'Statut invalide.');
            redirect('/admin/tickets/' . $id);
        }

        Database::connection()->prepare('UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $id]);
        AdminActivityLog::record('ticket_status', 'support_ticket', (int) $id, $status);

        Session::flash('success', 'Statut du ticket mis à jour.');
        redirect('/admin/tickets/' . $id);
    }
}
