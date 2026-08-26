<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Plan;

class LandingController
{
    public static function index(): void
    {
        if (Auth::check()) {
            DashboardController::index();
            return;
        }

        $plans = Plan::activeOrdered();

        View::render('landing/index', ['plans' => $plans], layout: null);
    }
}
