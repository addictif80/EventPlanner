<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\OrganizationInvite;

/**
 * Public acceptance flow for a super-admin-issued organization invite (see
 * AdminOrganizationInviteController) — the invited person picks a name for
 * their organization and a password; the email itself is fixed by the
 * invite. No Auth session exists yet, same as UserController's team-invite
 * acceptance.
 */
class JoinController
{
    public static function show(string $token): void
    {
        $invite = OrganizationInvite::findByToken($token);
        if (!$invite || !OrganizationInvite::isPending($invite)) {
            View::render('auth/join_invalid', [], layout: null);
            return;
        }

        View::render('auth/join', ['invite' => $invite, 'token' => $token], layout: null);
    }

    public static function store(string $token): void
    {
        Csrf::verifyOrFail();

        $invite = OrganizationInvite::findByToken($token);
        if (!$invite || !OrganizationInvite::isPending($invite)) {
            View::render('auth/join_invalid', [], layout: null);
            return;
        }

        $orgName = input('organization_name', '');
        $name = input('name', '');
        $password = input('password', '');

        $errors = \App\Controllers\RegisterController::validate($orgName, $name, $invite['email'], $password);
        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $_SESSION['old'] = ['organization_name' => $orgName, 'name' => $name];
            redirect('/join/' . $token);
        }

        try {
            $result = \App\Controllers\RegisterController::provision($orgName, $name, $invite['email'], $password, $invite['plan_id']);
        } catch (\Throwable $e) {
            Session::flash('error', "La création de l'organisation a échoué. Merci de réessayer.");
            redirect('/join/' . $token);
        }

        OrganizationInvite::update((int) $invite['id'], [
            'status' => 'accepted',
            'accepted_organization_id' => $result['organization_id'],
            'accepted_at' => date('Y-m-d H:i:s'),
        ]);

        \App\Models\Notification::toPlatform('system', 'Nouvelle organisation (invitation)', $orgName . ' a rejoint via une invitation.', '/admin/organizations/' . $result['organization_id']);

        Auth::login($result['user_id'], $result['organization_id']);
        Session::flash('success', 'Bienvenue sur EventPlanner ! Pensez à configurer votre serveur SMTP dans Paramètres pour pouvoir envoyer des emails.');
        redirect('/');
    }
}
