<?php

namespace App\Controllers;

use App\Core\View;
use App\Models\SiteMenuItem;
use App\Models\SitePage;

class PageController
{
    public static function show(string $slug): void
    {
        $page = SitePage::findBySlug($slug);
        $headerItems = SiteMenuItem::activeForLocation('header');
        $footerItems = SiteMenuItem::activeForLocation('footer');

        if (!$page) {
            http_response_code(404);
            View::render('page/not_found', compact('headerItems', 'footerItems'), layout: null);
            return;
        }

        View::render('page/show', compact('page', 'headerItems', 'footerItems'), layout: null);
    }
}
