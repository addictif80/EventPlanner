<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\ModuleAccess;
use App\Core\Session;
use App\Core\TicketPdf;
use App\Core\View;
use App\Models\CompanySettings;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Ticket;
use App\Models\TicketCategory;

class TicketController
{
    public static function index(string $eventId): void
    {
        ModuleAccess::requireModule('ticketing');
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
        ModuleAccess::requireModule('ticketing');
        Csrf::verifyOrFail();
        if (!Event::find((int) $eventId)) { http_response_code(404); die('Événement introuvable.'); }
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
        ModuleAccess::requireModule('ticketing');
        Csrf::verifyOrFail();
        Database::connection()->prepare('DELETE FROM ticket_categories WHERE id = ? AND event_id = ? AND organization_id = ?')
            ->execute([$categoryId, $eventId, Auth::organizationId()]);
        Session::flash('success', 'Catégorie supprimée.');
        redirect('/events/' . $eventId . '/tickets');
    }

    public static function generate(string $eventId): void
    {
        ModuleAccess::requireModule('ticketing');
        Csrf::verifyOrFail();
        $categoryId = (int) input('ticket_category_id');
        $category = TicketCategory::find($categoryId);
        if (!$category || (int) $category['event_id'] !== (int) $eventId) {
            http_response_code(404);
            die('Catégorie introuvable.');
        }

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
        ModuleAccess::requireModule('ticketing');
        Csrf::verifyOrFail();
        Database::connection()->prepare(
            'UPDATE tickets SET status = "cancelled"
             WHERE id = :ticket_id AND organization_id = :org_id
             AND ticket_category_id IN (SELECT id FROM ticket_categories WHERE event_id = :event_id AND organization_id = :org_id2)'
        )->execute([
            'ticket_id' => $ticketId,
            'org_id' => Auth::organizationId(),
            'event_id' => $eventId,
            'org_id2' => Auth::organizationId(),
        ]);
        Session::flash('success', 'Billet annulé.');
        redirect('/events/' . $eventId . '/tickets');
    }

    /** Preview page: shows who will receive a ticket and a live rendering of the email before sending. */
    public static function sendForm(string $eventId): void
    {
        ModuleAccess::requireModule('ticketing');
        $event = Event::findWithRelations((int) $eventId);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        $categories = TicketCategory::forEvent((int) $eventId);
        $guests = array_values(array_filter(Guest::forEvent((int) $eventId), fn($g) => !empty($g['email'])));
        $missingEmailCount = count(Guest::forEvent((int) $eventId)) - count($guests);

        $categoryId = (int) ($_GET['ticket_category_id'] ?? ($categories[0]['id'] ?? 0));
        $category = null;
        foreach ($categories as $c) {
            if ((int) $c['id'] === $categoryId) {
                $category = $c;
                break;
            }
        }

        $previewHtml = null;
        if ($category && !empty($guests)) {
            $company = CompanySettings::get();
            $sampleGuest = $guests[0];
            $sampleTicket = [
                'code' => Ticket::generateCode(),
                'holder_name' => Guest::displayName($sampleGuest),
                'category_name' => $category['name'],
            ];
            $bodyHtml = self::renderEmailHtml($event, $company, $sampleGuest, [$sampleTicket]);
            $previewHtml = \App\Core\EmailDesign::wrap($bodyHtml, $company['company_name'] ?? 'EventPlanner');
        }

        View::render('tickets/send', [
            'title' => 'Envoyer les billets — ' . $event['title'],
            'event' => $event,
            'categories' => $categories,
            'category' => $category,
            'guests' => $guests,
            'missingEmailCount' => max(0, $missingEmailCount),
            'previewHtml' => $previewHtml,
        ]);
    }

    /** Issues (if needed) and emails one PDF ticket per eligible guest for the chosen category. */
    public static function sendBulk(string $eventId): void
    {
        ModuleAccess::requireModule('ticketing');
        Csrf::verifyOrFail();

        $event = Event::findWithRelations((int) $eventId);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        $categoryId = (int) input('ticket_category_id');
        $category = TicketCategory::find($categoryId);
        if (!$category || (int) $category['event_id'] !== (int) $eventId) {
            Session::flash('error', 'Catégorie de billet invalide.');
            redirect('/events/' . $eventId . '/tickets');
        }

        $guests = array_values(array_filter(Guest::forEvent((int) $eventId), fn($g) => !empty($g['email'])));
        if (empty($guests)) {
            Session::flash('error', "Aucun invité avec une adresse email pour cet événement.");
            redirect('/events/' . $eventId . '/tickets');
        }

        $company = CompanySettings::get();
        $soldCountStmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM tickets WHERE ticket_category_id = ? AND organization_id = ? AND status != 'cancelled'"
        );
        $soldCountStmt->execute([$categoryId, Auth::organizationId()]);
        $soldCount = (int) $soldCountStmt->fetchColumn();
        $remainingCapacity = (int) $category['quantity_available'] > 0
            ? (int) $category['quantity_available'] - $soldCount
            : null;

        $sent = 0;
        $skippedCapacity = 0;
        $failed = 0;
        $lastError = null;

        foreach ($guests as $guest) {
            $ticket = Ticket::forGuestAndCategory((int) $guest['id'], $categoryId);
            if (!$ticket) {
                if ($remainingCapacity !== null && $remainingCapacity <= 0) {
                    $skippedCapacity++;
                    continue;
                }
                $ticketId = Ticket::create([
                    'ticket_category_id' => $categoryId,
                    'guest_id' => (int) $guest['id'],
                    'code' => Ticket::generateCode(),
                    'holder_name' => Guest::displayName($guest),
                    'holder_email' => $guest['email'],
                    'status' => 'valid',
                ]);
                $ticket = Ticket::find($ticketId);
                if ($remainingCapacity !== null) {
                    $remainingCapacity--;
                }
            }

            try {
                $pdf = TicketPdf::build($event, $company, [$ticket]);
                $html = self::renderEmailHtml($event, $company, $guest, [$ticket]);
                $filename = self::slug($event['title']) . '-billet.pdf';

                Mailer::send(
                    $guest['email'],
                    'Votre billet — ' . $event['title'],
                    $html,
                    null,
                    [['filename' => $filename, 'mimeType' => 'application/pdf', 'content' => $pdf]]
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $lastError = $e->getMessage();
            }
        }

        $parts = ["{$sent} billet(s) envoyé(s)"];
        if ($skippedCapacity > 0) {
            $parts[] = "{$skippedCapacity} non émis (capacité de la catégorie atteinte)";
        }
        if ($failed > 0) {
            $parts[] = "{$failed} échec(s) d'envoi" . ($lastError ? ' (' . $lastError . ')' : '');
        }
        Session::flash($failed > 0 && $sent === 0 ? 'error' : 'success', implode(', ', $parts) . '.');

        redirect('/events/' . $eventId . '/tickets');
    }

    private static function renderEmailHtml(array $event, array $company, array $guest, array $tickets): string
    {
        ob_start();
        View::render('tickets/email', [
            'event' => $event,
            'company' => $company,
            'guest' => $guest,
            'tickets' => $tickets,
        ], layout: null);
        return ob_get_clean();
    }

    private static function slug(string $text): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $ascii));
        return trim($slug, '-') ?: 'evenement';
    }

    public static function checkinForm(string $eventId): void
    {
        ModuleAccess::requireModule('ticketing');
        $event = Event::find((int) $eventId);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        View::render('tickets/checkin', [
            'title' => 'Check-in — ' . $event['title'],
            'event' => $event,
            'stats' => self::stats((int) $eventId),
        ]);
    }

    private static function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /** Counts + the most recently checked-in holders, for the live "mode jour J" counter. */
    private static function stats(int $eventId): array
    {
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total, SUM(t.status = 'checked_in') AS checked_in
             FROM tickets t JOIN ticket_categories tc ON tc.id = t.ticket_category_id
             WHERE tc.event_id = ? AND t.organization_id = ? AND t.status != 'cancelled'"
        );
        $stmt->execute([$eventId, $orgId]);
        $row = $stmt->fetch() ?: ['total' => 0, 'checked_in' => 0];

        $stmt = $pdo->prepare(
            "SELECT t.holder_name, t.code, t.checked_in_at, tc.name AS category_name
             FROM tickets t JOIN ticket_categories tc ON tc.id = t.ticket_category_id
             WHERE tc.event_id = ? AND t.organization_id = ? AND t.status = 'checked_in'
             ORDER BY t.checked_in_at DESC LIMIT 10"
        );
        $stmt->execute([$eventId, $orgId]);

        return [
            'total' => (int) $row['total'],
            'checked_in' => (int) $row['checked_in'],
            'recent' => $stmt->fetchAll(),
        ];
    }

    public static function checkinStats(string $eventId): void
    {
        ModuleAccess::requireModule('ticketing');
        if (!Event::find((int) $eventId)) { http_response_code(404); echo json_encode(['error' => 'not_found']); return; }
        header('Content-Type: application/json');
        echo json_encode(self::stats((int) $eventId));
    }

    public static function checkinSubmit(string $eventId): void
    {
        ModuleAccess::requireModule('ticketing');
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
            $message = 'Billet déjà validé le ' . $ticket['checked_in_at'] . '.';
            $status = 'warning';
        } else {
            Database::connection()->prepare('UPDATE tickets SET status = "checked_in", checked_in_at = NOW() WHERE id = ? AND organization_id = ?')
                ->execute([$ticket['id'], Auth::organizationId()]);
            $message = 'Accès validé pour ' . ($ticket['holder_name'] ?: $ticket['code']) . '.';
            $status = 'success';
        }

        if (self::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(array_merge(
                ['message' => $message, 'status' => $status],
                self::stats((int) $eventId)
            ));
            return;
        }

        View::render('tickets/checkin', [
            'title' => 'Check-in — ' . $event['title'],
            'event' => $event,
            'message' => $message,
            'messageStatus' => $status,
            'stats' => self::stats((int) $eventId),
        ]);
    }
}
