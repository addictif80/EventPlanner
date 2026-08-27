<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
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
