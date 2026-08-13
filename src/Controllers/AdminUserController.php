<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AdminActivityLog;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;

class AdminUserController
{
    public static function index(): void
    {
        Auth::requireSuperAdmin();

        $search = trim($_GET['q'] ?? '');
        $sql = 'SELECT users.*, organizations.name AS organization_name
                FROM users JOIN organizations ON organizations.id = users.organization_id';
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE users.name LIKE ? OR users.email LIKE ? OR organizations.name LIKE ?';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }
        $sql .= ' ORDER BY users.created_at DESC LIMIT 300';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        View::render('admin/users', ['title' => 'Utilisateurs', 'users' => $stmt->fetchAll(), 'search' => $search]);
    }

    public static function edit(string $id): void
    {
        Auth::requireSuperAdmin();

        $stmt = Database::connection()->prepare(
            'SELECT users.*, organizations.name AS organization_name
             FROM users JOIN organizations ON organizations.id = users.organization_id
             WHERE users.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) { http_response_code(404); die('Utilisateur introuvable.'); }

        View::render('admin/user_edit', ['title' => 'Modifier ' . $user['name'], 'user' => $user]);
    }

    public static function update(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) { http_response_code(404); die('Utilisateur introuvable.'); }

        $isActive = input('is_active') ? 1 : 0;
        $isSuperAdmin = input('is_super_admin') ? 1 : 0;

        if ((int) $id === Auth::id() && $isSuperAdmin === 0) {
            Session::flash('error', 'Vous ne pouvez pas retirer votre propre statut de super administrateur.');
            redirect('/admin/users/' . $id . '/edit');
        }

        $update = Database::connection()->prepare(
            'UPDATE users SET is_active = ?, is_super_admin = ? WHERE id = ?'
        );
        $update->execute([$isActive, $isSuperAdmin, $id]);

        AdminActivityLog::record('user_updated', 'user', (int) $id, $isSuperAdmin ? 'super_admin=1' : 'super_admin=0');

        Session::flash('success', 'Utilisateur mis à jour.');
        redirect('/admin/users');
    }

    /** Logs the super admin in as the target user, for support purposes. */
    public static function impersonate(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        if ((int) $id === Auth::id()) {
            Session::flash('error', 'Vous êtes déjà connecté avec ce compte.');
            redirect('/admin/users');
        }

        if (!Auth::impersonate((int) $id)) {
            Session::flash('error', 'Impossible de se connecter en tant que cet utilisateur.');
            redirect('/admin/users');
        }

        AdminActivityLog::record('impersonation_start', 'user', (int) $id);
        Session::flash('success', 'Vous êtes maintenant connecté en tant que cet utilisateur.');
        redirect('/');
    }

    public static function stopImpersonating(): void
    {
        Csrf::verifyOrFail();

        $impersonatedId = Auth::id();
        if (Auth::stopImpersonating()) {
            AdminActivityLog::record('impersonation_stop', 'user', $impersonatedId);
            Session::flash('success', 'Retour à votre session administrateur.');
        }

        redirect('/admin/users');
    }
}
