<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\ModuleAccess;
use App\Core\Session;
use App\Core\View;
use App\Models\Appointment;
use App\Models\BookingSettings;

class BookingSettingsController
{
    private const DAYS = ['1' => 'Lundi', '2' => 'Mardi', '3' => 'Mercredi', '4' => 'Jeudi', '5' => 'Vendredi', '6' => 'Samedi', '7' => 'Dimanche'];

    public static function edit(): void
    {
        ModuleAccess::requireModule('appointments');
        $slug = BookingSettings::ensureSlug();

        View::render('booking/settings', [
            'title' => 'Prise de rendez-vous en ligne',
            'settings' => BookingSettings::get(),
            'slug' => $slug,
            'publicUrl' => full_url('/booking/' . $slug),
            'days' => self::DAYS,
        ]);
    }

    public static function update(): void
    {
        ModuleAccess::requireModule('appointments');
        Csrf::verifyOrFail();

        $weeklyHours = [];
        foreach (self::DAYS as $day => $label) {
            if (input('open_' . $day) === '1' && input('start_' . $day) !== '' && input('end_' . $day) !== '') {
                $weeklyHours[$day] = ['start' => input('start_' . $day), 'end' => input('end_' . $day)];
            } else {
                $weeklyHours[$day] = null;
            }
        }

        BookingSettings::save([
            'is_enabled' => input('is_enabled') ? 1 : 0,
            'public_slug' => BookingSettings::ensureSlug(),
            'slot_duration_minutes' => max(5, (int) input('slot_duration_minutes', 30)),
            'buffer_minutes' => max(0, (int) input('buffer_minutes', 0)),
            'min_notice_hours' => max(0, (int) input('min_notice_hours', 24)),
            'max_advance_days' => max(1, (int) input('max_advance_days', 60)),
            'weekly_hours' => $weeklyHours,
            'location_type' => input('location_type', 'Téléphone'),
            'meeting_instructions' => input('meeting_instructions', ''),
        ]);

        Session::flash('success', 'Réglages de prise de rendez-vous enregistrés.');
        redirect('/booking-settings');
    }

    public static function index(): void
    {
        ModuleAccess::requireModule('appointments');
        View::render('booking/appointments', [
            'title' => 'Rendez-vous',
            'upcoming' => Appointment::upcoming(),
            'history' => Appointment::history(),
        ]);
    }

    public static function cancel(string $id): void
    {
        ModuleAccess::requireModule('appointments');
        Csrf::verifyOrFail();
        Appointment::update((int) $id, ['status' => 'cancelled']);
        Session::flash('success', 'Rendez-vous annulé.');
        redirect('/appointments');
    }
}
