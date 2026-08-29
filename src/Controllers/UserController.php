<?php

namespace App\Controllers;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\ModuleAccess;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

class UserController
{
    public static function index(): void
    {
        Auth::requireAdmin();
        View::render('users/index', ['title' => 'Utilisateurs', 'users' => User::all('name ASC')]);
    }

    public static function create(): void
    {
        Auth::requireAdmin();
        View::render('users/form', ['title' => 'Inviter un membre', 'user' => null]);
    }

    /**
     * Inviting a member never lets the admin set their password: an email
     * invitation link is sent instead, and the invitee chooses their own
     * password when accepting (see acceptInviteForm/acceptInvite below).
     * The account is created inactive (is_active = 0) with a placeholder,
     * unusable password hash, so it cannot be used to log in until accepted
     * — Auth::attempt() already filters on is_active = 1.
     */
    public static function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        if (ModuleAccess::memberLimitReached()) {
            Session::flash('error', "Le nombre maximum de membres de votre offre est atteint. Passez à une offre supérieure dans Abonnement pour ajouter des utilisateurs.");
            redirect('/users/create');
        }

        $name = trim(input('name', ''));
        $email = trim(input('email', ''));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Nom ou adresse email invalide.');
            redirect('/users/create');
        }

        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Session::flash('error', 'Cette adresse email est déjà utilisée par un autre compte.');
            redirect('/users/create');
        }

        $token = bin2hex(random_bytes(32));
        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            'role' => in_array(input('role'), ['admin', 'manager', 'staff'], true) ? input('role') : 'staff',
            'is_active' => 0,
            'invite_token' => $token,
            'invited_at' => date('Y-m-d H:i:s'),
            'invited_by' => Auth::id(),
        ]);

        self::sendInviteEmail($email, $name, $token);
        ActivityLog::record('Invitation membre', 'user', $userId, $email);

        Session::flash('success', "Invitation envoyée à {$email}.");
        redirect('/users');
    }

    public static function resendInvite(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        $user = User::find((int) $id);
        if (!$user || $user['is_active'] || empty($user['invite_token'])) {
            Session::flash('error', 'Invitation introuvable ou déjà acceptée.');
            redirect('/users');
        }

        self::sendInviteEmail($user['email'], $user['name'], $user['invite_token']);
        ActivityLog::record('Renvoi invitation membre', 'user', (int) $id, $user['email']);

        Session::flash('success', 'Invitation renvoyée.');
        redirect('/users');
    }

    private static function sendInviteEmail(string $email, string $name, string $token): void
    {
        $link = full_url('/invite/' . $token);
        $html = '<p>Bonjour ' . View::e($name) . ',</p>'
            . '<p>Vous avez été invité(e) à rejoindre une organisation sur EventPlanner.</p>'
            . '<p><a href="' . View::e($link) . '">Accepter l\'invitation et choisir mon mot de passe</a></p>';

        try {
            Mailer::send($email, 'Invitation à rejoindre EventPlanner', $html, null, [], false);
        } catch (\RuntimeException $e) {
            Session::flash('error', "L'invitation a été créée mais l'email n'a pas pu être envoyé : " . $e->getMessage());
        }
    }

    // --- Invitation acceptance (public, token-based — no Auth session) ---

    public static function showAcceptInvite(string $token): void
    {
        $user = self::findByInviteToken($token);
        if (!$user) {
            http_response_code(404);
            die('Invitation invalide ou déjà utilisée.');
        }
        View::render('users/accept_invite', ['user' => $user, 'token' => $token], layout: null);
    }

    public static function acceptInvite(string $token): void
    {
        Csrf::verifyOrFail();

        $user = self::findByInviteToken($token);
        if (!$user) {
            http_response_code(404);
            die('Invitation invalide ou déjà utilisée.');
        }

        $password = input('password', '');
        if (strlen($password) < 8 || $password !== input('password_confirmation', '')) {
            Session::flash('error', 'Les mots de passe doivent correspondre et contenir au moins 8 caractères.');
            redirect('/invite/' . $token);
        }

        Database::connection()->prepare(
            'UPDATE users SET password_hash = ?, is_active = 1, invite_token = NULL WHERE id = ?'
        )->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);

        Auth::login((int) $user['id'], (int) $user['organization_id']);
        Session::flash('success', 'Bienvenue ! Votre compte est activé.');
        redirect('/');
    }

    /** @return array{id:int,organization_id:int,name:string,email:string}|null */
    private static function findByInviteToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE invite_token = ? AND is_active = 0 LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    // --- Member management ---

    public static function edit(string $id): void
    {
        Auth::requireAdmin();
        $user = User::find((int) $id);
        if (!$user) { http_response_code(404); die('Utilisateur introuvable.'); }
        View::render('users/form', ['title' => 'Modifier l\'utilisateur', 'user' => $user]);
    }

    public static function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        $data = [
            'name' => input('name', ''),
            'email' => input('email', ''),
            'role' => in_array(input('role'), ['admin', 'manager', 'staff'], true) ? input('role') : 'staff',
            'is_active' => input('is_active') ? 1 : 0,
        ];

        $password = input('password', '');
        if ($password !== '') {
            if (strlen($password) < 8) {
                Session::flash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                redirect('/users/' . $id . '/edit');
            }
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        User::update((int) $id, $data);
        ActivityLog::record('Modification membre', 'user', (int) $id, $data['email']);
        Session::flash('success', 'Utilisateur mis à jour.');
        redirect('/users');
    }

    public static function toggleSuspend(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        if ((int) $id === Auth::id()) {
            Session::flash('error', 'Vous ne pouvez pas suspendre votre propre compte.');
            redirect('/users');
        }

        $user = User::find((int) $id);
        if (!$user) { http_response_code(404); die('Utilisateur introuvable.'); }

        $newStatus = $user['is_active'] ? 0 : 1;
        User::update((int) $id, ['is_active' => $newStatus]);
        ActivityLog::record($newStatus ? 'Réactivation membre' : 'Suspension membre', 'user', (int) $id, $user['email']);

        Session::flash('success', $newStatus ? 'Membre réactivé.' : 'Membre suspendu.');
        redirect('/users');
    }

    public static function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        if ((int) $id === Auth::id()) {
            Session::flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            redirect('/users');
        }

        $user = User::find((int) $id);
        $email = $user['email'] ?? '';

        User::delete((int) $id);
        ActivityLog::record('Suppression membre', 'user', (int) $id, $email);
        Session::flash('success', 'Utilisateur supprimé.');
        redirect('/users');
    }
}
