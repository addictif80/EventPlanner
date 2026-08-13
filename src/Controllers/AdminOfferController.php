<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AdminActivityLog;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\StripeBilling;
use App\Core\View;
use App\Models\Module;
use App\Models\ModulePackage;
use App\Models\ModulePackageItem;
use App\Models\Plan;
use App\Models\PlanModule;

/**
 * Super admin catalog management: plans (price, member limit, included
 * modules), individual modules (paid add-ons), and module packages (bundles
 * of modules sold together). Stripe Products/Prices are created/refreshed
 * automatically on save via StripeBilling::syncPrice().
 */
class AdminOfferController
{
    public static function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/offers', [
            'title' => 'Offres',
            'plans' => Plan::allOrdered(),
            'modules' => Module::allOrdered(),
            'packages' => ModulePackage::allOrdered(),
            'stripeConfigured' => StripeBilling::isConfigured(),
        ]);
    }

    // --- Plans ---

    public static function createPlan(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/plan_form', ['title' => 'Nouvelle offre', 'plan' => null, 'modules' => Module::activeOrdered()]);
    }

    public static function editPlan(string $id): void
    {
        Auth::requireSuperAdmin();
        $plan = Plan::withModules((int) $id);
        if (!$plan) { http_response_code(404); die('Offre introuvable.'); }
        View::render('admin/plan_form', ['title' => 'Modifier ' . $plan['name'], 'plan' => $plan, 'modules' => Module::activeOrdered()]);
    }

    public static function storePlan(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::savePlan(null);
    }

    public static function updatePlan(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::savePlan((int) $id);
    }

    private static function savePlan(?int $id): void
    {
        $name = input('name', '');
        $price = (float) str_replace(',', '.', input('monthly_price', '0'));
        $maxMembers = input('max_members', '') !== '' ? (int) input('max_members') : null;
        $moduleIds = array_map('intval', $_POST['module_ids'] ?? []);

        if ($name === '') {
            Session::flash('error', 'Le nom est obligatoire.');
            redirect($id ? '/admin/offers/plans/' . $id . '/edit' : '/admin/offers/plans/create');
        }

        $existing = $id ? Plan::find($id) : null;

        $data = [
            'name' => $name,
            'description' => input('description', ''),
            'monthly_price' => $price,
            'max_members' => $maxMembers,
            'is_default_signup' => input('is_default_signup') ? 1 : 0,
            'is_active' => input('is_active') ? 1 : 0,
            'sort_order' => (int) input('sort_order', 0),
        ];

        try {
            $stripe = StripeBilling::syncPrice(
                $name,
                $price,
                $existing['stripe_product_id'] ?? null,
                $existing['stripe_price_id'] ?? null,
                $existing ? (float) $existing['monthly_price'] : null
            );
            $data['stripe_product_id'] = $stripe['stripe_product_id'];
            $data['stripe_price_id'] = $stripe['stripe_price_id'];
        } catch (\RuntimeException $e) {
            Session::flash('error', 'Erreur Stripe : ' . $e->getMessage() . ' (offre enregistrée sans synchronisation Stripe)');
        }

        if ($data['is_default_signup']) {
            \App\Core\Database::connection()->exec('UPDATE plans SET is_default_signup = 0');
        }

        if ($id) {
            Plan::update($id, $data);
        } else {
            $id = Plan::create($data);
        }

        PlanModule::set($id, $moduleIds);
        AdminActivityLog::record('plan_saved', 'plan', $id, $name);

        Session::flash('success', 'Offre enregistrée.');
        redirect('/admin/offers');
    }

    public static function destroyPlan(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        Plan::update((int) $id, ['is_active' => 0]);
        AdminActivityLog::record('plan_deactivated', 'plan', (int) $id);
        Session::flash('success', 'Offre désactivée.');
        redirect('/admin/offers');
    }

    // --- Modules ---

    public static function createModule(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/module_form', ['title' => 'Nouveau module', 'module' => null]);
    }

    public static function editModule(string $id): void
    {
        Auth::requireSuperAdmin();
        $module = Module::find((int) $id);
        if (!$module) { http_response_code(404); die('Module introuvable.'); }
        View::render('admin/module_form', ['title' => 'Modifier ' . $module['name'], 'module' => $module]);
    }

    public static function storeModule(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::saveModule(null);
    }

    public static function updateModule(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::saveModule((int) $id);
    }

    private static function saveModule(?int $id): void
    {
        $name = input('name', '');
        $key = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', input('module_key', $name)), '_'));
        $price = (float) str_replace(',', '.', input('monthly_price', '0'));

        if ($name === '' || $key === '') {
            Session::flash('error', 'Le nom est obligatoire.');
            redirect($id ? '/admin/offers/modules/' . $id . '/edit' : '/admin/offers/modules/create');
        }

        $existing = $id ? Module::find($id) : null;

        $data = [
            'module_key' => $key,
            'name' => $name,
            'description' => input('description', ''),
            'monthly_price' => $price,
            'is_active' => input('is_active') ? 1 : 0,
        ];

        try {
            $stripe = StripeBilling::syncPrice(
                $name,
                $price,
                $existing['stripe_product_id'] ?? null,
                $existing['stripe_price_id'] ?? null,
                $existing ? (float) $existing['monthly_price'] : null
            );
            $data['stripe_product_id'] = $stripe['stripe_product_id'];
            $data['stripe_price_id'] = $stripe['stripe_price_id'];
        } catch (\RuntimeException $e) {
            Session::flash('error', 'Erreur Stripe : ' . $e->getMessage() . ' (module enregistré sans synchronisation Stripe)');
        }

        if ($id) {
            Module::update($id, $data);
        } else {
            $id = Module::create($data);
        }

        AdminActivityLog::record('module_saved', 'module', $id, $name);
        Session::flash('success', 'Module enregistré.');
        redirect('/admin/offers');
    }

    // --- Module packages ---

    public static function createPackage(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/package_form', ['title' => 'Nouveau package', 'package' => null, 'modules' => Module::activeOrdered()]);
    }

    public static function editPackage(string $id): void
    {
        Auth::requireSuperAdmin();
        $package = ModulePackage::withModules((int) $id);
        if (!$package) { http_response_code(404); die('Package introuvable.'); }
        View::render('admin/package_form', ['title' => 'Modifier ' . $package['name'], 'package' => $package, 'modules' => Module::activeOrdered()]);
    }

    public static function storePackage(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::savePackage(null);
    }

    public static function updatePackage(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::savePackage((int) $id);
    }

    private static function savePackage(?int $id): void
    {
        $name = input('name', '');
        $price = (float) str_replace(',', '.', input('monthly_price', '0'));
        $moduleIds = array_map('intval', $_POST['module_ids'] ?? []);

        if ($name === '') {
            Session::flash('error', 'Le nom est obligatoire.');
            redirect($id ? '/admin/offers/packages/' . $id . '/edit' : '/admin/offers/packages/create');
        }

        $existing = $id ? ModulePackage::find($id) : null;

        $data = [
            'name' => $name,
            'description' => input('description', ''),
            'monthly_price' => $price,
            'is_active' => input('is_active') ? 1 : 0,
        ];

        try {
            $stripe = StripeBilling::syncPrice(
                $name,
                $price,
                $existing['stripe_product_id'] ?? null,
                $existing['stripe_price_id'] ?? null,
                $existing ? (float) $existing['monthly_price'] : null
            );
            $data['stripe_product_id'] = $stripe['stripe_product_id'];
            $data['stripe_price_id'] = $stripe['stripe_price_id'];
        } catch (\RuntimeException $e) {
            Session::flash('error', 'Erreur Stripe : ' . $e->getMessage() . ' (package enregistré sans synchronisation Stripe)');
        }

        if ($id) {
            ModulePackage::update($id, $data);
        } else {
            $id = ModulePackage::create($data);
        }

        ModulePackageItem::set($id, $moduleIds);
        AdminActivityLog::record('package_saved', 'module_package', $id, $name);

        Session::flash('success', 'Package enregistré.');
        redirect('/admin/offers');
    }

    public static function destroyPackage(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        ModulePackage::update((int) $id, ['is_active' => 0]);
        AdminActivityLog::record('package_deactivated', 'module_package', (int) $id);
        Session::flash('success', 'Package désactivé.');
        redirect('/admin/offers');
    }
}
