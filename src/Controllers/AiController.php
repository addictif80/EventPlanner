<?php

namespace App\Controllers;

use App\Core\ClaudeClient;
use App\Core\Csrf;
use App\Core\ModuleAccess;
use App\Models\CompanySettings;
use App\Models\Event;
use App\Models\Invoice;

/**
 * Staff-side AI assistant endpoints (module "ai_assistant"): drafting quote
 * line items from a free-text brief, and drafting a payment reminder
 * paragraph — both return editable drafts, never send/save anything by
 * themselves (see quotes/form.php and invoices/show.php for the calling UI).
 */
class AiController
{
    private const QUOTE_ITEMS_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'items' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'description' => ['type' => 'string'],
                        'quantity' => ['type' => 'number'],
                        'unit_price' => ['type' => 'number'],
                    ],
                    'required' => ['description', 'quantity', 'unit_price'],
                    'additionalProperties' => false,
                ],
            ],
            'notes' => ['type' => 'string'],
        ],
        'required' => ['items', 'notes'],
        'additionalProperties' => false,
    ];

    public static function draftQuote(): void
    {
        ModuleAccess::requireModule('ai_assistant');
        Csrf::verifyOrFail();

        $brief = trim(input('brief', ''));
        if ($brief === '') {
            self::json(['error' => 'Merci de décrire le besoin du client.'], 422);
        }

        $company = CompanySettings::get();
        $context = 'Devise : ' . ($company['currency'] ?? 'EUR') . '.';

        $eventId = input('event_id', '');
        if ($eventId !== '') {
            $event = Event::findWithRelations((int) $eventId);
            if ($event) {
                $context .= " Événement : " . $event['title']
                    . ($event['type_name'] ? ' (' . $event['type_name'] . ')' : '')
                    . ($event['guests_count'] ? ', ' . $event['guests_count'] . ' invités' : '')
                    . ($event['venue_name'] ? ', lieu : ' . $event['venue_name'] : '') . '.';
            }
        }

        $system = "Tu es l'assistant d'un organisateur d'événements en France. À partir du besoin décrit par l'utilisateur, "
            . "propose une liste de lignes de devis réalistes (prestations, matériel, options) avec des quantités et des prix unitaires HT "
            . "plausibles pour le marché français. Sois concret et évite les lignes redondantes. Réponds uniquement avec les données demandées, "
            . "sans texte de présentation.";

        try {
            $data = ClaudeClient::completeJson($system, $context . " Besoin décrit par l'utilisateur : " . $brief, self::QUOTE_ITEMS_SCHEMA, 4096, 'medium');
        } catch (\RuntimeException $e) {
            self::json(['error' => $e->getMessage()], 502);
        }

        self::json($data);
    }

    public static function draftReminder(string $invoiceId): void
    {
        ModuleAccess::requireModule('ai_assistant');
        Csrf::verifyOrFail();

        $invoice = Invoice::findWithRelations((int) $invoiceId);
        if (!$invoice) {
            self::json(['error' => 'Facture introuvable.'], 404);
        }

        $daysOverdue = max(0, (int) floor((time() - strtotime($invoice['due_date'])) / 86400));
        $remaining = (float) $invoice['total'] - (float) $invoice['amount_paid'];
        $clientName = trim(($invoice['company_name'] ?: trim($invoice['first_name'] . ' ' . $invoice['last_name'])));

        $system = "Tu es l'assistant d'un organisateur d'événements en France qui rédige une relance de paiement à un client. "
            . "Rédige uniquement le paragraphe d'introduction de l'email (2 à 4 phrases), en français, poli mais ferme, sans formule de politesse "
            . "d'ouverture ni de signature (elles sont ajoutées séparément par le modèle d'email). Adapte le ton à l'ancienneté du retard : "
            . "courtois si récent, plus ferme au-delà de 30 jours. Ne mentionne pas de menace juridique. Réponds uniquement avec ce paragraphe, sans guillemets.";

        $userPrompt = "Client : {$clientName}. Facture {$invoice['invoice_number']}, échéance dépassée de {$daysOverdue} jour(s), "
            . 'montant restant dû : ' . number_format($remaining, 2, ',', ' ') . ' EUR.';

        try {
            $intro = ClaudeClient::complete($system, $userPrompt, 800, 'low');
        } catch (\RuntimeException $e) {
            self::json(['error' => $e->getMessage()], 502);
        }

        self::json(['intro' => $intro]);
    }

    private static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
