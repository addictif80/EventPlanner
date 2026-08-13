<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AdminActivityLog;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;

class AdminBlocklistController
{
    public static function index(): void
    {
        Auth::requireSuperAdmin();

        $pdo = Database::connection();
        View::render('admin/blocklist', [
            'title' => 'Blocage IP / email',
            'ips' => $pdo->query('SELECT blocked_ips.*, users.name AS blocked_by_name FROM blocked_ips LEFT JOIN users ON users.id = blocked_ips.blocked_by ORDER BY blocked_ips.created_at DESC')->fetchAll(),
            'emails' => $pdo->query('SELECT blocked_emails.*, users.name AS blocked_by_name FROM blocked_emails LEFT JOIN users ON users.id = blocked_emails.blocked_by ORDER BY blocked_emails.created_at DESC')->fetchAll(),
        ]);
    }

    public static function storeIp(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $ip = trim(input('ip_address', ''));
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            Session::flash('error', 'Adresse IP invalide.');
            redirect('/admin/blocklist');
        }

        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO blocked_ips (ip_address, reason, blocked_by) VALUES (?, ?, ?)'
        );
        $stmt->execute([$ip, input('reason', ''), Auth::id()]);

        AdminActivityLog::record('ip_blocked', 'blocked_ip', null, $ip);
        Session::flash('success', 'Adresse IP bloquée.');
        redirect('/admin/blocklist');
    }

    public static function destroyIp(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        Database::connection()->prepare('DELETE FROM blocked_ips WHERE id = ?')->execute([$id]);
        AdminActivityLog::record('ip_unblocked', 'blocked_ip', (int) $id);
        Session::flash('success', 'Adresse IP débloquée.');
        redirect('/admin/blocklist');
    }

    public static function storeEmail(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $email = strtolower(trim(input('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Adresse email invalide.');
            redirect('/admin/blocklist');
        }

        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO blocked_emails (email, reason, blocked_by) VALUES (?, ?, ?)'
        );
        $stmt->execute([$email, input('reason', ''), Auth::id()]);

        AdminActivityLog::record('email_blocked', 'blocked_email', null, $email);
        Session::flash('success', 'Adresse email bloquée.');
        redirect('/admin/blocklist');
    }

    public static function destroyEmail(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        Database::connection()->prepare('DELETE FROM blocked_emails WHERE id = ?')->execute([$id]);
        AdminActivityLog::record('email_unblocked', 'blocked_email', (int) $id);
        Session::flash('success', 'Adresse email débloquée.');
        redirect('/admin/blocklist');
    }
}
