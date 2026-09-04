<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ModuleAccess;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\Event;

class SurveyController
{
    public static function send(string $eventId): void
    {
        ModuleAccess::requireModule('satisfaction_survey');
        Csrf::verifyOrFail();
        $event = Event::find((int) $eventId);
        if (!$event) { http_response_code(404); die('Événement introuvable.'); }

        $stmt = Database::connection()->prepare('SELECT * FROM clients WHERE id = ? AND organization_id = ?');
        $stmt->execute([$event['client_id'], Auth::organizationId()]);
        $client = $stmt->fetch();

        if (!$client || empty($client['email'])) {
            Session::flash('error', "Le client n'a pas d'adresse email renseignée.");
            redirect('/events/' . $eventId);
        }

        $token = bin2hex(random_bytes(24));
        $stmt = Database::connection()->prepare(
            'INSERT INTO satisfaction_surveys (organization_id, event_id, client_id, token, sent_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([Auth::organizationId(), $eventId, $client['id'], $token]);

        $link = (($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('/survey/' . $token));
        $html = '<p>Bonjour,</p><p>Merci d\'avoir fait confiance à notre équipe pour l\'organisation de « ' . View::e($event['title']) . ' ».</p>'
            . '<p>Votre avis compte beaucoup pour nous : <a href="' . View::e($link) . '">merci de répondre à notre court sondage de satisfaction</a>.</p>';

        try {
            Mailer::send($client['email'], 'Votre avis sur ' . $event['title'], $html);
            Session::flash('success', 'Sondage de satisfaction envoyé.');
        } catch (\RuntimeException $e) {
            Session::flash('error', "Échec de l'envoi : " . $e->getMessage());
        }

        redirect('/events/' . $eventId);
    }

    public static function form(string $token): void
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, e.title AS event_title FROM satisfaction_surveys s JOIN events e ON e.id = s.event_id WHERE s.token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        $survey = $stmt->fetch();

        if (!$survey) { http_response_code(404); die('Lien invalide.'); }
        if ($survey['submitted_at']) {
            View::render('survey/thanks', [], layout: null);
            return;
        }

        View::render('survey/form', ['survey' => $survey], layout: null);
    }

    public static function submit(string $token): void
    {
        $stmt = Database::connection()->prepare('SELECT * FROM satisfaction_surveys WHERE token = ?');
        $stmt->execute([$token]);
        $survey = $stmt->fetch();

        if (!$survey) { http_response_code(404); die('Lien invalide.'); }

        $stmt = Database::connection()->prepare(
            'UPDATE satisfaction_surveys SET rating = ?, comments = ?, consent_public = ?, submitted_at = NOW() WHERE id = ?'
        );
        $stmt->execute([(int) input('rating', 5), input('comments', ''), input('consent_public') ? 1 : 0, $survey['id']]);

        View::render('survey/thanks', [], layout: null);
    }

    /** Org-side list of every survey response, with the publish toggle for the public directory. */
    public static function index(): void
    {
        ModuleAccess::requireModule('satisfaction_survey');
        $stmt = Database::connection()->prepare(
            "SELECT s.*, e.title AS event_title, c.first_name, c.last_name, c.company_name
             FROM satisfaction_surveys s
             JOIN events e ON e.id = s.event_id
             JOIN clients c ON c.id = s.client_id
             WHERE s.organization_id = ? AND s.submitted_at IS NOT NULL
             ORDER BY s.submitted_at DESC"
        );
        $stmt->execute([Auth::organizationId()]);

        View::render('survey/index', ['title' => 'Avis clients', 'surveys' => $stmt->fetchAll()]);
    }

    /** Only reviews the client explicitly consented to can ever be published — the org merely curates among those. */
    public static function togglePublish(string $id): void
    {
        ModuleAccess::requireModule('satisfaction_survey');
        Csrf::verifyOrFail();

        $stmt = Database::connection()->prepare('SELECT consent_public, is_published FROM satisfaction_surveys WHERE id = ? AND organization_id = ?');
        $stmt->execute([$id, Auth::organizationId()]);
        $survey = $stmt->fetch();

        if ($survey && $survey['consent_public']) {
            Database::connection()->prepare('UPDATE satisfaction_surveys SET is_published = ? WHERE id = ? AND organization_id = ?')
                ->execute([$survey['is_published'] ? 0 : 1, $id, Auth::organizationId()]);
        } else {
            Session::flash('error', "Cet avis ne peut pas être publié : le client n'a pas donné son consentement.");
        }

        redirect('/surveys');
    }
}
