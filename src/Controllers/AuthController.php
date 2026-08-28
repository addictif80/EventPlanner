<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Models\BlockedEmail;

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

        if (BlockedEmail::isBlocked($email)) {
            Session::flash('error', 'Ce compte a été bloqué.');
            redirect('/login');
        }

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

    public static function showForgotPassword(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        View::render('auth/forgot_password', [], layout: null);
    }

    /**
     * Always shows the same generic confirmation regardless of whether the
     * email matched an account, to avoid leaking which addresses are
     * registered. The email itself is sent via the matched user's own
     * organization (SMTP settings + branding), so we borrow the same
     * $_SESSION['organization_id'] trick the cron scripts use to make
     * Auth::organizationId()-dependent code (Mailer::send) work outside a
     * real login session — never touching $_SESSION['user_id'], so this
     * never grants access.
     */
    public static function sendResetLink(): void
    {
        Csrf::verifyOrFail();

        $email = trim(input('email', ''));
        $confirmation = "Si un compte existe avec cette adresse, un email de réinitialisation vient d'être envoyé.";

        if ($email !== '') {
            $stmt = Database::connection()->prepare('SELECT id, organization_id, name FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                Database::connection()
                    ->prepare('UPDATE users SET password_reset_token = ?, password_reset_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?')
                    ->execute([$token, $user['id']]);

                $previousOrgId = $_SESSION['organization_id'] ?? null;
                $_SESSION['organization_id'] = (int) $user['organization_id'];

                $link = full_url('/reset-password/' . $token);
                $html = '<p>Bonjour ' . View::e($user['name']) . ',</p>'
                    . '<p>Une demande de réinitialisation de mot de passe a été effectuée pour votre compte EventPlanner.</p>'
                    . '<p><a href="' . View::e($link) . '">Choisir un nouveau mot de passe</a></p>'
                    . '<p>Ce lien expire dans 1 heure. Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.</p>';

                try {
                    Mailer::send($email, 'Réinitialisation de votre mot de passe', $html);
                } catch (\RuntimeException $e) {
                    // Best-effort: never reveal a delivery failure to the caller (same generic message either way).
                }

                if ($previousOrgId === null) {
                    unset($_SESSION['organization_id']);
                } else {
                    $_SESSION['organization_id'] = $previousOrgId;
                }
            }
        }

        Session::flash('success', $confirmation);
        redirect('/forgot-password');
    }

    public static function showResetPassword(string $token): void
    {
        if (!self::validResetToken($token)) {
            Session::flash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            redirect('/forgot-password');
        }
        View::render('auth/reset_password', ['token' => $token], layout: null);
    }

    public static function resetPassword(string $token): void
    {
        Csrf::verifyOrFail();

        $user = self::validResetToken($token);
        if (!$user) {
            Session::flash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            redirect('/forgot-password');
        }

        $password = input('password', '');
        if (strlen($password) < 8 || $password !== input('password_confirmation', '')) {
            Session::flash('error', 'Les mots de passe doivent correspondre et contenir au moins 8 caractères.');
            redirect('/reset-password/' . $token);
        }

        Database::connection()
            ->prepare('UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);

        Auth::login((int) $user['id'], (int) $user['organization_id']);
        Session::flash('success', 'Mot de passe mis à jour.');
        redirect('/');
    }

    /** @return array{id:int,organization_id:int}|null */
    private static function validResetToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, organization_id FROM users WHERE password_reset_token = ? AND password_reset_expires_at > NOW() AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }
}
