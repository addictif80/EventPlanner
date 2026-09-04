<?php

namespace App\Core;

/**
 * The demo account (a single shared organization, credentials published on
 * the landing page, reseeded nightly by bin/seed_demo_data.php) lets a
 * visitor use the whole app before signing up. Everything that only writes
 * to this app's own database behaves normally — creating a client, a quote,
 * a POS sale — so the tour feels real. Anything with a real-world side
 * effect or a cost outside this app (an actual email, a Stripe charge, a
 * paid Claude API call) is short-circuited here instead, each call site
 * checking this flag right before that side effect.
 */
class Demo
{
    /** Published on the landing page / login screen and used by bin/seed_demo_data.php and DemoController. */
    public const EMAIL = 'demo@eventplanner.fr';
    public const PASSWORD = 'Demo1234!';

    private static ?bool $active = null;

    public static function isActive(): bool
    {
        if (self::$active !== null) {
            return self::$active;
        }

        $orgId = Auth::organizationId();
        if (!$orgId) {
            return self::$active = false;
        }

        $stmt = Database::connection()->prepare('SELECT is_demo FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        return self::$active = (bool) $stmt->fetchColumn();
    }

    /** For public/unauthenticated routes (no Auth session) that already resolved an organization_id from a token. */
    public static function isOrganization(int $organizationId): bool
    {
        $stmt = Database::connection()->prepare('SELECT is_demo FROM organizations WHERE id = ?');
        $stmt->execute([$organizationId]);
        return (bool) $stmt->fetchColumn();
    }
}
