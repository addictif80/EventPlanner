<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ModuleAccess;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

class UserController
{
    public static function index(): void
    {
        Auth::requireAdmin();
        View::render('users/index', ['title' => 'Utilisateurs', 'users' => User::all('name ASC')]);
    }

    public static function create(): void
    {
        Auth::requireAdmin();
        View::render('users/form', ['title' => 'Nouvel utilisateur', 'user' => null]);
    }

    public static function store(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        if (ModuleAccess::memberLimitReached()) {
            Session::flash('error', "Le nombre maximum de membres de votre offre est atteint. Passez à une offre supérieure dans Abonnement pour ajouter des utilisateurs.");
            redirect('/users/create');
        }

        $password = input('password', '');
        if (strlen($password) < 8) {
            Session::flash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            redirect('/users/create');
        }

        User::create([
            'name' => input('name', ''),
            'email' => input('email', ''),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => in_array(input('role'), ['admin', 'manager', 'staff'], true) ? input('role') : 'staff',
            'is_active' => 1,
        ]);

        Session::flash('success', 'Utilisateur créé.');
        redirect('/users');
    }

    public static function edit(string $id): void
    {
        Auth::requireAdmin();
        $user = User::find((int) $id);
        if (!$user) { http_response_code(404); die('Utilisateur introuvable.'); }
        View::render('users/form', ['title' => 'Modifier l\'utilisateur', 'user' => $user]);
    }

    public static function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        $data = [
            'name' => input('name', ''),
            'email' => input('email', ''),
            'role' => in_array(input('role'), ['admin', 'manager', 'staff'], true) ? input('role') : 'staff',
            'is_active' => input('is_active') ? 1 : 0,
        ];

        $password = input('password', '');
        if ($password !== '') {
            if (strlen($password) < 8) {
                Session::flash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                redirect('/users/' . $id . '/edit');
            }
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        User::update((int) $id, $data);
        Session::flash('success', 'Utilisateur mis à jour.');
        redirect('/users');
    }

    public static function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        if ((int) $id === Auth::id()) {
            Session::flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            redirect('/users');
        }

        User::delete((int) $id);
        Session::flash('success', 'Utilisateur supprimé.');
        redirect('/users');
    }
}
