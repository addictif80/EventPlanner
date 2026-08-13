<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\Client;
use App\Models\CompanySettings;
use App\Models\Contract;
use App\Models\Event;

class ContractController
{
    public static function index(): void
    {
        View::render('contracts/index', ['title' => 'Contrats', 'contracts' => Contract::allWithClient()]);
    }

    public static function create(): void
    {
        $company = CompanySettings::get();
        $defaultContent = "Entre les soussignés,\n\n"
            . ($company['company_name'] ?: '[Votre entreprise]') . ", ci-après « le Prestataire »,\n\n"
            . "et le client désigné ci-dessous, ci-après « le Client »,\n\n"
            . "il a été convenu ce qui suit :\n\n"
            . "1. Objet du contrat\n...\n\n2. Prix et modalités de paiement\n...\n\n3. Conditions d'annulation\n...\n";

        View::render('contracts/form', [
            'title' => 'Nouveau contrat',
            'clients' => Client::all('created_at DESC'),
            'events' => Event::allWithClient(),
            'defaultContent' => $defaultContent,
            'selectedClientId' => $_GET['client_id'] ?? null,
            'selectedEventId' => $_GET['event_id'] ?? null,
        ]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        $id = Contract::create([
            'client_id' => (int) input('client_id'),
            'event_id' => input('event_id') !== '' ? (int) input('event_id') : null,
            'title' => input('title', 'Contrat de prestation'),
            'content' => input('content', ''),
            'status' => 'draft',
        ]);
        Session::flash('success', 'Contrat créé.');
        redirect('/contracts/' . $id);
    }

    public static function show(string $id): void
    {
        $contract = Contract::findWithRelations((int) $id);
        if (!$contract) { http_response_code(404); die('Contrat introuvable.'); }

        View::render('contracts/show', [
            'title' => $contract['title'],
            'contract' => $contract,
            'smtpConfigured' => Mailer::isConfigured(),
        ]);
    }

    public static function send(string $id): void
    {
        Csrf::verifyOrFail();
        $contract = Contract::findWithRelations((int) $id);
        if (!$contract) { http_response_code(404); die('Contrat introuvable.'); }

        if (empty($contract['client_email'])) {
            Session::flash('error', "Le client n'a pas d'adresse email renseignée.");
            redirect('/contracts/' . $id);
        }

        $token = bin2hex(random_bytes(24));
        Contract::update((int) $id, ['status' => 'sent', 'sign_token' => $token, 'sent_at' => date('Y-m-d H:i:s')]);

        $link = (($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('/sign/' . $token));
        $html = '<p>Bonjour,</p><p>Veuillez consulter et signer électroniquement le contrat « ' . View::e($contract['title']) . ' » en suivant ce lien :</p>'
            . '<p><a href="' . View::e($link) . '">' . View::e($link) . '</a></p>';

        try {
            Mailer::send($contract['client_email'], 'Contrat à signer — ' . $contract['title'], $html);
            Session::flash('success', 'Lien de signature envoyé à ' . $contract['client_email']);
        } catch (\RuntimeException $e) {
            Session::flash('error', "Échec de l'envoi : " . $e->getMessage());
        }

        redirect('/contracts/' . $id);
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Contract::delete((int) $id);
        Session::flash('success', 'Contrat supprimé.');
        redirect('/contracts');
    }

    public static function signForm(string $token): void
    {
        $contract = Contract::findByToken($token);
        if (!$contract) { http_response_code(404); die('Lien invalide ou expiré.'); }
        if ($contract['status'] === 'signed') {
            View::render('contracts/signed_already', ['contract' => $contract], layout: null);
            return;
        }
        View::render('contracts/sign', ['contract' => $contract], layout: null);
    }

    public static function signSubmit(string $token): void
    {
        $contract = Contract::findByToken($token);
        if (!$contract) { http_response_code(404); die('Lien invalide ou expiré.'); }

        $signerName = input('signer_name', '');
        $signatureData = $_POST['signature_data'] ?? '';

        if ($signerName === '' || $signatureData === '') {
            Session::flash('error', 'Merci de renseigner votre nom et de signer dans le cadre prévu.');
            redirect('/sign/' . $token);
        }

        Contract::update((int) $contract['id'], [
            'status' => 'signed',
            'signer_name' => $signerName,
            'signature_data' => $signatureData,
            'signer_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'signed_at' => date('Y-m-d H:i:s'),
        ]);

        View::render('contracts/sign_thanks', ['contract' => $contract], layout: null);
    }
}
