<?php

namespace App\Core;

/**
 * Minimal dependency-free SMTP client (supports SSL/TLS/STARTTLS + AUTH LOGIN/PLAIN).
 * Written from scratch so the app has zero Composer dependencies and can be
 * dropped onto shared hosting (CyberPanel) without a build step.
 */
class SmtpClient
{
    private $socket;
    private array $config;
    private array $debugLog = [];

    public function __construct(array $config)
    {
        // Expected keys: host, port, encryption(none|ssl|tls), username, password, timeout
        $this->config = array_merge([
            'timeout' => 15,
        ], $config);
    }

    public function getLog(): array
    {
        return $this->debugLog;
    }

    /**
     * @param string $fromEmail
     * @param string $fromName
     * @param array $to  list of recipient emails
     * @param string $subject
     * @param string $htmlBody
     * @param string|null $textBody
     * @param array<int,array{filename:string,mimeType:string,content:string}> $attachments
     * @throws \RuntimeException on failure
     */
    public function send(string $fromEmail, string $fromName, array $to, string $subject, string $htmlBody, ?string $textBody = null, array $attachments = []): void
    {
        $host = $this->config['host'];
        $port = (int) $this->config['port'];
        $encryption = $this->config['encryption'] ?? 'tls';

        $transportHost = $encryption === 'ssl' ? "ssl://{$host}" : $host;

        $this->socket = @fsockopen($transportHost, $port, $errno, $errstr, (int) $this->config['timeout']);
        if (!$this->socket) {
            throw new \RuntimeException("Connexion SMTP impossible à {$host}:{$port} ({$errstr})");
        }

        $this->expect(220, 'Connexion');
        $this->command('EHLO ' . $this->localHost(), 250);

        if ($encryption === 'tls') {
            $this->command('STARTTLS', 220);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('Impossible de démarrer le chiffrement TLS (STARTTLS).');
            }
            $this->command('EHLO ' . $this->localHost(), 250);
        }

        if (!empty($this->config['username'])) {
            $this->command('AUTH LOGIN', 334);
            $this->command(base64_encode($this->config['username']), 334);
            $this->command(base64_encode((string) $this->config['password']), 235);
        }

        $this->command('MAIL FROM:<' . $fromEmail . '>', 250);
        foreach ($to as $recipient) {
            $this->command('RCPT TO:<' . $recipient . '>', [250, 251]);
        }

        $this->command('DATA', 354);

        $altBoundary = 'evtplanner-alt-' . bin2hex(random_bytes(8));
        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'To: ' . implode(', ', $to);
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'X-Mailer: EventPlanner';

        // View templates and heredocs use bare "\n" line endings; SMTP (and the RFC 5321
        // 1000-octet line limit enforced by strict servers) requires "\r\n" throughout,
        // otherwise a long HTML document arrives as one gigantic "line" and gets rejected.
        $text = $this->crlf($textBody ?? trim(strip_tags($htmlBody)));
        $htmlBody = $this->crlf($htmlBody);

        $altPart = "--{$altBoundary}\r\n";
        $altPart .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
        $altPart .= $this->stuff($text) . "\r\n";
        $altPart .= "--{$altBoundary}\r\n";
        $altPart .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
        $altPart .= $this->stuff($htmlBody) . "\r\n";
        $altPart .= "--{$altBoundary}--\r\n";

        if (empty($attachments)) {
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"';
            $body = $altPart;
        } else {
            $mixedBoundary = 'evtplanner-mix-' . bin2hex(random_bytes(8));
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"';

            $body = "--{$mixedBoundary}\r\n";
            $body .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";
            $body .= $altPart . "\r\n";

            foreach ($attachments as $attachment) {
                $body .= "--{$mixedBoundary}\r\n";
                $body .= 'Content-Type: ' . $attachment['mimeType'] . '; name="' . $this->encodeHeader($attachment['filename']) . "\"\r\n";
                $body .= 'Content-Disposition: attachment; filename="' . $this->encodeHeader($attachment['filename']) . "\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= chunk_split(base64_encode($attachment['content']));
                $body .= "\r\n";
            }

            $body .= "--{$mixedBoundary}--\r\n";
        }

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";

        $this->command($message, 250);
        $this->command('QUIT', 221);

        fclose($this->socket);
    }

    private function stuff(string $data): string
    {
        // RFC 5321 dot-stuffing for lines starting with a period.
        return preg_replace('/^\./m', '..', $data);
    }

    /** Normalizes all line endings to CRLF, as SMTP DATA content requires. */
    private function crlf(string $data): string
    {
        return preg_replace('/\r\n|\r|\n/', "\r\n", $data);
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private function localHost(): string
    {
        return $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    private function command(string $command, $expectedCodes): string
    {
        fwrite($this->socket, $command . "\r\n");
        $this->debugLog[] = '> ' . (str_starts_with($command, 'AUTH') || strlen($command) > 200 ? '[hidden]' : $command);
        return $this->expect($expectedCodes, $command);
    }

    private function expect($expectedCodes, string $context): string
    {
        $expectedCodes = is_array($expectedCodes) ? $expectedCodes : [$expectedCodes];
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $this->debugLog[] = '< ' . trim($response);

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            if ($this->socket) {
                fclose($this->socket);
            }
            throw new \RuntimeException("Erreur SMTP lors de « {$context} » : {$response}");
        }

        return $response;
    }
}
