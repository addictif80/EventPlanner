<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\Product;

class ProductController
{
    public static function index(): void
    {
        View::render('products/index', ['title' => 'Catalogue', 'products' => Product::all('category ASC, name ASC')]);
    }

    public static function create(): void
    {
        View::render('products/form', ['title' => 'Nouvel article', 'product' => null]);
    }

    public static function store(): void
    {
        Csrf::verifyOrFail();
        Product::create(self::validated());
        Session::flash('success', 'Article ajouté au catalogue.');
        redirect('/products');
    }

    public static function edit(string $id): void
    {
        $product = Product::find((int) $id);
        if (!$product) { http_response_code(404); die('Article introuvable.'); }
        View::render('products/form', ['title' => 'Modifier l\'article', 'product' => $product]);
    }

    public static function update(string $id): void
    {
        Csrf::verifyOrFail();
        Product::update((int) $id, self::validated());
        Session::flash('success', 'Article mis à jour.');
        redirect('/products');
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();
        Product::delete((int) $id);
        Session::flash('success', 'Article supprimé.');
        redirect('/products');
    }

    private static function validated(): array
    {
        return [
            'name' => input('name', ''),
            'description' => input('description', ''),
            'unit_price' => (float) str_replace(',', '.', input('unit_price', '0')),
            'unit' => input('unit', 'unité'),
            'category' => input('category', ''),
            'stock_quantity' => input('stock_quantity', '') !== '' ? (int) input('stock_quantity', '0') : null,
        ];
    }
}
