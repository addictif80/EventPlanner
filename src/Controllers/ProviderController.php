<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\Provider;

class ProviderController
{
    public static function index(): void
    {
        View::render('providers/index', ['title' => 'Prestataires', 'providers' => Provider::all('name ASC')]);
    }

    public static function create(): void
    {
        View::render('providers/form', ['title' => 'Nouveau prestataire', 'provider' => null]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        Provider::create(self::validated());
        Session::flash('success', 'Prestataire ajouté.');
        redirect('/providers');
    }

    public static function edit(string $id): void
    {
        $provider = Provider::find((int) $id);
        if (!$provider) { http_response_code(404); die('Prestataire introuvable.'); }
        View::render('providers/form', ['title' => 'Modifier le prestataire', 'provider' => $provider]);
    }

    public static function update(string $id): void
    {
        Csrf::verifyOrFail();
        Provider::update((int) $id, self::validated());
        Session::flash('success', 'Prestataire mis à jour.');
        redirect('/providers');
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Provider::delete((int) $id);
        Session::flash('success', 'Prestataire supprimé.');
        redirect('/providers');
    }

    private static function validated(): array
    {
        return [
            'name' => input('name', ''),
            'category' => input('category', ''),
            'contact_name' => input('contact_name', ''),
            'email' => input('email', ''),
            'phone' => input('phone', ''),
            'notes' => input('notes', ''),
            'rating' => input('rating') !== '' ? (int) input('rating') : null,
        ];
    }
}
