<?php

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Invoice;
use App\Models\Payment;

class PaymentController
{
    public static function store(string $invoiceId): void
    {
        Csrf::verifyOrFail();

        if (!Invoice::find((int) $invoiceId)) {
            http_response_code(404);
            die('Facture introuvable.');
        }

        $amount = (float) str_replace(',', '.', input('amount', '0'));

        Payment::create([
            'invoice_id' => (int) $invoiceId,
            'amount' => $amount,
            'payment_date' => input('payment_date') ?: date('Y-m-d'),
            'method' => in_array(input('method'), ['virement', 'cb', 'especes', 'cheque', 'autre'], true) ? input('method') : 'virement',
            'reference' => input('reference', ''),
            'notes' => input('notes', ''),
        ]);

        Invoice::recalculatePaidStatus((int) $invoiceId);
        ActivityLog::record('Paiement enregistré', 'invoice', (int) $invoiceId, (string) $amount);
        Session::flash('success', 'Paiement enregistré.');
        redirect('/invoices/' . $invoiceId);
    }

    public static function destroy(string $invoiceId, string $paymentId): void
    {
        Csrf::verifyOrFail();
        Payment::delete((int) $paymentId);
        Invoice::recalculatePaidStatus((int) $invoiceId);
        Session::flash('success', 'Paiement supprimé.');
        redirect('/invoices/' . $invoiceId);
    }
}
