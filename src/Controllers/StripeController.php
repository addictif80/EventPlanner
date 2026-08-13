<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\CompanySettings;
use App\Models\Invoice;
use App\Models\Payment;

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

    public static function handleReturn(string $id): void
    {
        $invoice = Invoice::find((int) $id);
        $company = CompanySettings::get();
        $sessionId = $_GET['session_id'] ?? '';

        if (!$invoice || empty($company['stripe_secret_key']) || $sessionId === '') {
            redirect('/invoices/' . $id);
        }

        try {
            $session = self::request($company['stripe_secret_key'], 'checkout/sessions/' . urlencode($sessionId), null, 'GET');

            if (($session['payment_status'] ?? '') === 'paid') {
                $stmt = Database::connection()->prepare('SELECT id FROM payments WHERE reference = ?');
                $stmt->execute([$sessionId]);

                if (!$stmt->fetch()) {
                    Payment::create([
                        'invoice_id' => (int) $id,
                        'amount' => ($session['amount_total'] ?? 0) / 100,
                        'payment_date' => date('Y-m-d'),
                        'method' => 'cb',
                        'reference' => $sessionId,
                        'notes' => 'Paiement en ligne Stripe',
                    ]);
                    Invoice::recalculatePaidStatus((int) $id);
                }

                Session::flash('success', 'Paiement en ligne confirmé, merci !');
            } else {
                Session::flash('error', 'Le paiement n\'a pas pu être confirmé.');
            }
        } catch (\RuntimeException $e) {
            Session::flash('error', 'Erreur Stripe : ' . $e->getMessage());
        }

        redirect('/invoices/' . $id);
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
