<?php

namespace App\Core;

use App\Models\SystemSetting;

/**
 * Platform-level Stripe Subscriptions integration: bills organizations for
 * their plan + module add-ons on the PLATFORM's own Stripe account
 * (system_settings.stripe_secret_key). Entirely separate from
 * StripeController, which lets each organization collect payment on its own
 * client invoices using its own Stripe account (company_settings.stripe_secret_key).
 */
class StripeBilling
{
    public static function isConfigured(): bool
    {
        $key = SystemSetting::get()['stripe_secret_key'] ?? '';
        return $key !== '';
    }

    private static function secretKey(): string
    {
        $key = SystemSetting::get()['stripe_secret_key'] ?? '';
        if ($key === '') {
            throw new \RuntimeException("Le paiement des abonnements n'est pas configuré (clé Stripe plateforme manquante).");
        }
        return $key;
    }

    /**
     * Creates a Stripe Product/Price for a plan, module or package if it
     * doesn't have one yet, or if its price changed since the last save
     * (Stripe prices are immutable, so a price change creates a new Price
     * object under the same Product and archives the old one).
     *
     * @return array{stripe_product_id: string, stripe_price_id: string}
     */
    public static function syncPrice(string $name, float $monthlyPrice, ?string $productId, ?string $priceId, ?float $previousMonthlyPrice): array
    {
        if ($monthlyPrice <= 0) {
            return ['stripe_product_id' => $productId ?? '', 'stripe_price_id' => ''];
        }

        $secretKey = self::secretKey();

        if (!$productId) {
            $product = StripeClient::request($secretKey, 'products', ['name' => $name]);
            $productId = $product['id'] ?? null;
            if (!$productId) {
                throw new \RuntimeException('Erreur Stripe : ' . ($product['error']['message'] ?? 'création du produit impossible.'));
            }
        } else {
            StripeClient::request($secretKey, 'products/' . urlencode($productId), ['name' => $name]);
        }

        $needsNewPrice = !$priceId || $previousMonthlyPrice === null || abs($previousMonthlyPrice - $monthlyPrice) > 0.001;

        if ($needsNewPrice) {
            if ($priceId) {
                StripeClient::request($secretKey, 'prices/' . urlencode($priceId), ['active' => 'false']);
            }
            $price = StripeClient::request($secretKey, 'prices', [
                'product' => $productId,
                'currency' => 'eur',
                'unit_amount' => (int) round($monthlyPrice * 100),
                'recurring' => ['interval' => 'month'],
            ]);
            $priceId = $price['id'] ?? null;
            if (!$priceId) {
                throw new \RuntimeException('Erreur Stripe : ' . ($price['error']['message'] ?? 'création du prix impossible.'));
            }
        }

        return ['stripe_product_id' => $productId, 'stripe_price_id' => $priceId];
    }

    public static function createCheckoutSession(array $lineItemPriceIds, string $successUrl, string $cancelUrl, array $metadata, ?string $customerId, ?string $customerEmail): array
    {
        $params = [
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'line_items' => array_map(fn($priceId) => ['price' => $priceId, 'quantity' => 1], $lineItemPriceIds),
        ];

        if ($customerId) {
            $params['customer'] = $customerId;
        } elseif ($customerEmail) {
            $params['customer_email'] = $customerEmail;
        }

        return StripeClient::request(self::secretKey(), 'checkout/sessions', $params);
    }

    public static function retrieveCheckoutSession(string $sessionId): array
    {
        return StripeClient::request(self::secretKey(), 'checkout/sessions/' . urlencode($sessionId), ['expand' => ['subscription']], 'GET');
    }

    public static function retrieveSubscription(string $subscriptionId): array
    {
        return StripeClient::request(self::secretKey(), 'subscriptions/' . urlencode($subscriptionId), null, 'GET');
    }

    public static function addSubscriptionItem(string $subscriptionId, string $priceId): array
    {
        return StripeClient::request(self::secretKey(), 'subscription_items', [
            'subscription' => $subscriptionId,
            'price' => $priceId,
            'quantity' => 1,
        ]);
    }

    public static function removeSubscriptionItem(string $subscriptionItemId): array
    {
        return StripeClient::request(self::secretKey(), 'subscription_items/' . urlencode($subscriptionItemId), null, 'DELETE');
    }

    public static function updateSubscriptionItemPrice(string $subscriptionItemId, string $newPriceId): array
    {
        return StripeClient::request(self::secretKey(), 'subscription_items/' . urlencode($subscriptionItemId), [
            'price' => $newPriceId,
            'proration_behavior' => 'always_invoice',
        ]);
    }

    public static function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): array
    {
        if ($atPeriodEnd) {
            return StripeClient::request(self::secretKey(), 'subscriptions/' . urlencode($subscriptionId), ['cancel_at_period_end' => 'true']);
        }
        return StripeClient::request(self::secretKey(), 'subscriptions/' . urlencode($subscriptionId), null, 'DELETE');
    }

    /**
     * Verifies a Stripe webhook payload's signature without the Stripe SDK.
     * See https://stripe.com/docs/webhooks/signatures — v1 scheme.
     */
    public static function verifyWebhookSignature(string $payload, string $sigHeader, string $webhookSecret): bool
    {
        if ($webhookSecret === '' || $sigHeader === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $sigHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $parts[$key][] = $value;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];
        if (!$timestamp || empty($signatures)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $webhookSecret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
