<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Models\CompanySettings;
use App\Models\Invoice;

/**
 * Minimal Stripe Checkout integration using cURL against the REST API directly
 * (no Composer SDK), gated behind the merchant's own Stripe secret key set in
 * Paramètres > Intégrations.
 */
class StripeController
{
    public static function createPaymentLink(string $id): void
    {
        Csrf::verifyOrFail();

        $invoice = Invoice::find((int) $id);
        $company = CompanySettings::get();

        if (!$invoice || empty($company['stripe_secret_key'])) {
            Session::flash('error', 'Stripe n\'est pas configuré.');
            redirect('/invoices/' . $id);
        }

        $remaining = (float) $invoice['total'] - (float) $invoice['amount_paid'];
        if ($remaining <= 0) {
            Session::flash('error', 'Cette facture est déjà réglée.');
            redirect('/invoices/' . $id);
        }

        $successUrl = (($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('/stripe/return/' . $id)) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = (($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('/invoices/' . $id));

        $params = [
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'invoice_id' => (string) $id,
                'organization_id' => (string) Auth::organizationId(),
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($invoice['currency'] ?? $company['currency'] ?? 'eur'),
                    'unit_amount' => (int) round($remaining * 100),
                    'product_data' => ['name' => 'Facture ' . $invoice['invoice_number']],
                ],
            ]],
        ];

        try {
            $session = self::request($company['stripe_secret_key'], 'checkout/sessions', $params);
            if (!empty($session['url'])) {
                $_SESSION['stripe_payment_url_' . $id] = $session['url'];
                Session::flash('success', 'Lien de paiement généré.');
            } else {
                Session::flash('error', 'Réponse Stripe invalide : ' . ($session['error']['message'] ?? 'erreur inconnue'));
            }
        } catch (\RuntimeException $e) {
            Session::flash('error', 'Erreur Stripe : ' . $e->getMessage());
        }

        redirect('/invoices/' . $id);
    }

    /**
     * Public, unauthenticated: Stripe redirects the paying customer's browser
     * here after checkout, so there is no Auth session. The organization and
     * invoice are instead re-derived from the Stripe session's own metadata
     * (set at creation time in createPaymentLink), which only Stripe can
     * authoritatively confirm as paid — that confirmation is what authorizes
     * the write below, not the URL's invoice id.
     */
    public static function handleReturn(string $id): void
    {
        $sessionId = $_GET['session_id'] ?? '';
        if ($sessionId === '') {
            redirect('/login');
        }

        $stmt = Database::connection()->prepare('SELECT organization_id FROM invoices WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $invoiceRow = $stmt->fetch();
        if (!$invoiceRow) {
            redirect('/login');
        }

        $stmt = Database::connection()->prepare('SELECT stripe_secret_key FROM company_settings WHERE organization_id = ?');
        $stmt->execute([$invoiceRow['organization_id']]);
        $secretKey = $stmt->fetchColumn();
        if (!$secretKey) {
            redirect('/login');
        }

        try {
            $session = self::request($secretKey, 'checkout/sessions/' . urlencode($sessionId), null, 'GET');
            $metaInvoiceId = $session['metadata']['invoice_id'] ?? null;
            $metaOrgId = $session['metadata']['organization_id'] ?? null;

            if ((string) $metaInvoiceId !== (string) $id || (string) $metaOrgId !== (string) $invoiceRow['organization_id']) {
                die('Session de paiement invalide.');
            }

            if (($session['payment_status'] ?? '') === 'paid') {
                $stmt = Database::connection()->prepare('SELECT id FROM payments WHERE reference = ?');
                $stmt->execute([$sessionId]);

                if (!$stmt->fetch()) {
                    $stmt = Database::connection()->prepare(
                        'INSERT INTO payments (organization_id, invoice_id, amount, payment_date, method, reference, notes) VALUES (?, ?, ?, CURDATE(), "cb", ?, "Paiement en ligne Stripe")'
                    );
                    $stmt->execute([$invoiceRow['organization_id'], (int) $id, ($session['amount_total'] ?? 0) / 100, $sessionId]);

                    // Recompute the invoice's paid status directly (bypassing the
                    // Auth-scoped Invoice::recalculatePaidStatus(), unavailable here).
                    self::recalculatePaidStatusFor((int) $id, $invoiceRow['organization_id']);
                }

                echo '<p>Paiement confirmé, merci ! Vous pouvez fermer cette page.</p>';
            } else {
                echo '<p>Le paiement n\'a pas pu être confirmé.</p>';
            }
        } catch (\RuntimeException $e) {
            echo '<p>Erreur lors de la vérification du paiement : ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }

    private static function recalculatePaidStatusFor(int $invoiceId, int $organizationId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND organization_id = ?');
        $stmt->execute([$invoiceId, $organizationId]);
        $paid = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM credit_notes WHERE invoice_id = ? AND organization_id = ?');
        $stmt->execute([$invoiceId, $organizationId]);
        $paid += (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT total, status FROM invoices WHERE id = ? AND organization_id = ?');
        $stmt->execute([$invoiceId, $organizationId]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return;
        }

        $status = $invoice['status'];
        if ($status !== 'cancelled') {
            if ($paid >= (float) $invoice['total'] && (float) $invoice['total'] > 0) {
                $status = 'paid';
            } elseif ($paid > 0) {
                $status = 'partially_paid';
            }
        }

        $pdo->prepare('UPDATE invoices SET amount_paid = ?, status = ? WHERE id = ? AND organization_id = ?')
            ->execute([$paid, $status, $invoiceId, $organizationId]);
    }

    private static function request(string $secretKey, string $path, ?array $params, string $method = 'POST'): array
    {
        $ch = curl_init('https://api.stripe.com/v1/' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $secretKey . ':',
            CURLOPT_TIMEOUT => 20,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params ?? []));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException($error ?: 'Connexion à Stripe impossible.');
        }
        curl_close($ch);

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }
}
