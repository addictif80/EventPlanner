<?php

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\ModuleAccess;
use App\Core\Session;
use App\Core\View;
use App\Models\CompanySettings;
use App\Models\Event;
use App\Models\Product;
use App\Models\PosSale;
use App\Models\PosSession;

class PosController
{
    /** Entry point: straight into the open session's till, or the "open a register" form if none. */
    public static function index(): void
    {
        ModuleAccess::requireModule('pos');
        $open = PosSession::currentOpen();
        if ($open) {
            redirect('/pos/' . $open['id']);
        }

        View::render('pos/open', [
            'title' => 'Ouvrir la caisse',
            'events' => Event::allWithClient(),
        ]);
    }

    public static function open(): void
    {
        ModuleAccess::requireModule('pos');
        Csrf::verifyOrFail();

        if (PosSession::currentOpen()) {
            Session::flash('error', 'Une session de caisse est déjà ouverte.');
            redirect('/pos');
        }

        $eventId = input('event_id') !== '' ? (int) input('event_id') : null;
        if ($eventId !== null && !Event::find($eventId)) {
            http_response_code(404);
            die('Événement introuvable.');
        }

        $id = PosSession::create([
            'event_id' => $eventId,
            'status' => 'open',
            'opening_float' => (float) str_replace(',', '.', input('opening_float', '0')),
            'opened_by' => Auth::id(),
        ]);

        ActivityLog::record('Ouverture de caisse', 'pos_session', $id);
        Session::flash('success', 'Caisse ouverte.');
        redirect('/pos/' . $id);
    }

    /** The main point-of-sale screen: product grid + cart, for an open session. */
    public static function till(string $id): void
    {
        ModuleAccess::requireModule('pos');
        $session = PosSession::findWithRelations((int) $id);
        if (!$session) { http_response_code(404); die('Session de caisse introuvable.'); }
        if ($session['status'] !== 'open') {
            redirect('/pos/sessions/' . $id);
        }

        View::render('pos/till', [
            'title' => 'Caisse — ' . ($session['event_title'] ?? 'session #' . $session['id']),
            'session' => $session,
            'products' => Product::all('category ASC, name ASC'),
            'expectedCash' => PosSession::expectedCash((int) $id),
        ]);
    }

    public static function sell(string $id): void
    {
        ModuleAccess::requireModule('pos');
        Csrf::verifyOrFail();

        $session = PosSession::find((int) $id);
        if (!$session || $session['status'] !== 'open') {
            Session::flash('error', 'Cette session de caisse est fermée.');
            redirect('/pos');
        }

        $cart = json_decode(input('cart_json', '[]'), true);
        if (!is_array($cart) || empty($cart)) {
            Session::flash('error', 'Le panier est vide.');
            redirect('/pos/' . $id);
        }

        $items = [];
        $total = 0.0;
        foreach ($cart as $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $productId = !empty($line['product_id']) ? (int) $line['product_id'] : null;
            $description = trim((string) ($line['description'] ?? ''));
            $unitPrice = (float) ($line['unit_price'] ?? 0);

            // The unit price and description are always re-read from the
            // catalogue when a product_id is given — a POST body is
            // attacker-controlled and must never set its own price for a
            // known product, only for a free-form "article personnalisé" line.
            if ($productId !== null) {
                $product = Product::find($productId);
                if (!$product) {
                    continue;
                }
                $description = $product['name'];
                $unitPrice = (float) $product['unit_price'];
            }

            if ($description === '') {
                continue;
            }

            $lineTotal = round($qty * $unitPrice, 2);
            $items[] = ['product_id' => $productId, 'description' => $description, 'quantity' => $qty, 'unit_price' => $unitPrice, 'total' => $lineTotal];
            $total += $lineTotal;
        }

        if (empty($items)) {
            Session::flash('error', 'Le panier est vide.');
            redirect('/pos/' . $id);
        }

        $paymentMethod = in_array(input('payment_method'), ['cash', 'card', 'other'], true) ? input('payment_method') : 'cash';
        $buyerEmail = trim(input('buyer_email', ''));

        // All stock decrements + the sale itself succeed or fail together: an
        // item further down the cart failing must not leave an earlier item's
        // stock decremented for a sale that never gets recorded.
        $pdo = Database::connection();
        $pdo->beginTransaction();

        foreach ($items as $item) {
            if ($item['product_id'] !== null && !Product::decrementStock($item['product_id'], $item['quantity'])) {
                $pdo->rollBack();
                Session::flash('error', 'Stock insuffisant pour « ' . $item['description'] . ' ». Vente annulée.');
                redirect('/pos/' . $id);
            }
        }

        $clientId = input('client_id') !== '' && \App\Models\Client::find((int) input('client_id')) ? (int) input('client_id') : null;

        $saleId = PosSale::create([
            'pos_session_id' => (int) $id,
            'sale_number' => PosSale::nextNumber(),
            'client_id' => $clientId,
            'buyer_name' => input('buyer_name', ''),
            'buyer_email' => $buyerEmail,
            'payment_method' => $paymentMethod,
            'subtotal' => round($total, 2),
            'total' => round($total, 2),
            'status' => 'completed',
            'access_token' => bin2hex(random_bytes(32)),
            'created_by' => Auth::id(),
        ]);

        PosSale::insertItems($saleId, $items);
        $pdo->commit();

        ActivityLog::record('Vente en caisse', 'pos_sale', $saleId, round($total, 2) . ' EUR');

        if ($buyerEmail !== '' && filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            self::sendReceiptEmail($saleId);
        }

        redirect('/pos/sales/' . $saleId);
    }

    /** Staff-side confirmation screen after a sale: totals, QR code and link to hand the client their self-service ticket. */
    public static function saleShow(string $id): void
    {
        ModuleAccess::requireModule('pos');
        $sale = PosSale::find((int) $id);
        if (!$sale) { http_response_code(404); die('Vente introuvable.'); }

        View::render('pos/sale', [
            'title' => 'Vente ' . $sale['sale_number'],
            'sale' => $sale,
            'items' => PosSale::items((int) $id),
            'receiptUrl' => full_url('/pos-receipt/' . $sale['access_token']),
        ]);
    }

    public static function movement(string $id): void
    {
        ModuleAccess::requireModule('pos');
        Csrf::verifyOrFail();

        $session = PosSession::find((int) $id);
        if (!$session || $session['status'] !== 'open') {
            Session::flash('error', 'Cette session de caisse est fermée.');
            redirect('/pos');
        }

        $type = input('type') === 'out' ? 'out' : 'in';
        $amount = (float) str_replace(',', '.', input('amount', '0'));
        if ($amount <= 0) {
            Session::flash('error', 'Montant invalide.');
            redirect('/pos/' . $id);
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO pos_cash_movements (organization_id, pos_session_id, type, amount, reason, created_by) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([Auth::organizationId(), $id, $type, $amount, input('reason', ''), Auth::id()]);

        Session::flash('success', $type === 'in' ? 'Apport en caisse enregistré.' : 'Retrait de caisse enregistré.');
        redirect('/pos/' . $id);
    }

    public static function closeForm(string $id): void
    {
        ModuleAccess::requireModule('pos');
        $session = PosSession::findWithRelations((int) $id);
        if (!$session || $session['status'] !== 'open') { http_response_code(404); die('Session introuvable ou déjà fermée.'); }

        View::render('pos/close', [
            'title' => 'Clôturer la caisse',
            'session' => $session,
            'expectedCash' => PosSession::expectedCash((int) $id),
            'totalsByMethod' => PosSession::totalsByPaymentMethod((int) $id),
        ]);
    }

    public static function close(string $id): void
    {
        ModuleAccess::requireModule('pos');
        Csrf::verifyOrFail();

        $session = PosSession::find((int) $id);
        if (!$session || $session['status'] !== 'open') {
            Session::flash('error', 'Session introuvable ou déjà fermée.');
            redirect('/pos');
        }

        $counted = (float) str_replace(',', '.', input('counted_cash', '0'));
        $expected = PosSession::expectedCash((int) $id);

        PosSession::update((int) $id, [
            'status' => 'closed',
            'counted_cash' => $counted,
            'cash_difference' => round($counted - $expected, 2),
            'closed_by' => Auth::id(),
            'closed_at' => date('Y-m-d H:i:s'),
            'notes' => input('notes', ''),
        ]);

        ActivityLog::record('Clôture de caisse', 'pos_session', (int) $id, 'Écart ' . round($counted - $expected, 2) . ' EUR');
        Session::flash('success', 'Caisse clôturée.');
        redirect('/pos/sessions/' . $id);
    }

    public static function sessions(): void
    {
        ModuleAccess::requireModule('pos');
        View::render('pos/sessions', ['title' => 'Historique des caisses', 'sessions' => PosSession::allWithRelations()]);
    }

    public static function showSession(string $id): void
    {
        ModuleAccess::requireModule('pos');
        $session = PosSession::findWithRelations((int) $id);
        if (!$session) { http_response_code(404); die('Session introuvable.'); }

        View::render('pos/session_show', [
            'title' => 'Session de caisse #' . $session['id'],
            'session' => $session,
            'sales' => PosSale::forSession((int) $id),
            'movements' => PosSession::movements((int) $id),
            'totalsByMethod' => PosSession::totalsByPaymentMethod((int) $id),
        ]);
    }

    public static function salePdf(string $id): void
    {
        ModuleAccess::requireModule('pos');
        $sale = PosSale::find((int) $id);
        if (!$sale) { http_response_code(404); die('Vente introuvable.'); }

        self::streamReceiptPdf($sale, PosSale::items((int) $id), CompanySettings::get());
    }

    /** $company is passed in rather than fetched here: the public token route has no Auth session to scope CompanySettings::get() with. */
    public static function streamReceiptPdf(array $sale, array $items, array $company): void
    {
        $pdf = \App\Core\PosReceiptPdf::build($sale, $items, $company, full_url('/pos-receipt/' . $sale['access_token']));

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ticket-' . $sale['sale_number'] . '.pdf"');
        echo $pdf;
    }

    private static function sendReceiptEmail(int $saleId): void
    {
        $sale = PosSale::find($saleId);
        $items = PosSale::items($saleId);
        $company = CompanySettings::get();
        $link = full_url('/pos-receipt/' . $sale['access_token']);

        $html = '<p>Bonjour,</p>'
            . '<p>Merci pour votre achat' . ($sale['buyer_name'] ? ' ' . View::e($sale['buyer_name']) : '') . '. Voici votre ticket de caisse <strong>' . View::e($sale['sale_number']) . '</strong> (' . View::money((float) $sale['total']) . ').</p>'
            . '<p style="margin:24px 0;"><a href="' . View::e($link) . '" style="background-color:#14213d; color:#ffffff; padding:12px 22px; text-decoration:none; font-family:Helvetica,Arial,sans-serif; font-size:14px;">Voir et télécharger mon ticket</a></p>'
            . '<p class="text-muted small">Vous pouvez aussi présenter cet email ou le QR code remis en caisse pour accéder à votre ticket depuis votre téléphone.</p>';

        try {
            Mailer::send($sale['buyer_email'], 'Votre ticket de caisse ' . $sale['sale_number'], $html);
        } catch (\RuntimeException $e) {
            Session::flash('error', "La vente a été enregistrée mais l'email n'a pas pu être envoyé : " . $e->getMessage());
        }
    }
}
