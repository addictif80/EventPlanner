<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

class AuthController
{
    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        View::render('auth/login', [], layout: null);
    }

    public static function login(): void
    {
        Csrf::verifyOrFail();

        $email = input('email', '');
        $password = input('password', '');

        if (Auth::attempt($email, $password)) {
            redirect('/');
        }

        Session::flash('error', 'Identifiants incorrects.');
        $_SESSION['old'] = ['email' => $email];
        redirect('/login');
    }

    public static function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
