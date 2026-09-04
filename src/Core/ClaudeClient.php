<?php

namespace App\Core;

use App\Models\CompanySettings;
use App\Models\SystemSetting;

/**
 * Minimal, dependency-free client for the Claude API (raw cURL — this
 * codebase has no Composer/vendor dir, so the official Anthropic SDK isn't
 * an option here). Each organization can bring its own Anthropic API key
 * (Paramètres > Intégrations), billed on its own Anthropic account; if it
 * hasn't set one, falls back to the platform's own key (Administration >
 * Paramètres système), when the platform has chosen to fund usage itself —
 * the same own-first/system-fallback pattern as Mailer::send()'s SMTP.
 */
class ClaudeClient
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const MODEL = 'claude-opus-5';

    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    private static function apiKey(): string
    {
        $orgKey = CompanySettings::get()['anthropic_api_key'] ?? '';
        if ($orgKey !== '') {
            return $orgKey;
        }
        return SystemSetting::get()['anthropic_api_key'] ?? '';
    }

    /**
     * Plain-text completion (e.g. drafting an email paragraph).
     *
     * @throws \RuntimeException if unconfigured or the request fails
     */
    public static function complete(string $systemPrompt, string $userPrompt, int $maxTokens = 1024, string $effort = 'medium'): string
    {
        $response = self::request([
            'model' => self::MODEL,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'output_config' => ['effort' => $effort],
            'messages' => [['role' => 'user', 'content' => $userPrompt]],
        ]);

        $text = '';
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }
        return trim($text);
    }

    /**
     * JSON-structured completion, constrained to $schema via structured
     * outputs (output_config.format) so the result always parses.
     *
     * @throws \RuntimeException if unconfigured, the request fails, or the response isn't valid JSON
     */
    public static function completeJson(string $systemPrompt, string $userPrompt, array $schema, int $maxTokens = 2048, string $effort = 'medium'): array
    {
        $response = self::request([
            'model' => self::MODEL,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'output_config' => [
                'effort' => $effort,
                'format' => ['type' => 'json_schema', 'schema' => $schema],
            ],
            'messages' => [['role' => 'user', 'content' => $userPrompt]],
        ]);

        $text = '';
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        $data = json_decode($text, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Réponse de l'IA invalide.");
        }
        return $data;
    }

    private static function request(array $payload): array
    {
        $apiKey = self::apiKey();
        if ($apiKey === '') {
            throw new \RuntimeException("L'assistant IA n'est pas configuré. Renseignez une clé API Anthropic dans Paramètres > Intégrations.");
        }

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: ' . self::API_VERSION,
            ],
            CURLOPT_TIMEOUT => 45,
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new \RuntimeException("Impossible de contacter l'assistant IA : {$error}");
        }

        $decoded = json_decode((string) $body, true);

        if ($status !== 200) {
            $message = $decoded['error']['message'] ?? ('HTTP ' . $status);
            throw new \RuntimeException("L'assistant IA a renvoyé une erreur : {$message}");
        }

        if (($decoded['stop_reason'] ?? null) === 'refusal') {
            throw new \RuntimeException("L'assistant IA n'a pas pu traiter cette demande.");
        }

        return $decoded;
    }
}
