<?php

namespace App\Controllers;

use App\Core\View;
use App\Models\PosSale;

/**
 * Public, unauthenticated ticket page: the buyer reaches it either by
 * clicking the link emailed at checkout or by scanning the QR code printed
 * on their in-person receipt, and can view/download their ticket on their
 * own phone without any staff involvement. The access token is the sole
 * scope — there is no Auth session here (see PortalController for the same
 * pattern used by the client portal).
 */
class PosReceiptController
{
    public static function show(string $token): void
    {
        $sale = PosSale::findByToken($token);
        if (!$sale) { http_response_code(404); die('Ticket introuvable ou invalide.'); }

        $companyStmt = \App\Core\Database::connection()->prepare('SELECT * FROM company_settings WHERE organization_id = ?');
        $companyStmt->execute([$sale['organization_id']]);

        View::render('pos/receipt_public', [
            'sale' => $sale,
            'items' => PosSale::itemsUnscoped((int) $sale['id']),
            'company' => $companyStmt->fetch() ?: [],
            'token' => $token,
        ], layout: null);
    }

    public static function download(string $token): void
    {
        $sale = PosSale::findByToken($token);
        if (!$sale) { http_response_code(404); die('Ticket introuvable ou invalide.'); }

        $companyStmt = \App\Core\Database::connection()->prepare('SELECT * FROM company_settings WHERE organization_id = ?');
        $companyStmt->execute([$sale['organization_id']]);

        PosController::streamReceiptPdf($sale, PosSale::itemsUnscoped((int) $sale['id']), $companyStmt->fetch() ?: []);
    }
}
