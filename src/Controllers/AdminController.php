<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\AdminActivityLog;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Organization;

/**
 * Platform-level administration, entirely separate from an organization's own
 * (tenant-scoped) area: reachable only to users with users.is_super_admin = 1,
 * see Auth::requireSuperAdmin().
 */
class AdminController
{
    public static function dashboard(): void
    {
        Auth::requireSuperAdmin();
        $pdo = Database::connection();

        $stats = [
            'organizations' => (int) $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn(),
            'suspended_organizations' => (int) $pdo->query("SELECT COUNT(*) FROM organizations WHERE status = 'suspended'")->fetchColumn(),
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'open_tickets' => (int) $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status != 'closed'")->fetchColumn(),
            'blocked_ips' => (int) $pdo->query('SELECT COUNT(*) FROM blocked_ips')->fetchColumn(),
            'blocked_emails' => (int) $pdo->query('SELECT COUNT(*) FROM blocked_emails')->fetchColumn(),
        ];

        $recentOrganizations = $pdo->query('SELECT * FROM organizations ORDER BY created_at DESC LIMIT 8')->fetchAll();
        $recentTickets = $pdo->query(
            'SELECT support_tickets.*, organizations.name AS organization_name
             FROM support_tickets JOIN organizations ON organizations.id = support_tickets.organization_id
             ORDER BY support_tickets.updated_at DESC LIMIT 8'
        )->fetchAll();

        View::render('admin/dashboard', [
            'title' => 'Administration plateforme',
            'stats' => $stats,
            'recentOrganizations' => $recentOrganizations,
            'recentTickets' => $recentTickets,
        ]);
    }

    public static function organizations(): void
    {
        Auth::requireSuperAdmin();

        $stmt = Database::connection()->query(
            'SELECT organizations.*,
                    (SELECT COUNT(*) FROM users WHERE users.organization_id = organizations.id) AS user_count,
                    (SELECT COUNT(*) FROM events WHERE events.organization_id = organizations.id) AS event_count
             FROM organizations ORDER BY organizations.created_at DESC'
        );

        View::render('admin/organizations', ['title' => 'Organisations', 'organizations' => $stmt->fetchAll()]);
    }

    public static function showOrganization(string $id): void
    {
        Auth::requireSuperAdmin();
        $organization = Organization::find((int) $id);
        if (!$organization) { http_response_code(404); die('Organisation introuvable.'); }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE organization_id = ? ORDER BY name ASC');
        $stmt->execute([$id]);

        View::render('admin/organization_show', [
            'title' => $organization['name'],
            'organization' => $organization,
            'users' => $stmt->fetchAll(),
        ]);
    }

    public static function toggleOrganizationStatus(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $organization = Organization::find((int) $id);
        if (!$organization) { http_response_code(404); die('Organisation introuvable.'); }

        $newStatus = $organization['status'] === 'suspended' ? 'active' : 'suspended';
        Organization::update((int) $id, ['status' => $newStatus]);

        AdminActivityLog::record(
            $newStatus === 'suspended' ? 'organization_suspended' : 'organization_reactivated',
            'organization',
            (int) $id,
            $organization['name']
        );

        Session::flash('success', $newStatus === 'suspended' ? 'Organisation suspendue.' : 'Organisation réactivée.');
        redirect('/admin/organizations/' . $id);
    }

    public static function activityLog(): void
    {
        Auth::requireSuperAdmin();

        $stmt = Database::connection()->query(
            'SELECT admin_activity_log.*, users.name AS admin_name
             FROM admin_activity_log LEFT JOIN users ON users.id = admin_activity_log.admin_user_id
             ORDER BY admin_activity_log.created_at DESC LIMIT 300'
        );

        View::render('admin/activity_log', ['title' => "Journal d'activité plateforme", 'logs' => $stmt->fetchAll()]);
    }
}
