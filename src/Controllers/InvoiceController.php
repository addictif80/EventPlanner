<?php

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\Client;
use App\Models\CompanySettings;
use App\Models\CreditNote;
use App\Models\EmailTemplate;
use App\Models\Event;
use App\Models\Invoice;

class InvoiceController
{
    public static function index(): void
    {
        View::render('invoices/index', ['title' => 'Factures', 'invoices' => Invoice::allWithClient()]);
    }

    public static function create(): void
    {
        View::render('invoices/form', [
            'title' => 'Nouvelle facture',
            'invoice' => null,
            'items' => [],
            'clients' => Client::all('created_at DESC'),
            'events' => Event::allWithClient(),
            'company' => CompanySettings::get(),
            'selectedClientId' => $_GET['client_id'] ?? null,
            'selectedEventId' => $_GET['event_id'] ?? null,
        ]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        [$items, $subtotal, $taxRate, $taxAmount, $total] = self::parseItems();

        $id = Invoice::create([
            'invoice_number' => Invoice::nextNumber(),
            'client_id' => (int) input('client_id'),
            'event_id' => input('event_id') !== '' ? (int) input('event_id') : null,
            'type' => in_array(input('type'), ['deposit', 'final', 'standalone'], true) ? input('type') : 'standalone',
            'status' => 'draft',
            'issue_date' => input('issue_date') ?: date('Y-m-d'),
            'due_date' => input('due_date') !== '' ? input('due_date') : null,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => input('notes', ''),
        ]);

        Invoice::replaceItems($id, $items);
        ActivityLog::record('Création facture', 'invoice', $id);
        Session::flash('success', 'Facture créée.');
        redirect('/invoices/' . $id);
    }

    public static function show(string $id): void
    {
        $invoice = Invoice::findWithRelations((int) $id);
        if (!$invoice) { http_response_code(404); die('Facture introuvable.'); }

        View::render('invoices/show', [
            'title' => $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => Invoice::items((int) $id),
            'payments' => Invoice::payments((int) $id),
            'creditNotes' => CreditNote::forInvoice((int) $id),
            'smtpConfigured' => Mailer::isConfigured(),
            'stripeEnabled' => !empty(CompanySettings::get()['stripe_secret_key']),
            'stripePaymentUrl' => $_SESSION['stripe_payment_url_' . $id] ?? null,
        ]);
        unset($_SESSION['stripe_payment_url_' . $id]);
    }

    public static function setRecurring(string $id): void
    {
        Csrf::verifyOrFail();
        $isRecurring = input('is_recurring') ? 1 : 0;
        $interval = in_array(input('recurrence_interval'), ['monthly', 'quarterly', 'yearly'], true) ? input('recurrence_interval') : null;

        $nextDate = null;
        if ($isRecurring && $interval) {
            $map = ['monthly' => '+1 month', 'quarterly' => '+3 months', 'yearly' => '+1 year'];
            $nextDate = date('Y-m-d', strtotime($map[$interval]));
        }

        Invoice::update((int) $id, [
            'is_recurring' => $isRecurring,
            'recurrence_interval' => $interval,
            'recurrence_next_date' => $nextDate,
        ]);

        Session::flash('success', 'Paramètres de récurrence mis à jour.');
        redirect('/invoices/' . $id);
    }

    public static function generateNext(string $id): void
    {
        Csrf::verifyOrFail();
        $invoice = Invoice::find((int) $id);
        if (!$invoice) { http_response_code(404); die('Facture introuvable.'); }

        $items = Invoice::items((int) $id);
        $number = Invoice::nextNumber();

        $newId = Invoice::create([
            'invoice_number' => $number,
            'quote_id' => $invoice['quote_id'],
            'client_id' => $invoice['client_id'],
            'event_id' => $invoice['event_id'],
            'type' => $invoice['type'],
            'status' => 'draft',
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => $invoice['subtotal'],
            'tax_rate' => $invoice['tax_rate'],
            'tax_amount' => $invoice['tax_amount'],
            'total' => $invoice['total'],
            'notes' => $invoice['notes'],
            'is_recurring' => $invoice['is_recurring'],
            'recurrence_interval' => $invoice['recurrence_interval'],
            'recurrence_parent_id' => $invoice['id'],
        ]);

        Invoice::replaceItems($newId, $items);

        if (!empty($invoice['recurrence_interval'])) {
            $map = ['monthly' => '+1 month', 'quarterly' => '+3 months', 'yearly' => '+1 year'];
            Invoice::update((int) $id, ['recurrence_next_date' => date('Y-m-d', strtotime($map[$invoice['recurrence_interval']]))]);
        }

        Session::flash('success', 'Nouvelle échéance générée : ' . $number);
        redirect('/invoices/' . $newId);
    }

    public static function edit(string $id): void
    {
        $invoice = Invoice::find((int) $id);
        if (!$invoice) { http_response_code(404); die('Facture introuvable.'); }

        View::render('invoices/form', [
            'title' => 'Modifier la facture ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => Invoice::items((int) $id),
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
        [$items, $subtotal, $taxRate, $taxAmount, $total] = self::parseItems();

        Invoice::update((int) $id, [
            'client_id' => (int) input('client_id'),
            'event_id' => input('event_id') !== '' ? (int) input('event_id') : null,
            'type' => in_array(input('type'), ['deposit', 'final', 'standalone'], true) ? input('type') : 'standalone',
            'issue_date' => input('issue_date'),
            'due_date' => input('due_date') !== '' ? input('due_date') : null,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'notes' => input('notes', ''),
        ]);

        Invoice::replaceItems((int) $id, $items);
        Invoice::recalculatePaidStatus((int) $id);
        Session::flash('success', 'Facture mise à jour.');
        redirect('/invoices/' . $id);
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Invoice::delete((int) $id);
        Session::flash('success', 'Facture supprimée.');
        redirect('/invoices');
    }

    public static function send(string $id): void
    {
        Csrf::verifyOrFail();
        $invoice = Invoice::findWithRelations((int) $id);

        if (!$invoice || empty($invoice['client_email'])) {
            Session::flash('error', "Impossible d'envoyer : le client n'a pas d'adresse email renseignée.");
            redirect('/invoices/' . $id);
        }

        $items = Invoice::items((int) $id);
        $company = CompanySettings::get();
        $template = EmailTemplate::get('invoice');
        $subject = str_replace('{number}', $invoice['invoice_number'], $template['subject'] ?? 'Votre facture {number}');

        ob_start();
        View::render('invoices/email', ['invoice' => $invoice, 'items' => $items, 'company' => $company, 'intro' => $template['intro'] ?? null], layout: null);
        $html = ob_get_clean();

        try {
            Mailer::send($invoice['client_email'], $subject, $html);
            if ($invoice['status'] === 'draft') {
                \App\Models\Invoice::update((int) $id, ['status' => 'sent']);
            }
            Session::flash('success', 'Facture envoyée par email à ' . $invoice['client_email']);
        } catch (\RuntimeException $e) {
            Session::flash('error', "Échec de l'envoi : " . $e->getMessage());
        }

        redirect('/invoices/' . $id);
    }

    public static function sendReminder(string $id): void
    {
        Csrf::verifyOrFail();
        $invoice = Invoice::findWithRelations((int) $id);

        if (!$invoice || empty($invoice['client_email'])) {
            Session::flash('error', "Impossible d'envoyer : le client n'a pas d'adresse email renseignée.");
            redirect('/invoices/' . $id);
        }

        $company = CompanySettings::get();
        $template = EmailTemplate::get('reminder');
        $subject = str_replace('{number}', $invoice['invoice_number'], $template['subject'] ?? 'Rappel — facture {number} en attente de paiement');

        ob_start();
        View::render('invoices/reminder_email', ['invoice' => $invoice, 'company' => $company, 'intro' => $template['intro'] ?? null], layout: null);
        $html = ob_get_clean();

        try {
            Mailer::send($invoice['client_email'], $subject, $html);
            Session::flash('success', 'Relance envoyée à ' . $invoice['client_email']);
        } catch (\RuntimeException $e) {
            Session::flash('error', "Échec de l'envoi : " . $e->getMessage());
        }

        redirect('/invoices/' . $id);
    }

    public static function printView(string $id): void
    {
        $invoice = Invoice::findWithRelations((int) $id);
        if (!$invoice) { http_response_code(404); die('Facture introuvable.'); }

        View::render('invoices/print', [
            'invoice' => $invoice,
            'items' => Invoice::items((int) $id),
            'company' => CompanySettings::get(),
        ], layout: null);
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
