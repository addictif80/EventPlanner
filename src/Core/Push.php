<?php

namespace App\Core;

use App\Models\SystemSetting;

/**
 * Web Push (RFC 8291 aes128gcm content-encoding + RFC 8292 VAPID), hand-rolled
 * with PHP's openssl extension — this project uses no Composer packages (see
 * StripeClient), so there is no minishlink/web-push here. Every call is
 * best-effort: a failure here (unreachable push service, expired
 * subscription, misconfigured keys) is logged and swallowed, never thrown at
 * the caller — the in-app notification (already written to the DB by
 * Notification::to...()) is the source of truth regardless of whether the
 * push itself succeeds.
 */
class Push
{
    public static function vapidKeys(): array
    {
        $settings = SystemSetting::get();
        if (!empty($settings['vapid_public_key']) && !empty($settings['vapid_private_key'])) {
            return ['public' => $settings['vapid_public_key'], 'private' => $settings['vapid_private_key']];
        }

        $keyResource = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        if ($keyResource === false) {
            return ['public' => '', 'private' => ''];
        }

        $details = openssl_pkey_get_details($keyResource);
        $public = "\x04" . $details['ec']['x'] . $details['ec']['y'];
        $private = $details['ec']['d'];

        $keys = ['public' => self::base64url($public), 'private' => self::base64url($private)];
        SystemSetting::update(['vapid_public_key' => $keys['public'], 'vapid_private_key' => $keys['private']]);

        return $keys;
    }

    public static function sendToUser(int $userId, string $title, string $body, string $link = ''): void
    {
        self::sendToSubscriptions('user_id', $userId, $title, $body, $link);
    }

    public static function sendToClient(int $clientId, string $title, string $body, string $link = ''): void
    {
        self::sendToSubscriptions('client_id', $clientId, $title, $body, $link);
    }

    public static function sendToPlatform(string $title, string $body, string $link = ''): void
    {
        try {
            $stmt = Database::connection()->prepare('SELECT * FROM push_subscriptions WHERE is_platform = 1');
            $stmt->execute();
            foreach ($stmt->fetchAll() as $sub) {
                self::send($sub, $title, $body, $link);
            }
        } catch (\Throwable $e) {
            error_log('Push::sendToPlatform: ' . $e->getMessage());
        }
    }

    private static function sendToSubscriptions(string $column, int $id, string $title, string $body, string $link): void
    {
        try {
            $stmt = Database::connection()->prepare("SELECT * FROM push_subscriptions WHERE {$column} = ?");
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll() as $sub) {
                self::send($sub, $title, $body, $link);
            }
        } catch (\Throwable $e) {
            error_log('Push::sendToSubscriptions: ' . $e->getMessage());
        }
    }

    public static function subscribe(?int $userId, ?int $clientId, bool $isPlatform, string $endpoint, string $p256dh, string $auth): void
    {
        Database::connection()->prepare(
            'INSERT INTO push_subscriptions (user_id, client_id, is_platform, endpoint, p256dh, auth)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), client_id = VALUES(client_id), is_platform = VALUES(is_platform), p256dh = VALUES(p256dh), auth = VALUES(auth)'
        )->execute([$userId, $clientId, $isPlatform ? 1 : 0, $endpoint, $p256dh, $auth]);
    }

    public static function unsubscribe(string $endpoint): void
    {
        Database::connection()->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?')->execute([$endpoint]);
    }

    private static function send(array $subscription, string $title, string $body, string $link): void
    {
        try {
            $payload = json_encode(['title' => $title, 'body' => $body, 'link' => $link], JSON_UNESCAPED_UNICODE);
            $encrypted = self::encrypt($payload, $subscription['p256dh'], $subscription['auth']);

            $endpoint = $subscription['endpoint'];
            $origin = self::originOf($endpoint);
            $vapid = self::vapidKeys();
            $jwt = self::vapidJwt($origin, $vapid);

            $headers = [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'TTL: 86400',
                'Authorization: vapid t=' . $jwt . ', k=' . $vapid['public'],
            ];

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $encrypted,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 404/410: the browser unsubscribed or the subscription expired.
            if (in_array($status, [404, 410], true)) {
                self::unsubscribe($endpoint);
            }
        } catch (\Throwable $e) {
            error_log('Push::send: ' . $e->getMessage());
        }
    }

    /** RFC 8291: encrypts $payload for a single subscription, returns the aes128gcm body ready to POST. */
    private static function encrypt(string $payload, string $p256dhB64, string $authB64): string
    {
        $clientPublicKey = self::base64urlDecode($p256dhB64);
        $authSecret = self::base64urlDecode($authB64);

        $serverKeyResource = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $serverDetails = openssl_pkey_get_details($serverKeyResource);
        $serverPublicKey = "\x04" . $serverDetails['ec']['x'] . $serverDetails['ec']['y'];

        $sharedSecret = self::ecdhSharedSecret($serverKeyResource, $clientPublicKey);

        $authInfo = "WebPush: info\x00" . $clientPublicKey . $serverPublicKey;
        $prkKey = hash_hmac('sha256', $sharedSecret, $authSecret, true);
        $ikm = substr(hash_hmac('sha256', $authInfo . "\x01", $prkKey, true), 0, 32);

        $salt = random_bytes(16);
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $cek = substr(hash_hmac('sha256', "Content-Encoding: aes128gcm\x00\x01", $prk, true), 0, 16);
        $nonce = substr(hash_hmac('sha256', "Content-Encoding: nonce\x00\x01", $prk, true), 0, 12);

        // Single record: append the 0x02 delimiter (RFC 8188 §2), no padding.
        $tag = '';
        $ciphertext = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        $header = $salt
            . pack('N', 4096)
            . pack('C', strlen($serverPublicKey))
            . $serverPublicKey;

        return $header . $ciphertext . $tag;
    }

    private static function ecdhSharedSecret($privateKeyResource, string $peerPublicKeyUncompressed): string
    {
        $peerPublicKeyPem = self::uncompressedPointToPem($peerPublicKeyUncompressed);
        $peerPublicKey = openssl_pkey_get_public($peerPublicKeyPem);

        $secret = openssl_pkey_derive($peerPublicKey, $privateKeyResource);
        if ($secret === false) {
            throw new \RuntimeException('Échec du calcul ECDH (openssl_pkey_derive).');
        }

        return $secret;
    }

    private static function uncompressedPointToPem(string $point): string
    {
        // Build a minimal SubjectPublicKeyInfo DER wrapper for a P-256 point so openssl_pkey_get_public() accepts it.
        $oidEcPublicKey = hex2bin('06072a8648ce3d0201');
        $oidPrime256v1 = hex2bin('06082a8648ce3d030107');
        $algorithm = self::derSequence($oidEcPublicKey . $oidPrime256v1);
        $subjectPublicKey = self::derBitString($point);
        $spki = self::derSequence($algorithm . $subjectPublicKey);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }
        $bytes = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private static function derSequence(string $content): string
    {
        return "\x30" . self::derLength(strlen($content)) . $content;
    }

    private static function derBitString(string $content): string
    {
        $withUnusedBits = "\x00" . $content;
        return "\x03" . self::derLength(strlen($withUnusedBits)) . $withUnusedBits;
    }

    private static function vapidJwt(string $audience, array $vapidKeys): string
    {
        $header = self::base64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = self::base64url(json_encode([
            'aud' => $audience,
            'exp' => time() + 12 * 3600,
            'sub' => 'mailto:contact@abhd.fr',
        ]));
        $unsigned = $header . '.' . $payload;

        $privateKeyPem = self::rawEcPrivateKeyToPem($vapidKeys['private']);
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        $derSignature = '';
        openssl_sign($unsigned, $derSignature, $privateKey, OPENSSL_ALGO_SHA256);
        $rawSignature = self::derSignatureToRaw($derSignature);

        return $unsigned . '.' . self::base64url($rawSignature);
    }

    private static function rawEcPrivateKeyToPem(string $privateKeyB64): string
    {
        $d = self::base64urlDecode($privateKeyB64);
        $d = str_pad($d, 32, "\x00", STR_PAD_LEFT);

        // Minimal SEC1 ECPrivateKey DER (no public key field needed by openssl).
        // P-256 OID is 1.2.840.10045.3.1.7 — the [0] EXPLICIT parameters field.
        $version = "\x02\x01\x01";
        $privateKeyOctet = "\x04" . chr(strlen($d)) . $d;
        $p256Oid = hex2bin('06082a8648ce3d030107');
        $parameters = "\xa0" . self::derLength(strlen($p256Oid)) . $p256Oid;

        $ecPrivateKey = self::derSequence($version . $privateKeyOctet . $parameters);

        $oidEcPublicKey = hex2bin('06072a8648ce3d0201');
        $oidPrime256v1 = hex2bin('06082a8648ce3d030107');
        $algorithm = self::derSequence($oidEcPublicKey . $oidPrime256v1);
        $privateKeyInfo = self::derSequence("\x02\x01\x00" . $algorithm . self::derOctetString($ecPrivateKey));

        return "-----BEGIN PRIVATE KEY-----\n" . chunk_split(base64_encode($privateKeyInfo), 64, "\n") . "-----END PRIVATE KEY-----\n";
    }

    private static function derOctetString(string $content): string
    {
        return "\x04" . self::derLength(strlen($content)) . $content;
    }

    private static function derSignatureToRaw(string $der): string
    {
        // DER SEQUENCE { INTEGER r, INTEGER s } -> raw r(32) || s(32), left-padded/truncated as needed (JWS ES256 format).
        $offset = 2; // skip SEQUENCE tag + length
        $r = self::derReadInteger($der, $offset);
        $s = self::derReadInteger($der, $offset);

        return str_pad($r, 32, "\x00", STR_PAD_LEFT) . str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    private static function derReadInteger(string $der, int &$offset): string
    {
        $offset++; // INTEGER tag (0x02)
        $len = ord($der[$offset]);
        $offset++;
        $value = substr($der, $offset, $len);
        $offset += $len;
        return ltrim($value, "\x00");
    }

    private static function originOf(string $url): string
    {
        $parts = parse_url($url);
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    public static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64urlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $padded = str_pad($data, strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + (4 - strlen($data) % 4), '=');
        return base64_decode($padded);
    }
}
