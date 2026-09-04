<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\SiteMenuItem;

/**
 * Public, unauthenticated directory of organizations that opted in
 * (company_settings.directory_listed) — a network-effect feature: prospects
 * browse or search by city/specialty and land on an organization's public
 * profile, which only shows reviews the reviewing client explicitly
 * consented to publish (satisfaction_surveys.consent_public) and the
 * organization chose to display (is_published) — see SurveyController for
 * that moderation step.
 */
class DirectoryController
{
    public static function index(): void
    {
        $pdo = Database::connection();
        $search = trim($_GET['q'] ?? '');

        $sql = "SELECT cs.organization_id, cs.company_name, cs.city, cs.directory_slug, cs.directory_description, cs.directory_specialties,
                       (SELECT ROUND(AVG(rating), 1) FROM satisfaction_surveys WHERE organization_id = cs.organization_id AND is_published = 1) AS avg_rating,
                       (SELECT COUNT(*) FROM satisfaction_surveys WHERE organization_id = cs.organization_id AND is_published = 1) AS review_count
                FROM company_settings cs
                WHERE cs.directory_listed = 1 AND cs.directory_slug IS NOT NULL";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (cs.company_name LIKE ? OR cs.city LIKE ? OR cs.directory_specialties LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }

        $sql .= " ORDER BY review_count DESC, cs.company_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        View::render('directory/index', [
            'organizations' => $stmt->fetchAll(),
            'search' => $search,
            'headerItems' => SiteMenuItem::activeForLocation('header'),
            'footerItems' => SiteMenuItem::activeForLocation('footer'),
        ], layout: null);
    }

    public static function show(string $slug): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM company_settings WHERE directory_slug = ? AND directory_listed = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $company = $stmt->fetch();

        if (!$company) { http_response_code(404); die('Cette organisation ne figure pas (ou plus) dans l\'annuaire.'); }

        $orgId = $company['organization_id'];

        $stmt = $pdo->prepare(
            "SELECT rating, comments, submitted_at FROM satisfaction_surveys
             WHERE organization_id = ? AND is_published = 1 AND consent_public = 1
             ORDER BY submitted_at DESC"
        );
        $stmt->execute([$orgId]);
        $reviews = $stmt->fetchAll();

        $avgRating = null;
        if (!empty($reviews)) {
            $avgRating = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
        }

        $stmt = $pdo->prepare('SELECT is_enabled, public_slug FROM booking_settings WHERE organization_id = ?');
        $stmt->execute([$orgId]);
        $booking = $stmt->fetch();

        View::render('directory/show', [
            'company' => $company,
            'reviews' => $reviews,
            'avgRating' => $avgRating,
            'bookingUrl' => (!empty($booking['is_enabled']) && !empty($booking['public_slug'])) ? '/booking/' . $booking['public_slug'] : null,
            'headerItems' => SiteMenuItem::activeForLocation('header'),
            'footerItems' => SiteMenuItem::activeForLocation('footer'),
        ], layout: null);
    }
}
