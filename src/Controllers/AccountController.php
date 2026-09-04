<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Demo;
use App\Core\Session;
use App\Core\View;

/**
 * Self-service RGPD actions for the logged-in user's own account: export
 * their own profile data, and delete their own account. If they are the
 * only member left in their organization, deleting the account necessarily
 * deletes the organization too (nothing legitimate would remain of it) —
 * see the ON DELETE CASCADE note in SettingsController::destroyOrganization().
 */
class AccountController
{
    public static function show(): void
    {
        Auth::requireLogin();

        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE organization_id = ?');
        $stmt->execute([Auth::organizationId()]);

        View::render('account/show', [
            'title' => 'Mon compte',
            'user' => Auth::user(),
            'isSoleMember' => (int) $stmt->fetchColumn() <= 1,
        ]);
    }

    public static function updateProfile(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();

        $name = trim(input('name', ''));
        $email = trim(input('email', ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Nom ou adresse email invalide.');
            redirect('/account');
        }

        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, Auth::id()]);
        if ($stmt->fetch()) {
            Session::flash('error', 'Cette adresse email est déjà utilisée par un autre compte.');
            redirect('/account');
        }

        Database::connection()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')->execute([$name, $email, Auth::id()]);
        unset($_SESSION['user_cache']);
        \App\Core\ActivityLog::record('Modification profil', 'user', Auth::id(), 'Nom/email mis à jour');

        Session::flash('success', 'Profil mis à jour.');
        redirect('/account');
    }

    public static function updatePassword(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();

        $stmt = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::id()]);
        $currentHash = $stmt->fetchColumn();

        if (!$currentHash || !password_verify(input('current_password', ''), $currentHash)) {
            Session::flash('error', 'Mot de passe actuel incorrect.');
            redirect('/account');
        }

        $newPassword = input('new_password', '');
        if (strlen($newPassword) < 8 || $newPassword !== input('new_password_confirmation', '')) {
            Session::flash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères et être confirmé à l\'identique.');
            redirect('/account');
        }

        Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), Auth::id()]);
        \App\Core\ActivityLog::record('Changement mot de passe', 'user', Auth::id());

        Session::flash('success', 'Mot de passe mis à jour.');
        redirect('/account');
    }

    public static function exportData(): void
    {
        Auth::requireLogin();

        $user = Auth::user();

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="mes-donnees-compte.json"');
        echo json_encode(['profil' => $user], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public static function destroy(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();

        if (Demo::isActive()) {
            Session::flash('error', 'La suppression de compte est désactivée en mode démo.');
            redirect('/account');
        }

        $pdo = Database::connection();
        $organizationId = Auth::organizationId();
        $userId = Auth::id();

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $passwordHash = $stmt->fetchColumn();

        if (!$passwordHash || !password_verify(input('password', ''), $passwordHash)) {
            Session::flash('error', 'Mot de passe incorrect.');
            redirect('/account');
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE organization_id = ?');
        $stmt->execute([$organizationId]);
        $memberCount = (int) $stmt->fetchColumn();

        if ($memberCount <= 1) {
            // Sole member: deleting the account means deleting the whole
            // organization (cascades to every table via organization_id FKs).
            $pdo->prepare('DELETE FROM organizations WHERE id = ?')->execute([$organizationId]);
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ? AND organization_id = ?')->execute([$userId, $organizationId]);
        }

        Auth::logout();
        redirect('/');
    }
}
