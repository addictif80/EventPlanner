<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\Venue;

class VenueController
{
    public static function index(): void
    {
        View::render('venues/index', ['title' => 'Lieux', 'venues' => Venue::all('name ASC')]);
    }

    public static function create(): void
    {
        View::render('venues/form', ['title' => 'Nouveau lieu', 'venue' => null]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        Venue::create(self::validated());
        Session::flash('success', 'Lieu ajouté.');
        redirect('/venues');
    }

    public static function edit(string $id): void
    {
        $venue = Venue::find((int) $id);
        if (!$venue) { http_response_code(404); die('Lieu introuvable.'); }
        View::render('venues/form', ['title' => 'Modifier le lieu', 'venue' => $venue]);
    }

    public static function update(string $id): void
    {
        Csrf::verifyOrFail();
        Venue::update((int) $id, self::validated());
        Session::flash('success', 'Lieu mis à jour.');
        redirect('/venues');
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Venue::delete((int) $id);
        Session::flash('success', 'Lieu supprimé.');
        redirect('/venues');
    }

    private static function validated(): array
    {
        return [
            'name' => input('name', ''),
            'address' => input('address', ''),
            'postal_code' => input('postal_code', ''),
            'city' => input('city', ''),
            'capacity' => input('capacity') !== '' ? (int) input('capacity') : null,
            'contact_name' => input('contact_name', ''),
            'contact_phone' => input('contact_phone', ''),
            'contact_email' => input('contact_email', ''),
            'notes' => input('notes', ''),
        ];
    }
}
