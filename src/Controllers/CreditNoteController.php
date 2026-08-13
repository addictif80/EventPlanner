<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\CompanySettings;
use App\Models\CreditNote;
use App\Models\Invoice;

class CreditNoteController
{
    public static function store(string $invoiceId): void
    {
        Csrf::verifyOrFail();
        $invoice = Invoice::find((int) $invoiceId);
        if (!$invoice) { http_response_code(404); die('Facture introuvable.'); }

        $amount = (float) str_replace(',', '.', input('amount', '0'));

        $id = CreditNote::create([
            'credit_note_number' => CreditNote::nextNumber(),
            'invoice_id' => (int) $invoiceId,
            'client_id' => $invoice['client_id'],
            'issue_date' => date('Y-m-d'),
            'amount' => $amount,
            'reason' => input('reason', ''),
        ]);

        // recalculatePaidStatus() aggregates payments + credit notes into amount_paid/status.
        Invoice::recalculatePaidStatus((int) $invoiceId);

        Session::flash('success', 'Avoir généré.');
        redirect('/credit-notes/' . $id . '/print');
    }

    public static function printView(string $id): void
    {
        $creditNote = CreditNote::findWithRelations((int) $id);
        if (!$creditNote) { http_response_code(404); die('Avoir introuvable.'); }

        View::render('credit_notes/print', [
            'creditNote' => $creditNote,
            'company' => CompanySettings::get(),
        ], layout: null);
    }
}
