<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Demo;

/**
 * One-click entry into the shared demo account (see bin/seed_demo_data.php)
 * for a visitor coming from the landing page — no credentials to type. The
 * same credentials also work on the normal /login form for anyone who wants
 * to come back to it directly.
 */
class DemoController
{
    public static function start(): void
    {
        if (Auth::check()) {
            redirect('/');
        }

        if (!Auth::attempt(Demo::EMAIL, Demo::PASSWORD)) {
            http_response_code(503);
            die("Le compte de démonstration est momentanément indisponible (régénération en cours). Réessayez dans une minute.");
        }

        redirect('/');
    }
}
