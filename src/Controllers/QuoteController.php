<?php

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\Client;
use App\Models\CompanySettings;
use App\Models\EmailTemplate;
use App\Models\Event;
use App\Models\Quote;

class QuoteController
{
    public static function index(): void
    {
        View::render('quotes/index', ['title' => 'Devis', 'quotes' => Quote::allWithClient()]);
    }

    public static function create(): void
    {
        $company = CompanySettings::get();
        View::render('quotes/form', [
            'title' => 'Nouveau devis',
            'quote' => null,
            'items' => [],
            'clients' => Client::all('created_at DESC'),
            'events' => Event::allWithClient(),
            'company' => $company,
            'selectedClientId' => $_GET['client_id'] ?? null,
            'selectedEventId' => $_GET['event_id'] ?? null,
        ]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        self::assertOwnership((int) input('client_id'), input('event_id'));
        [$items, $subtotal, $taxRate, $taxAmount, $total] = self::parseItems();

        $id = Quote::create([
            'quote_number' => Quote::nextNumber(),
            'client_id' => (int) input('client_id'),
            'event_id' => input('event_id') !== '' ? (int) input('event_id') : null,
            'status' => 'draft',
            'issue_date' => input('issue_date') ?: date('Y-m-d'),
            'valid_until' => input('valid_until') !== '' ? input('valid_until') : null,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => input('notes', ''),
        ]);

        Quote::replaceItems($id, $items);
        ActivityLog::record('Création devis', 'quote', $id);
        Session::flash('success', 'Devis créé.');
        redirect('/quotes/' . $id);
    }

    public static function show(string $id): void
    {
        $quote = Quote::findWithRelations((int) $id);
        if (!$quote) { http_response_code(404); die('Devis introuvable.'); }

        View::render('quotes/show', [
            'title' => $quote['quote_number'],
            'quote' => $quote,
            'items' => Quote::items((int) $id),
            'smtpConfigured' => Mailer::isConfigured(),
        ]);
    }

    public static function edit(string $id): void
    {
        $quote = Quote::find((int) $id);
        if (!$quote) { http_response_code(404); die('Devis introuvable.'); }

        View::render('quotes/form', [
            'title' => 'Modifier le devis ' . $quote['quote_number'],
            'quote' => $quote,
            'items' => Quote::items((int) $id),
            'clients' => Client::all('created_at DESC'),
            'events' => Event::allWithClient(),
            'company' => CompanySettings::get(),
            'selectedClientId' => null,
            'selectedEventId' => null,
        ]);
    }

    public static function update(string $id): void
    {
        Csrf::verifyOrFail();
        self::assertOwnership((int) input('client_id'), input('event_id'));
        [$items, $subtotal, $taxRate, $taxAmount, $total] = self::parseItems();

        Quote::update((int) $id, [
            'client_id' => (int) input('client_id'),
            'event_id' => input('event_id') !== '' ? (int) input('event_id') : null,
            'issue_date' => input('issue_date'),
            'valid_until' => input('valid_until') !== '' ? input('valid_until') : null,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => input('notes', ''),
        ]);

        Quote::replaceItems((int) $id, $items);
        Session::flash('success', 'Devis mis à jour.');
        redirect('/quotes/' . $id);
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Quote::delete((int) $id);
        Session::flash('success', 'Devis supprimé.');
        redirect('/quotes');
    }

    public static function updateStatus(string $id): void
    {
        Csrf::verifyOrFail();
        $status = input('status');
        if (in_array($status, ['draft', 'sent', 'accepted', 'refused', 'expired'], true)) {
            Quote::update((int) $id, ['status' => $status]);
            ActivityLog::record('Changement statut devis', 'quote', (int) $id, $status);
            Session::flash('success', 'Statut du devis mis à jour.');
        }
        redirect('/quotes/' . $id);
    }

    public static function send(string $id): void
    {
        Csrf::verifyOrFail();
        $quote = Quote::findWithRelations((int) $id);

        if (!$quote || empty($quote['client_email'])) {
            Session::flash('error', "Impossible d'envoyer : le client n'a pas d'adresse email renseignée.");
            redirect('/quotes/' . $id);
        }

        $items = Quote::items((int) $id);
        $company = CompanySettings::get();
        $template = EmailTemplate::get('quote');
        $subject = str_replace('{number}', $quote['quote_number'], $template['subject'] ?? 'Votre devis {number}');

        ob_start();
        View::render('quotes/email', ['quote' => $quote, 'items' => $items, 'company' => $company, 'intro' => $template['intro'] ?? null], layout: null);
        $html = ob_get_clean();

        try {
            Mailer::send($quote['client_email'], $subject, $html);
            Quote::update((int) $id, ['status' => $quote['status'] === 'draft' ? 'sent' : $quote['status']]);
            \App\Models\Notification::toClient(
                (int) $quote['client_id'],
                Auth::organizationId(),
                'quote',
                'Nouveau devis',
                'Le devis ' . $quote['quote_number'] . ' vous a été envoyé.'
            );
            Session::flash('success', 'Devis envoyé par email à ' . $quote['client_email']);
        } catch (\RuntimeException $e) {
            Session::flash('error', "Échec de l'envoi : " . $e->getMessage());
        }

        redirect('/quotes/' . $id);
    }

    public static function printView(string $id): void
    {
        $quote = Quote::findWithRelations((int) $id);
        if (!$quote) { http_response_code(404); die('Devis introuvable.'); }

        View::render('quotes/print', [
            'quote' => $quote,
            'items' => Quote::items((int) $id),
            'company' => CompanySettings::get(),
        ], layout: null);
    }

    public static function convertToInvoice(string $id): void
    {
        Csrf::verifyOrFail();
        $quote = Quote::find((int) $id);
        if (!$quote) { http_response_code(404); die('Devis introuvable.'); }

        $items = Quote::items((int) $id);
        $pdo = Database::connection();
        $orgId = Auth::organizationId();

        $stmt = $pdo->prepare('SELECT invoice_prefix FROM company_settings WHERE organization_id = ?');
        $stmt->execute([$orgId]);
        $prefix = $stmt->fetchColumn() ?: 'FAC-';
        $year = date('Y');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM invoices WHERE invoice_number LIKE ? AND organization_id = ?');
        $stmt->execute([$prefix . $year . '-%', $orgId]);
        $number = sprintf('%s%s-%03d', $prefix, $year, (int) $stmt->fetchColumn() + 1);

        $invoiceStmt = $pdo->prepare(
            'INSERT INTO invoices (organization_id, invoice_number, quote_id, client_id, event_id, type, status, issue_date, due_date, subtotal, tax_rate, tax_amount, total, notes)
             VALUES (?, ?, ?, ?, ?, "standalone", "draft", ?, ?, ?, ?, ?, ?, ?)'
        );
        $invoiceStmt->execute([
            $orgId,
            $number,
            $quote['id'],
            $quote['client_id'],
            $quote['event_id'],
            date('Y-m-d'),
            date('Y-m-d', strtotime('+30 days')),
            $quote['subtotal'],
            $quote['tax_rate'],
            $quote['tax_amount'],
            $quote['total'],
            $quote['notes'],
        ]);
        $invoiceId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO invoice_items (organization_id, invoice_id, description, quantity, unit_price, total, position) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $i => $item) {
            $itemStmt->execute([$orgId, $invoiceId, $item['description'], $item['quantity'], $item['unit_price'], $item['total'], $i]);
        }

        Session::flash('success', 'Facture ' . $number . ' générée à partir du devis.');
        redirect('/invoices/' . $invoiceId);
    }

    private static function assertOwnership(int $clientId, $eventId): void
    {
        if (!Client::find($clientId)) {
            http_response_code(404);
            die('Client introuvable.');
        }
        if ($eventId !== '' && !Event::find((int) $eventId)) {
            http_response_code(404);
            die('Événement introuvable.');
        }
    }

    private static function parseItems(): array
    {
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unitPrices = $_POST['unit_price'] ?? [];

        $items = [];
        $subtotal = 0.0;

        foreach ($descriptions as $i => $description) {
            $description = trim($description);
            if ($description === '') {
                continue;
            }
            $qty = (float) str_replace(',', '.', $quantities[$i] ?? 1);
            $price = (float) str_replace(',', '.', $unitPrices[$i] ?? 0);
            $lineTotal = round($qty * $price, 2);

            $items[] = ['description' => $description, 'quantity' => $qty, 'unit_price' => $price, 'total' => $lineTotal];
            $subtotal += $lineTotal;
        }

        $taxRate = (float) str_replace(',', '.', input('tax_rate', '20'));
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total = round($subtotal + $taxAmount, 2);

        return [$items, round($subtotal, 2), $taxRate, $taxAmount, $total];
    }
}
