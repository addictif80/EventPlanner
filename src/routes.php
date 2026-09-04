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
use App\Controllers\GuestController;
use App\Controllers\TicketController;
use App\Controllers\ContractController;
use App\Controllers\DocumentController;
use App\Controllers\PurchaseOrderController;
use App\Controllers\EquipmentController;
use App\Controllers\CreditNoteController;
use App\Controllers\EventOpsController;
use App\Controllers\NoteController;
use App\Controllers\SurveyController;
use App\Controllers\PortalController;
use App\Controllers\ReportController;
use App\Controllers\ExportController;
use App\Controllers\CalendarController;
use App\Controllers\StripeController;
use App\Controllers\RegisterController;
use App\Controllers\JoinController;
use App\Controllers\AdminOrganizationInviteController;
use App\Controllers\AdminController;
use App\Controllers\AdminUserController;
use App\Controllers\AdminDocumentController;
use App\Controllers\AdminBlocklistController;
use App\Controllers\AdminTicketController;
use App\Controllers\AdminSettingsController;
use App\Controllers\AdminOfferController;
use App\Controllers\SupportController;
use App\Controllers\SubscriptionController;
use App\Controllers\LandingController;
use App\Controllers\PageController;
use App\Controllers\AdminSiteController;
use App\Controllers\AccountController;
use App\Controllers\ClientMessageController;
use App\Controllers\NotificationController;
use App\Controllers\OrgLogoController;
use App\Controllers\SearchController;
use App\Controllers\PosController;
use App\Controllers\PosReceiptController;
use App\Controllers\AiController;
use App\Controllers\BookingSettingsController;
use App\Controllers\PublicBookingController;

$router = new Router();

// --- Auth (public) ---
$router->get('/login', fn() => AuthController::showLogin());
$router->post('/login', fn() => AuthController::login());
$router->get('/logout', fn() => AuthController::logout());
$router->get('/register', fn() => RegisterController::show());
$router->post('/register', fn() => RegisterController::register());
$router->get('/join/{token}', fn($p) => JoinController::show($p['token']));
$router->post('/join/{token}', fn($p) => JoinController::store($p['token']));
$router->get('/forgot-password', fn() => AuthController::showForgotPassword());
$router->post('/forgot-password', fn() => AuthController::sendResetLink());
$router->get('/reset-password/{token}', fn($p) => AuthController::showResetPassword($p['token']));
$router->post('/reset-password/{token}', fn($p) => AuthController::resetPassword($p['token']));

// --- Everything below requires authentication ---
// (enforced in public/index.php before dispatch, based on the request path,
// since route closures here are only registered, not executed, at this point)
// "/" is the exception: it is public and shows the landing page to visitors,
// but dispatches to the dashboard for already-authenticated users.

$router->get('/', fn() => LandingController::index());
$router->get('/page/{slug}', fn($p) => PageController::show($p['slug']));
$router->get('/org-logo/{id}', fn($p) => OrgLogoController::show($p['id']));

// Clients
$router->get('/clients', fn() => ClientController::index());
$router->get('/clients/create', fn() => ClientController::create());
$router->post('/clients', fn() => ClientController::store());
$router->get('/clients/{id}', fn($p) => ClientController::show($p['id']));
$router->get('/clients/{id}/edit', fn($p) => ClientController::edit($p['id']));
$router->post('/clients/{id}', fn($p) => ClientController::update($p['id']));
$router->post('/clients/{id}/delete', fn($p) => ClientController::destroy($p['id']));
$router->post('/clients/{id}/erasure-request/dismiss', fn($p) => ClientController::dismissErasureRequest($p['id']));
$router->get('/clients/{id}/messages.json', fn($p) => ClientMessageController::staffPoll($p['id']));
$router->post('/clients/{id}/messages', fn($p) => ClientMessageController::staffSend($p['id']));

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
$router->post('/invoices/{id}/remind', fn($p) => InvoiceController::sendReminder($p['id']));
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
$router->post('/settings/email-templates', fn() => SettingsController::updateEmailTemplates());
$router->post('/settings/ics-token', fn() => SettingsController::generateIcsToken());
$router->post('/settings/organization/delete', fn() => SettingsController::destroyOrganization());

$router->get('/account', fn() => AccountController::show());
$router->post('/account/profile', fn() => AccountController::updateProfile());
$router->post('/account/password', fn() => AccountController::updatePassword());
$router->get('/account/export.json', fn() => AccountController::exportData());
$router->post('/account/delete', fn() => AccountController::destroy());

$router->get('/search.json', fn() => SearchController::search());

$router->get('/notifications.json', fn() => NotificationController::userFeed());
$router->post('/notifications/{id}/read', fn($p) => NotificationController::userMarkRead($p['id']));
$router->post('/notifications/read-all', fn() => NotificationController::userMarkAllRead());
$router->post('/push/subscribe', fn() => NotificationController::userSubscribe());
$router->post('/push/unsubscribe', fn() => NotificationController::userUnsubscribe());
$router->get('/push/vapid-public-key.json', fn() => NotificationController::vapidPublicKey());

// Users (admin only)
$router->get('/users', fn() => UserController::index());
$router->get('/users/create', fn() => UserController::create());
$router->post('/users', fn() => UserController::store());
$router->get('/users/{id}/edit', fn($p) => UserController::edit($p['id']));
$router->post('/users/{id}', fn($p) => UserController::update($p['id']));
$router->post('/users/{id}/delete', fn($p) => UserController::destroy($p['id']));
$router->post('/users/{id}/suspend', fn($p) => UserController::toggleSuspend($p['id']));
$router->post('/users/{id}/resend-invite', fn($p) => UserController::resendInvite($p['id']));
$router->get('/invite/{token}', fn($p) => UserController::showAcceptInvite($p['token']));
$router->post('/invite/{token}', fn($p) => UserController::acceptInvite($p['token']));

// Guests, RSVP, seating
$router->get('/events/{id}/guests', fn($p) => GuestController::index($p['id']));
$router->post('/events/{id}/guests', fn($p) => GuestController::store($p['id']));
$router->post('/guests/{id}', fn($p) => GuestController::update($p['id']));
$router->post('/guests/{id}/delete', fn($p) => GuestController::destroy($p['id']));
$router->post('/guests/{id}/invite', fn($p) => GuestController::sendInvite($p['id']));
$router->post('/events/{id}/tables', fn($p) => GuestController::storeTable($p['id']));
$router->post('/events/{id}/tables/{tableId}/delete', fn($p) => GuestController::destroyTable($p['id'], $p['tableId']));

// Ticketing + check-in
$router->get('/events/{id}/tickets', fn($p) => TicketController::index($p['id']));
$router->post('/events/{id}/ticket-categories', fn($p) => TicketController::storeCategory($p['id']));
$router->post('/events/{id}/ticket-categories/{catId}/delete', fn($p) => TicketController::destroyCategory($p['id'], $p['catId']));
$router->post('/events/{id}/tickets/generate', fn($p) => TicketController::generate($p['id']));
$router->get('/events/{id}/tickets/send', fn($p) => TicketController::sendForm($p['id']));
$router->post('/events/{id}/tickets/send', fn($p) => TicketController::sendBulk($p['id']));
$router->post('/events/{id}/tickets/{ticketId}/cancel', fn($p) => TicketController::cancel($p['id'], $p['ticketId']));
$router->get('/events/{id}/checkin', fn($p) => TicketController::checkinForm($p['id']));
$router->post('/events/{id}/checkin', fn($p) => TicketController::checkinSubmit($p['id']));
$router->get('/events/{id}/checkin/stats.json', fn($p) => TicketController::checkinStats($p['id']));

// Caisse (point de vente)
$router->get('/pos', fn() => PosController::index());
$router->post('/pos/open', fn() => PosController::open());
$router->get('/pos/sessions', fn() => PosController::sessions());
$router->get('/pos/sessions/{id}', fn($p) => PosController::showSession($p['id']));
$router->get('/pos/sales/{id}', fn($p) => PosController::saleShow($p['id']));
$router->get('/pos/sales/{id}/pdf', fn($p) => PosController::salePdf($p['id']));
$router->get('/pos/{id}', fn($p) => PosController::till($p['id']));
$router->post('/pos/{id}/sell', fn($p) => PosController::sell($p['id']));
$router->post('/pos/{id}/movement', fn($p) => PosController::movement($p['id']));
$router->get('/pos/{id}/close', fn($p) => PosController::closeForm($p['id']));
$router->post('/pos/{id}/close', fn($p) => PosController::close($p['id']));

// Ticket de caisse — accès public en autonomie (email ou QR code)
$router->get('/pos-receipt/{token}', fn($p) => PosReceiptController::show($p['token']));
$router->get('/pos-receipt/{token}/download', fn($p) => PosReceiptController::download($p['token']));

// Assistant IA
$router->post('/quotes/ai-draft', fn() => AiController::draftQuote());
$router->post('/invoices/{id}/ai-reminder', fn($p) => AiController::draftReminder($p['id']));

// Prise de rendez-vous en ligne
$router->get('/booking-settings', fn() => BookingSettingsController::edit());
$router->post('/booking-settings', fn() => BookingSettingsController::update());
$router->get('/appointments', fn() => BookingSettingsController::index());
$router->post('/appointments/{id}/cancel', fn($p) => BookingSettingsController::cancel($p['id']));

// Page publique de réservation (prospect)
$router->get('/booking/cancel/{token}', fn($p) => PublicBookingController::cancel($p['token']));
$router->get('/booking/{slug}/slots.json', fn($p) => PublicBookingController::slotsJson($p['slug']));
$router->get('/booking/{slug}', fn($p) => PublicBookingController::show($p['slug']));
$router->post('/booking/{slug}', fn($p) => PublicBookingController::store($p['slug']));

// Contracts + e-signature
$router->get('/contracts', fn() => ContractController::index());
$router->get('/contracts/create', fn() => ContractController::create());
$router->post('/contracts', fn() => ContractController::store());
$router->get('/contracts/{id}', fn($p) => ContractController::show($p['id']));
$router->post('/contracts/{id}/send', fn($p) => ContractController::send($p['id']));
$router->post('/contracts/{id}/delete', fn($p) => ContractController::destroy($p['id']));

// Documents
$router->post('/documents', fn() => DocumentController::store());
$router->get('/documents/{id}/download', fn($p) => DocumentController::download($p['id']));
$router->post('/documents/{id}/delete', fn($p) => DocumentController::destroy($p['id']));

// Purchase orders (fournisseurs)
$router->get('/purchase-orders', fn() => PurchaseOrderController::index());
$router->get('/purchase-orders/create', fn() => PurchaseOrderController::create());
$router->post('/purchase-orders', fn() => PurchaseOrderController::store());
$router->get('/purchase-orders/{id}', fn($p) => PurchaseOrderController::show($p['id']));
$router->post('/purchase-orders/{id}/status', fn($p) => PurchaseOrderController::updateStatus($p['id']));
$router->post('/purchase-orders/{id}/delete', fn($p) => PurchaseOrderController::destroy($p['id']));
$router->get('/purchase-orders/{id}/print', fn($p) => PurchaseOrderController::printView($p['id']));

// Equipment / stock
$router->get('/equipment', fn() => EquipmentController::index());
$router->get('/equipment/create', fn() => EquipmentController::create());
$router->post('/equipment', fn() => EquipmentController::store());
$router->get('/equipment/{id}/edit', fn($p) => EquipmentController::edit($p['id']));
$router->post('/equipment/{id}', fn($p) => EquipmentController::update($p['id']));
$router->post('/equipment/{id}/delete', fn($p) => EquipmentController::destroy($p['id']));
$router->post('/events/{id}/equipment', fn($p) => EquipmentController::book($p['id']));
$router->post('/events/{id}/equipment/{bookingId}/delete', fn($p) => EquipmentController::unbook($p['id'], $p['bookingId']));

// Credit notes (avoirs)
$router->post('/invoices/{id}/credit-notes', fn($p) => CreditNoteController::store($p['id']));
$router->get('/credit-notes/{id}/print', fn($p) => CreditNoteController::printView($p['id']));

// Recurring invoices
$router->post('/invoices/{id}/recurring', fn($p) => InvoiceController::setRecurring($p['id']));
$router->post('/invoices/{id}/generate-next', fn($p) => InvoiceController::generateNext($p['id']));

// Jour-J : feuille de route, contacts d'urgence, incidents
$router->get('/events/{id}/dayof', fn($p) => EventOpsController::index($p['id']));
$router->post('/events/{id}/runsheet', fn($p) => EventOpsController::storeRunSheetItem($p['id']));
$router->post('/events/{id}/runsheet/{itemId}/delete', fn($p) => EventOpsController::destroyRunSheetItem($p['id'], $p['itemId']));
$router->post('/events/{id}/emergency-contacts', fn($p) => EventOpsController::storeEmergencyContact($p['id']));
$router->post('/events/{id}/emergency-contacts/{contactId}/delete', fn($p) => EventOpsController::destroyEmergencyContact($p['id'], $p['contactId']));
$router->post('/events/{id}/incidents', fn($p) => EventOpsController::storeIncident($p['id']));
$router->post('/events/{id}/incidents/{incidentId}/status', fn($p) => EventOpsController::updateIncidentStatus($p['id'], $p['incidentId']));

// Notes internes
$router->post('/events/{id}/notes', fn($p) => NoteController::storeForEvent($p['id']));
$router->post('/clients/{id}/notes', fn($p) => NoteController::storeForClient($p['id']));
$router->post('/notes/{id}/delete', fn($p) => NoteController::destroy($p['id']));

// Sondage de satisfaction
$router->post('/events/{id}/survey/send', fn($p) => SurveyController::send($p['id']));

// Portail client
$router->post('/clients/{id}/portal-link', fn($p) => PortalController::generateLink($p['id']));

// Reporting & exports
$router->get('/reports', fn() => ReportController::index());
$router->get('/reports/urssaf', fn() => ReportController::urssaf());
$router->get('/reports/urssaf/export.csv', fn() => ReportController::urssafExport());
$router->get('/export/invoices.csv', fn() => ExportController::invoicesCsv());
$router->get('/export/clients.csv', fn() => ExportController::clientsCsv());

// Journal d'activité
$router->get('/settings/activity-log', fn() => SettingsController::activityLog());

// Stripe (paiement en ligne optionnel)
$router->post('/invoices/{id}/stripe-link', fn($p) => StripeController::createPaymentLink($p['id']));
$router->get('/stripe/return/{id}', fn($p) => StripeController::handleReturn($p['id']));

// Abonnement (côté organisation)
$router->get('/subscription', fn() => SubscriptionController::index());
$router->post('/subscription/checkout', fn() => SubscriptionController::checkout());
$router->post('/subscription/change-plan', fn() => SubscriptionController::changePlan());
$router->post('/subscription/add-addon', fn() => SubscriptionController::addAddon());
$router->post('/subscription/remove-addon/{id}', fn($p) => SubscriptionController::removeAddon($p['id']));
$router->post('/subscription/cancel', fn() => SubscriptionController::cancel());
$router->post('/subscription/webhook', fn() => SubscriptionController::webhook());

// Support (tickets, côté organisation)
$router->get('/support', fn() => SupportController::index());
$router->get('/support/create', fn() => SupportController::create());
$router->post('/support', fn() => SupportController::store());
$router->get('/support/{id}', fn($p) => SupportController::show($p['id']));
$router->post('/support/{id}/reply', fn($p) => SupportController::reply($p['id']));

// --- Administration plateforme (super admin uniquement) ---
$router->get('/admin', fn() => AdminController::dashboard());
$router->get('/admin/organizations', fn() => AdminController::organizations());
$router->get('/admin/organizations/{id}', fn($p) => AdminController::showOrganization($p['id']));
$router->post('/admin/organizations/{id}/status', fn($p) => AdminController::toggleOrganizationStatus($p['id']));
$router->post('/admin/organizations/{id}/assign-plan', fn($p) => AdminController::assignPlan($p['id']));
$router->get('/admin/activity-log', fn() => AdminController::activityLog());

$router->get('/admin/offers', fn() => AdminOfferController::index());
$router->get('/admin/offers/plans/create', fn() => AdminOfferController::createPlan());
$router->post('/admin/offers/plans', fn() => AdminOfferController::storePlan());
$router->get('/admin/offers/plans/{id}/edit', fn($p) => AdminOfferController::editPlan($p['id']));
$router->post('/admin/offers/plans/{id}', fn($p) => AdminOfferController::updatePlan($p['id']));
$router->post('/admin/offers/plans/{id}/delete', fn($p) => AdminOfferController::destroyPlan($p['id']));
$router->get('/admin/offers/modules/create', fn() => AdminOfferController::createModule());
$router->post('/admin/offers/modules', fn() => AdminOfferController::storeModule());
$router->get('/admin/offers/modules/{id}/edit', fn($p) => AdminOfferController::editModule($p['id']));
$router->post('/admin/offers/modules/{id}', fn($p) => AdminOfferController::updateModule($p['id']));
$router->get('/admin/offers/packages/create', fn() => AdminOfferController::createPackage());
$router->post('/admin/offers/packages', fn() => AdminOfferController::storePackage());
$router->get('/admin/offers/packages/{id}/edit', fn($p) => AdminOfferController::editPackage($p['id']));
$router->post('/admin/offers/packages/{id}', fn($p) => AdminOfferController::updatePackage($p['id']));
$router->post('/admin/offers/packages/{id}/delete', fn($p) => AdminOfferController::destroyPackage($p['id']));

$router->get('/admin/users', fn() => AdminUserController::index());
$router->get('/admin/users/{id}/edit', fn($p) => AdminUserController::edit($p['id']));
$router->post('/admin/users/{id}', fn($p) => AdminUserController::update($p['id']));
$router->post('/admin/users/{id}/impersonate', fn($p) => AdminUserController::impersonate($p['id']));
$router->post('/admin/stop-impersonating', fn() => AdminUserController::stopImpersonating());

$router->get('/admin/documents', fn() => AdminDocumentController::index());
$router->get('/admin/documents/{id}/download', fn($p) => AdminDocumentController::download($p['id']));
$router->post('/admin/documents/{id}/delete', fn($p) => AdminDocumentController::destroy($p['id']));

$router->get('/admin/blocklist', fn() => AdminBlocklistController::index());
$router->post('/admin/blocklist/ips', fn() => AdminBlocklistController::storeIp());
$router->post('/admin/blocklist/ips/{id}/delete', fn($p) => AdminBlocklistController::destroyIp($p['id']));
$router->post('/admin/blocklist/emails', fn() => AdminBlocklistController::storeEmail());
$router->post('/admin/blocklist/emails/{id}/delete', fn($p) => AdminBlocklistController::destroyEmail($p['id']));

$router->get('/admin/tickets', fn() => AdminTicketController::index());
$router->get('/admin/tickets/{id}', fn($p) => AdminTicketController::show($p['id']));
$router->post('/admin/tickets/{id}/reply', fn($p) => AdminTicketController::reply($p['id']));
$router->post('/admin/tickets/{id}/status', fn($p) => AdminTicketController::updateStatus($p['id']));

$router->get('/admin/settings', fn() => AdminSettingsController::index());
$router->post('/admin/settings/smtp', fn() => AdminSettingsController::updateSmtp());
$router->post('/admin/settings/smtp/test', fn() => AdminSettingsController::testSmtp());
$router->post('/admin/settings/billing', fn() => AdminSettingsController::updateBilling());
$router->post('/admin/settings/ai', fn() => AdminSettingsController::updateAi());
$router->get('/admin/invitations', fn() => AdminOrganizationInviteController::index());
$router->post('/admin/invitations', fn() => AdminOrganizationInviteController::store());
$router->post('/admin/invitations/{id}/resend', fn($p) => AdminOrganizationInviteController::resend($p['id']));
$router->post('/admin/invitations/{id}/revoke', fn($p) => AdminOrganizationInviteController::revoke($p['id']));

$router->get('/admin/pages', fn() => AdminSiteController::index());
$router->get('/admin/pages/create', fn() => AdminSiteController::createPage());
$router->post('/admin/pages', fn() => AdminSiteController::storePage());
$router->get('/admin/pages/{id}/edit', fn($p) => AdminSiteController::editPage($p['id']));
$router->post('/admin/pages/{id}', fn($p) => AdminSiteController::updatePage($p['id']));
$router->post('/admin/pages/{id}/delete', fn($p) => AdminSiteController::destroyPage($p['id']));
$router->post('/admin/menu', fn() => AdminSiteController::storeMenuItem());
$router->post('/admin/menu/{id}', fn($p) => AdminSiteController::updateMenuItem($p['id']));
$router->post('/admin/menu/{id}/delete', fn($p) => AdminSiteController::destroyMenuItem($p['id']));

$router->get('/admin/notifications.json', fn() => NotificationController::platformFeed());
$router->post('/admin/notifications/{id}/read', fn($p) => NotificationController::platformMarkRead($p['id']));
$router->post('/admin/notifications/read-all', fn() => NotificationController::platformMarkAllRead());
$router->post('/admin/push/subscribe', fn() => NotificationController::platformSubscribe());

// --- Routes publiques (sans authentification) ---
$router->get('/rsvp/{token}', fn($p) => GuestController::rsvpForm($p['token']));
$router->post('/rsvp/{token}', fn($p) => GuestController::rsvpSubmit($p['token']));
$router->post('/rsvp/{token}/delete', fn($p) => GuestController::rsvpDelete($p['token']));
$router->get('/sign/{token}', fn($p) => ContractController::signForm($p['token']));
$router->post('/sign/{token}', fn($p) => ContractController::signSubmit($p['token']));
$router->get('/survey/{token}', fn($p) => SurveyController::form($p['token']));
$router->post('/survey/{token}', fn($p) => SurveyController::submit($p['token']));
$router->get('/portal/{token}', fn($p) => PortalController::show($p['token']));
$router->post('/portal/{token}/quotes/{id}/accept', fn($p) => PortalController::acceptQuote($p['token'], $p['id']));
$router->post('/portal/{token}/quotes/{id}/refuse', fn($p) => PortalController::refuseQuote($p['token'], $p['id']));
$router->post('/portal/{token}/invoices/{id}/pay', fn($p) => StripeController::createPortalPaymentLink($p['token'], $p['id']));
$router->get('/portal/{token}/export.json', fn($p) => PortalController::exportData($p['token']));
$router->post('/portal/{token}/erasure-request', fn($p) => PortalController::requestErasure($p['token']));
$router->get('/portal/{token}/messages.json', fn($p) => ClientMessageController::portalPoll($p['token']));
$router->post('/portal/{token}/messages', fn($p) => ClientMessageController::portalSend($p['token']));
$router->get('/portal/{token}/notifications.json', fn($p) => NotificationController::portalFeed($p['token']));
$router->post('/portal/{token}/notifications/{id}/read', fn($p) => NotificationController::portalMarkRead($p['token'], $p['id']));
$router->post('/portal/{token}/notifications/read-all', fn($p) => NotificationController::portalMarkAllRead($p['token']));
$router->post('/portal/{token}/push/subscribe', fn($p) => NotificationController::portalSubscribe($p['token']));
$router->get('/calendar/{token}.ics', fn($p) => CalendarController::feed($p['token']));

return $router;
