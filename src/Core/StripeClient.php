<?php

namespace App\Core;

/**
 * Minimal cURL wrapper around the Stripe REST API (no Composer SDK), shared
 * by the per-organization integration (StripeController, client invoice
 * payments) and the platform-level one (StripeBilling, paid subscriptions).
 */
class StripeClient
{
    public static function request(string $secretKey, string $path, ?array $params = null, string $method = 'POST'): array
    {
        $url = 'https://api.stripe.com/v1/' . $path;
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
            $params = null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $secretKey . ':',
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if (in_array($method, ['POST', 'DELETE'], true) && $params !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
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
