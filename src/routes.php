<?php

use App\Core\Auth;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ClientController;
use App\Controllers\EventController;
use App\Controllers\ProviderController;
use App\Controllers\VenueController;
use App\Controllers\ProductController;
use App\Controllers\QuoteController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Controllers\TaskController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;

$router = new Router();

// --- Auth (public) ---
$router->get('/login', fn() => AuthController::showLogin());
$router->post('/login', fn() => AuthController::login());
$router->get('/logout', fn() => AuthController::logout());

// --- Everything below requires authentication ---
// (enforced in public/index.php before dispatch, based on the request path,
// since route closures here are only registered, not executed, at this point)

$router->get('/', fn() => DashboardController::index());

// Clients
$router->get('/clients', fn() => ClientController::index());
$router->get('/clients/create', fn() => ClientController::create());
$router->post('/clients', fn() => ClientController::store());
$router->get('/clients/{id}', fn($p) => ClientController::show($p['id']));
$router->get('/clients/{id}/edit', fn($p) => ClientController::edit($p['id']));
$router->post('/clients/{id}', fn($p) => ClientController::update($p['id']));
$router->post('/clients/{id}/delete', fn($p) => ClientController::destroy($p['id']));

// Events
$router->get('/events', fn() => EventController::index());
$router->get('/events/create', fn() => EventController::create());
$router->post('/events', fn() => EventController::store());
$router->get('/events/{id}', fn($p) => EventController::show($p['id']));
$router->get('/events/{id}/edit', fn($p) => EventController::edit($p['id']));
$router->post('/events/{id}', fn($p) => EventController::update($p['id']));
$router->post('/events/{id}/delete', fn($p) => EventController::destroy($p['id']));
$router->post('/events/{id}/providers', fn($p) => EventController::attachProvider($p['id']));
$router->post('/events/{id}/providers/{providerLinkId}/delete', fn($p) => EventController::detachProvider($p['id'], $p['providerLinkId']));

// Providers
$router->get('/providers', fn() => ProviderController::index());
$router->get('/providers/create', fn() => ProviderController::create());
$router->post('/providers', fn() => ProviderController::store());
$router->get('/providers/{id}/edit', fn($p) => ProviderController::edit($p['id']));
$router->post('/providers/{id}', fn($p) => ProviderController::update($p['id']));
$router->post('/providers/{id}/delete', fn($p) => ProviderController::destroy($p['id']));

// Venues
$router->get('/venues', fn() => VenueController::index());
$router->get('/venues/create', fn() => VenueController::create());
$router->post('/venues', fn() => VenueController::store());
$router->get('/venues/{id}/edit', fn($p) => VenueController::edit($p['id']));
$router->post('/venues/{id}', fn($p) => VenueController::update($p['id']));
$router->post('/venues/{id}/delete', fn($p) => VenueController::destroy($p['id']));

// Products
$router->get('/products', fn() => ProductController::index());
$router->get('/products/create', fn() => ProductController::create());
$router->post('/products', fn() => ProductController::store());
$router->get('/products/{id}/edit', fn($p) => ProductController::edit($p['id']));
$router->post('/products/{id}', fn($p) => ProductController::update($p['id']));
$router->post('/products/{id}/delete', fn($p) => ProductController::destroy($p['id']));

// Quotes
$router->get('/quotes', fn() => QuoteController::index());
$router->get('/quotes/create', fn() => QuoteController::create());
$router->post('/quotes', fn() => QuoteController::store());
$router->get('/quotes/{id}', fn($p) => QuoteController::show($p['id']));
$router->get('/quotes/{id}/edit', fn($p) => QuoteController::edit($p['id']));
$router->post('/quotes/{id}', fn($p) => QuoteController::update($p['id']));
$router->post('/quotes/{id}/delete', fn($p) => QuoteController::destroy($p['id']));
$router->post('/quotes/{id}/status', fn($p) => QuoteController::updateStatus($p['id']));
$router->post('/quotes/{id}/send', fn($p) => QuoteController::send($p['id']));
$router->post('/quotes/{id}/convert', fn($p) => QuoteController::convertToInvoice($p['id']));
$router->get('/quotes/{id}/print', fn($p) => QuoteController::printView($p['id']));

// Invoices
$router->get('/invoices', fn() => InvoiceController::index());
$router->get('/invoices/create', fn() => InvoiceController::create());
$router->post('/invoices', fn() => InvoiceController::store());
$router->get('/invoices/{id}', fn($p) => InvoiceController::show($p['id']));
$router->get('/invoices/{id}/edit', fn($p) => InvoiceController::edit($p['id']));
$router->post('/invoices/{id}', fn($p) => InvoiceController::update($p['id']));
$router->post('/invoices/{id}/delete', fn($p) => InvoiceController::destroy($p['id']));
$router->post('/invoices/{id}/send', fn($p) => InvoiceController::send($p['id']));
$router->get('/invoices/{id}/print', fn($p) => InvoiceController::printView($p['id']));
$router->post('/invoices/{id}/payments', fn($p) => PaymentController::store($p['id']));
$router->post('/invoices/{id}/payments/{paymentId}/delete', fn($p) => PaymentController::destroy($p['id'], $p['paymentId']));

// Tasks (scoped to an event)
$router->post('/events/{id}/tasks', fn($p) => TaskController::store($p['id']));
$router->post('/tasks/{id}/status', fn($p) => TaskController::updateStatus($p['id']));
$router->post('/tasks/{id}/delete', fn($p) => TaskController::destroy($p['id']));

// Settings
$router->get('/settings', fn() => SettingsController::index());
$router->post('/settings/company', fn() => SettingsController::updateCompany());
$router->post('/settings/smtp', fn() => SettingsController::updateSmtp());
$router->post('/settings/smtp/test', fn() => SettingsController::testSmtp());

// Users (admin only)
$router->get('/users', fn() => UserController::index());
$router->get('/users/create', fn() => UserController::create());
$router->post('/users', fn() => UserController::store());
$router->get('/users/{id}/edit', fn($p) => UserController::edit($p['id']));
$router->post('/users/{id}', fn($p) => UserController::update($p['id']));
$router->post('/users/{id}/delete', fn($p) => UserController::destroy($p['id']));

return $router;
