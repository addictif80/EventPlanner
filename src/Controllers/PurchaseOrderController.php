<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\ModuleAccess;
use App\Core\Session;
use App\Core\View;
use App\Models\Event;
use App\Models\Provider;
use App\Models\PurchaseOrder;

class PurchaseOrderController
{
    public static function index(): void
    {
        ModuleAccess::requireModule('purchase_orders');
        View::render('purchase_orders/index', ['title' => 'Bons de commande', 'orders' => PurchaseOrder::allWithProvider()]);
    }

    public static function create(): void
    {
        ModuleAccess::requireModule('purchase_orders');
        View::render('purchase_orders/form', [
            'title' => 'Nouveau bon de commande',
            'providers' => Provider::all('name ASC'),
            'events' => Event::allWithClient(),
        ]);
    }

    public static function store(): void
    {
        ModuleAccess::requireModule('purchase_orders');
        Csrf::verifyOrFail();

        if (!Provider::find((int) input('provider_id'))) {
            http_response_code(404);
            die('Prestataire introuvable.');
        }
        if (input('event_id') !== '' && !Event::find((int) input('event_id'))) {
            http_response_code(404);
            die('Événement introuvable.');
        }

        [$items, $total] = self::parseItems();

        $id = PurchaseOrder::create([
            'po_number' => PurchaseOrder::nextNumber(),
            'provider_id' => (int) input('provider_id'),
            'event_id' => input('event_id') !== '' ? (int) input('event_id') : null,
            'status' => 'draft',
            'issue_date' => input('issue_date') ?: date('Y-m-d'),
            'total' => $total,
            'notes' => input('notes', ''),
        ]);

        PurchaseOrder::replaceItems($id, $items);
        Session::flash('success', 'Bon de commande créé.');
        redirect('/purchase-orders/' . $id);
    }

    public static function show(string $id): void
    {
        ModuleAccess::requireModule('purchase_orders');
        $po = PurchaseOrder::findWithRelations((int) $id);
        if (!$po) { http_response_code(404); die('Bon de commande introuvable.'); }

        View::render('purchase_orders/show', [
            'title' => $po['po_number'],
            'po' => $po,
            'items' => PurchaseOrder::items((int) $id),
        ]);
    }

    public static function updateStatus(string $id): void
    {
        ModuleAccess::requireModule('purchase_orders');
        Csrf::verifyOrFail();
        $status = input('status');
        if (in_array($status, ['draft', 'sent', 'confirmed', 'cancelled'], true)) {
            PurchaseOrder::update((int) $id, ['status' => $status]);
            Session::flash('success', 'Statut mis à jour.');
        }
        redirect('/purchase-orders/' . $id);
    }

    public static function destroy(string $id): void
    {
        ModuleAccess::requireModule('purchase_orders');
        Csrf::verifyOrFail();
        PurchaseOrder::delete((int) $id);
        Session::flash('success', 'Bon de commande supprimé.');
        redirect('/purchase-orders');
    }

    public static function printView(string $id): void
    {
        ModuleAccess::requireModule('purchase_orders');
        $po = PurchaseOrder::findWithRelations((int) $id);
        if (!$po) { http_response_code(404); die('Bon de commande introuvable.'); }

        View::render('purchase_orders/print', [
            'po' => $po,
            'items' => PurchaseOrder::items((int) $id),
            'company' => \App\Models\CompanySettings::get(),
        ], layout: null);
    }

    private static function parseItems(): array
    {
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unitPrices = $_POST['unit_price'] ?? [];

        $items = [];
        $total = 0.0;

        foreach ($descriptions as $i => $description) {
            $description = trim($description);
            if ($description === '') {
                continue;
            }
            $qty = (float) str_replace(',', '.', $quantities[$i] ?? 1);
            $price = (float) str_replace(',', '.', $unitPrices[$i] ?? 0);
            $lineTotal = round($qty * $price, 2);

            $items[] = ['description' => $description, 'quantity' => $qty, 'unit_price' => $price, 'total' => $lineTotal];
            $total += $lineTotal;
        }

        return [$items, round($total, 2)];
    }
}
