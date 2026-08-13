<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\CompanySettings;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\User;

class RegisterController
{
    public static function show(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        View::render('auth/register', [], layout: null);
    }

    public static function register(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        Csrf::verifyOrFail();

        $orgName = input('organization_name', '');
        $name = input('name', '');
        $email = input('email', '');
        $password = input('password', '');

        $errors = [];
        if ($orgName === '') {
            $errors[] = "Le nom de votre entreprise/agence est obligatoire.";
        }
        if ($name === '') {
            $errors[] = 'Votre nom est obligatoire.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if (empty($errors)) {
            $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Cette adresse email est déjà utilisée par un autre compte.';
            }
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $_SESSION['old'] = ['organization_name' => $orgName, 'name' => $name, 'email' => $email];
            redirect('/register');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $organizationId = Organization::create(['name' => $orgName]);

            $userId = User::create([
                'organization_id' => $organizationId,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
                'is_active' => 1,
            ]);

            CompanySettings::createDefaults($organizationId);

            $pdo->prepare('INSERT INTO smtp_settings (organization_id) VALUES (?)')->execute([$organizationId]);

            $pdo->prepare(
                'INSERT INTO event_types (organization_id, name) VALUES (?, ?), (?, ?), (?, ?), (?, ?), (?, ?), (?, ?), (?, ?), (?, ?)'
            )->execute([
                $organizationId, 'Mariage', $organizationId, 'Anniversaire', $organizationId, 'Séminaire / Corporate',
                $organizationId, 'Conférence', $organizationId, 'Baptême', $organizationId, 'Soirée privée',
                $organizationId, 'Festival / Concert', $organizationId, 'Autre',
            ]);

            EmailTemplate::createDefaults($organizationId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Session::flash('error', "L'inscription a échoué. Merci de réessayer.");
            redirect('/register');
        }

        Auth::login($userId, $organizationId);
        Session::flash('success', 'Bienvenue sur EventPlanner ! Pensez à configurer votre serveur SMTP dans Paramètres pour pouvoir envoyer des emails.');
        redirect('/');
    }
}
