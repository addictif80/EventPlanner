<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Plan;
use App\Models\SiteMenuItem;

class LandingController
{
    public static function index(): void
    {
        if (Auth::check()) {
            DashboardController::index();
            return;
        }

        View::render('landing/index', [
            'plans' => Plan::activeOrdered(),
            'headerItems' => SiteMenuItem::activeForLocation('header'),
            'footerItems' => SiteMenuItem::activeForLocation('footer'),
        ], layout: null);
    }
}
