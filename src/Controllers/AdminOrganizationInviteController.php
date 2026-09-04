<?php

namespace App\Controllers;

use App\Core\AdminActivityLog;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\OrganizationInvite;
use App\Models\Plan;

/**
 * Super-admin-issued invitations to create a new organization — distinct
 * from a team invite (UserController), which adds a member to an existing
 * organization. An org invite instead bootstraps a brand-new tenant, and
 * can optionally pre-assign a plan (e.g. a negotiated or grandfathered
 * offer) instead of the default self-signup plan.
 */
class AdminOrganizationInviteController
{
    public static function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/organization_invites', [
            'title' => 'Invitations',
            'invites' => OrganizationInvite::allWithRelations(),
            'plans' => Plan::activeOrdered(),
        ]);
    }

    public static function store(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $email = trim(input('email', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Adresse email invalide.');
            redirect('/admin/invitations');
        }

        $token = bin2hex(random_bytes(32));
        $id = OrganizationInvite::create([
            'email' => $email,
            'note' => input('note', ''),
            'plan_id' => input('plan_id') !== '' ? (int) input('plan_id') : null,
            'invited_by' => Auth::id(),
            'token' => $token,
            'status' => 'pending',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
        ]);

        self::sendInviteEmail($email, $token);
        AdminActivityLog::record('organization_invite_sent');

        Session::flash('success', 'Invitation envoyée à ' . $email . '.');
        redirect('/admin/invitations');
    }

    public static function resend(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $invite = OrganizationInvite::find((int) $id);
        if (!$invite || $invite['status'] !== 'pending') {
            Session::flash('error', 'Invitation introuvable ou déjà utilisée.');
            redirect('/admin/invitations');
        }

        OrganizationInvite::update((int) $id, ['expires_at' => date('Y-m-d H:i:s', strtotime('+14 days'))]);
        self::sendInviteEmail($invite['email'], $invite['token']);
        AdminActivityLog::record('organization_invite_resent');

        Session::flash('success', 'Invitation renvoyée.');
        redirect('/admin/invitations');
    }

    public static function revoke(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $invite = OrganizationInvite::find((int) $id);
        if ($invite && $invite['status'] === 'pending') {
            OrganizationInvite::update((int) $id, ['status' => 'revoked']);
            AdminActivityLog::record('organization_invite_revoked');
            Session::flash('success', 'Invitation révoquée.');
        }
        redirect('/admin/invitations');
    }

    private static function sendInviteEmail(string $email, string $token): void
    {
        $link = full_url('/join/' . $token);
        $html = '<p>Bonjour,</p>'
            . '<p>Vous avez été invité(e) à créer votre organisation sur EventPlanner.</p>'
            . '<p style="margin:24px 0;"><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="background-color:#14213d; color:#ffffff; padding:12px 22px; text-decoration:none; font-family:Helvetica,Arial,sans-serif; font-size:14px;">Créer mon organisation</a></p>'
            . '<p style="color:#8a909c; font-size:12px;">Ce lien est valable 14 jours.</p>';

        try {
            Mailer::sendSystem($email, 'Invitation à créer votre organisation sur EventPlanner', $html);
        } catch (\RuntimeException $e) {
            Session::flash('error', "L'invitation a été enregistrée mais l'email n'a pas pu être envoyé : " . $e->getMessage());
        }
    }
}
