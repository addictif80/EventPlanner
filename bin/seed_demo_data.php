<?php

/**
 * (Re)builds the public demo account from scratch: deletes the previous demo
 * organization (if any — cascades to every tenant table via FK) and
 * provisions a fresh one with realistic sample data across every module, so
 * a visitor can explore the whole app before signing up. Safe to run
 * repeatedly; intended as a nightly cron job so the shared demo account
 * never accumulates visitor edits indefinitely:
 *   0 3 * * * php /path/to/EventPlanner/bin/seed_demo_data.php
 *
 * Credentials are fixed (App\Core\Demo::EMAIL/PASSWORD) and published on the
 * landing page; DemoController::start() also logs a visitor straight in
 * without them having to type anything.
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Controllers\RegisterController;
use App\Core\ActivityLog;
use App\Core\Database;
use App\Core\Demo;
use App\Models\Appointment;
use App\Models\BookingSettings;
use App\Models\Client;
use App\Models\CompanySettings;
use App\Models\Contract;
use App\Models\Event;
use App\Models\EventTable;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\Venue;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

const DEMO_ORG_NAME = 'Agence Événements Démo';

$pdo = Database::connection();

// --- 1. Wipe any previous demo organization (cascades to every tenant table) ---
$stmt = $pdo->prepare('SELECT id FROM organizations WHERE is_demo = 1');
$stmt->execute();
foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $oldId) {
    $pdo->prepare('DELETE FROM organizations WHERE id = ?')->execute([$oldId]);
}

// --- 2. Provision a fresh organization + admin user, same as self-registration ---
$result = RegisterController::provision(DEMO_ORG_NAME, 'Alex Démo', Demo::EMAIL, Demo::PASSWORD, 2 /* Historique (legacy) : tous les modules, sans limite */);
$orgId = $result['organization_id'];
$userId = $result['user_id'];

Organization::update($orgId, ['is_demo' => 1]);

// Every scoped Model call below reads Auth::organizationId()/Auth::id() — this
// is a CLI script with no real login session, so it is faked here exactly
// like the other bin/*.php scripts (see send_business_alerts.php etc.).
$_SESSION['organization_id'] = $orgId;
$_SESSION['user_id'] = $userId;

CompanySettings::update([
    'company_name' => DEMO_ORG_NAME,
    'legal_form' => 'SASU',
    'address' => '12 rue des Fêtes',
    'postal_code' => '75011',
    'city' => 'Paris',
    'country' => 'France',
    'phone' => '01 23 45 67 89',
    'email' => Demo::EMAIL,
    'website' => 'https://www.eventplanner-demo.fr',
    'siret' => '123 456 789 00012',
    'default_tax_rate' => 20,
    'brand_color' => '#b48c1e',
    'invoice_footer' => "Paiement à réception. IBAN démonstration : FR76 0000 0000 0000 0000 0000 000",
]);

function eventTypeId(\PDO $pdo, int $orgId, string $name): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM event_types WHERE organization_id = ? AND name = ? LIMIT 1');
    $stmt->execute([$orgId, $name]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

// --- 3. Venues ---
$venueIds = [];
foreach ([
    ['name' => 'Château de Vincennes Réception', 'address' => '1 avenue de Paris', 'postal_code' => '94300', 'city' => 'Vincennes', 'capacity' => 200, 'contact_name' => 'Marie Lefort', 'contact_phone' => '01 45 67 89 10', 'contact_email' => 'contact@chateau-vincennes-demo.fr'],
    ['name' => 'Loft Canal Saint-Martin', 'address' => '25 quai de Valmy', 'postal_code' => '75010', 'city' => 'Paris', 'capacity' => 80, 'contact_name' => 'Julien Marchand', 'contact_phone' => '01 22 33 44 55', 'contact_email' => 'loft@demo.fr'],
    ['name' => 'Salle des Fêtes de Neuilly', 'address' => '8 rue du Château', 'postal_code' => '92200', 'city' => 'Neuilly-sur-Seine', 'capacity' => 150, 'contact_name' => '', 'contact_phone' => '', 'contact_email' => ''],
] as $v) {
    $venueIds[] = Venue::create($v);
}

// --- 4. Providers (prestataires) ---
$providerIds = [];
foreach ([
    ['name' => 'Traiteur Saveurs & Co', 'category' => 'Traiteur', 'contact_name' => 'Claire Dupuis', 'email' => 'contact@saveurs-co-demo.fr', 'phone' => '06 12 34 56 78', 'rating' => 5],
    ['name' => 'DJ Maxime Events', 'category' => 'Animation musicale', 'contact_name' => 'Maxime Petit', 'email' => 'maxime@demo.fr', 'phone' => '06 98 76 54 32', 'rating' => 4],
    ['name' => 'Fleurs de Léa', 'category' => 'Décoration florale', 'contact_name' => 'Léa Bernard', 'email' => 'lea@demo.fr', 'phone' => '06 11 22 33 44', 'rating' => 5],
    ['name' => 'Studio Photo Instant', 'category' => 'Photographe', 'contact_name' => 'Nicolas Roy', 'email' => 'nicolas@demo.fr', 'phone' => '06 55 66 77 88', 'rating' => 4],
] as $p) {
    $providerIds[] = Provider::create($p);
}

// --- 5. Products (catalogue, avec/sans stock pour la caisse) ---
$productIds = [];
foreach ([
    ['name' => 'Coupe de champagne', 'category' => 'Bar', 'unit_price' => 8, 'unit' => 'unité', 'stock_quantity' => 240],
    ['name' => 'Cocktail sans alcool', 'category' => 'Bar', 'unit_price' => 5, 'unit' => 'unité', 'stock_quantity' => 150],
    ['name' => 'Part de gâteau', 'category' => 'Restauration', 'unit_price' => 6, 'unit' => 'part', 'stock_quantity' => 100],
    ['name' => 'Entrée du buffet', 'category' => 'Restauration', 'unit_price' => 12, 'unit' => 'portion', 'stock_quantity' => 80],
    ['name' => "Forfait animation DJ (soirée)", 'category' => 'Prestation', 'unit_price' => 950, 'unit' => 'forfait', 'stock_quantity' => null],
    ['name' => 'Location vidéoprojecteur', 'category' => 'Matériel', 'unit_price' => 80, 'unit' => 'jour', 'stock_quantity' => 6],
    ['name' => 'Bouquet de table', 'category' => 'Décoration', 'unit_price' => 35, 'unit' => 'unité', 'stock_quantity' => 40],
    ['name' => 'Forfait photographe (4h)', 'category' => 'Prestation', 'unit_price' => 600, 'unit' => 'forfait', 'stock_quantity' => null],
] as $p) {
    $productIds[] = Product::create($p);
}

// --- 6. Clients ---
$clients = [
    ['type' => 'particulier', 'first_name' => 'Camille', 'last_name' => 'Girard', 'email' => 'camille.girard@example-demo.fr', 'phone' => '06 01 02 03 04', 'city' => 'Paris', 'postal_code' => '75011'],
    ['type' => 'particulier', 'first_name' => 'Thomas', 'last_name' => 'Lefevre', 'email' => 'thomas.lefevre@example-demo.fr', 'phone' => '06 02 03 04 05', 'city' => 'Lyon', 'postal_code' => '69003'],
    ['type' => 'entreprise', 'company_name' => 'Nova Technologies', 'first_name' => 'Sophie', 'last_name' => 'Martin', 'email' => 'sophie.martin@nova-demo.fr', 'phone' => '01 40 50 60 70', 'city' => 'Paris', 'postal_code' => '75008'],
    ['type' => 'particulier', 'first_name' => 'Julie', 'last_name' => 'Rousseau', 'email' => 'julie.rousseau@example-demo.fr', 'phone' => '06 03 04 05 06', 'city' => 'Versailles', 'postal_code' => '78000'],
    ['type' => 'entreprise', 'company_name' => 'Atelier Créatif SARL', 'first_name' => 'Marc', 'last_name' => 'Simon', 'email' => 'marc.simon@atelier-demo.fr', 'phone' => '01 33 44 55 66', 'city' => 'Boulogne-Billancourt', 'postal_code' => '92100'],
    ['type' => 'particulier', 'first_name' => 'Laura', 'last_name' => 'Fontaine', 'email' => 'laura.fontaine@example-demo.fr', 'phone' => '06 04 05 06 07', 'city' => 'Paris', 'postal_code' => '75015'],
];
$clientIds = [];
foreach ($clients as $c) {
    $clientIds[] = Client::create($c);
}
[$cCamille, $cThomas, $cNova, $cJulie, $cAtelier, $cLaura] = $clientIds;

// --- 7. Events ---
$today = new DateTime();
$events = [
    ['client_id' => $cCamille, 'event_type_id' => eventTypeId($pdo, $orgId, 'Mariage'), 'venue_id' => $venueIds[0], 'title' => 'Mariage Camille & Antoine', 'description' => "Mariage champêtre, 120 invités, cocktail puis dîner assis.", 'event_date' => (clone $today)->modify('+45 days')->format('Y-m-d'), 'guests_count' => 120, 'status' => 'confirmed', 'budget' => 18000],
    ['client_id' => $cNova, 'event_type_id' => eventTypeId($pdo, $orgId, 'Séminaire / Corporate'), 'venue_id' => $venueIds[1], 'title' => 'Séminaire annuel Nova Technologies', 'description' => "Séminaire d'entreprise, 60 participants, format journée + soirée networking.", 'event_date' => (clone $today)->modify('+20 days')->format('Y-m-d'), 'guests_count' => 60, 'status' => 'confirmed', 'budget' => 9500],
    ['client_id' => $cThomas, 'event_type_id' => eventTypeId($pdo, $orgId, 'Anniversaire'), 'venue_id' => $venueIds[2], 'title' => 'Anniversaire 40 ans de Thomas', 'description' => '40 invités, soirée à thème.', 'event_date' => (clone $today)->modify('+10 days')->format('Y-m-d'), 'guests_count' => 40, 'status' => 'confirmed', 'budget' => 4200],
    ['client_id' => $cJulie, 'event_type_id' => eventTypeId($pdo, $orgId, 'Baptême'), 'venue_id' => null, 'title' => 'Baptême de la petite Léa', 'description' => 'Réception familiale, 30 invités.', 'event_date' => (clone $today)->modify('+5 days')->format('Y-m-d'), 'guests_count' => 30, 'status' => 'draft', 'budget' => 1800],
    ['client_id' => $cAtelier, 'event_type_id' => eventTypeId($pdo, $orgId, 'Soirée privée'), 'venue_id' => $venueIds[1], 'title' => "Soirée de lancement Atelier Créatif", 'description' => 'Lancement produit, presse et clients VIP.', 'event_date' => (clone $today)->modify('-15 days')->format('Y-m-d'), 'guests_count' => 90, 'status' => 'completed', 'budget' => 7000],
    ['client_id' => $cLaura, 'event_type_id' => eventTypeId($pdo, $orgId, 'Anniversaire'), 'venue_id' => $venueIds[2], 'title' => 'Anniversaire de Laura', 'description' => '25 invités.', 'event_date' => (clone $today)->modify('-40 days')->format('Y-m-d'), 'guests_count' => 25, 'status' => 'completed', 'budget' => 2200],
    ['client_id' => $cCamille, 'event_type_id' => eventTypeId($pdo, $orgId, 'Festival / Concert'), 'venue_id' => $venueIds[0], 'title' => 'Festival Été en Fête', 'description' => 'Billetterie ouverte au public, 300 places.', 'event_date' => (clone $today)->modify('+60 days')->format('Y-m-d'), 'guests_count' => 300, 'status' => 'confirmed', 'budget' => 25000],
];
$eventIds = [];
foreach ($events as $e) {
    $eventIds[] = Event::create(array_merge($e, ['created_by' => $userId]));
}
[$evMariage, $evSeminaire, $evAnniv40, $evBapteme, $evLancement, $evAnnivLaura, $evFestival] = $eventIds;

// --- 8. Quotes ---
$quotesData = [
    ['client_id' => $cCamille, 'event_id' => $evMariage, 'status' => 'accepted', 'items' => [
        ['description' => 'Traiteur - menu 3 services (120 pers.)', 'quantity' => 120, 'unit_price' => 95],
        ['description' => 'Animation DJ soirée', 'quantity' => 1, 'unit_price' => 950],
        ['description' => 'Décoration florale complète', 'quantity' => 1, 'unit_price' => 1400],
    ]],
    ['client_id' => $cNova, 'event_id' => $evSeminaire, 'status' => 'accepted', 'items' => [
        ['description' => 'Location salle + régie technique', 'quantity' => 1, 'unit_price' => 3200],
        ['description' => 'Restauration journée (60 pers.)', 'quantity' => 60, 'unit_price' => 45],
    ]],
    ['client_id' => $cThomas, 'event_id' => $evAnniv40, 'status' => 'sent', 'items' => [
        ['description' => 'Traiteur cocktail dînatoire (40 pers.)', 'quantity' => 40, 'unit_price' => 55],
        ['description' => 'Animation DJ', 'quantity' => 1, 'unit_price' => 600],
    ]],
    ['client_id' => $cJulie, 'event_id' => $evBapteme, 'status' => 'draft', 'items' => [
        ['description' => 'Traiteur brunch (30 pers.)', 'quantity' => 30, 'unit_price' => 28],
    ]],
    ['client_id' => $cLaura, 'event_id' => $evAnnivLaura, 'status' => 'refused', 'items' => [
        ['description' => 'Forfait salle + traiteur', 'quantity' => 1, 'unit_price' => 2500],
    ]],
    ['client_id' => $cCamille, 'event_id' => $evFestival, 'status' => 'sent', 'items' => [
        ['description' => 'Régie son et scène', 'quantity' => 1, 'unit_price' => 6000],
        ['description' => "Sécurité et billetterie", 'quantity' => 1, 'unit_price' => 3000],
    ]],
];
$quoteIds = [];
foreach ($quotesData as $q) {
    $subtotal = array_sum(array_map(fn($i) => $i['quantity'] * $i['unit_price'], $q['items']));
    $taxAmount = round($subtotal * 0.20, 2);
    $id = Quote::create([
        'quote_number' => Quote::nextNumber(),
        'client_id' => $q['client_id'],
        'event_id' => $q['event_id'],
        'status' => $q['status'],
        'issue_date' => date('Y-m-d', strtotime('-10 days')),
        'valid_until' => date('Y-m-d', strtotime('+20 days')),
        'subtotal' => $subtotal,
        'tax_rate' => 20,
        'tax_amount' => $taxAmount,
        'total' => $subtotal + $taxAmount,
        'notes' => 'Devis de démonstration — modifiable librement.',
    ]);
    Quote::replaceItems($id, array_map(fn($i) => array_merge($i, ['total' => $i['quantity'] * $i['unit_price']]), $q['items']));
    $quoteIds[] = $id;
}
[$qMariage, $qSeminaire, , , , ] = $quoteIds;

// --- 9. Invoices + payments ---
$invoicesData = [
    ['client_id' => $cCamille, 'event_id' => $evMariage, 'quote_id' => $qMariage, 'status' => 'partially_paid', 'paid_ratio' => 0.3, 'items' => [
        ['description' => 'Acompte 30% - Mariage Camille & Antoine', 'quantity' => 1, 'unit_price' => 6042],
    ]],
    ['client_id' => $cNova, 'event_id' => $evSeminaire, 'quote_id' => $qSeminaire, 'status' => 'paid', 'paid_ratio' => 1.0, 'items' => [
        ['description' => 'Solde séminaire Nova Technologies', 'quantity' => 1, 'unit_price' => 5340],
    ]],
    ['client_id' => $cAtelier, 'event_id' => $evLancement, 'quote_id' => null, 'status' => 'paid', 'paid_ratio' => 1.0, 'items' => [
        ['description' => 'Prestation soirée de lancement', 'quantity' => 1, 'unit_price' => 7000],
    ]],
    ['client_id' => $cLaura, 'event_id' => $evAnnivLaura, 'quote_id' => null, 'status' => 'overdue', 'paid_ratio' => 0, 'items' => [
        ['description' => 'Prestation anniversaire', 'quantity' => 1, 'unit_price' => 2100],
    ]],
    ['client_id' => $cThomas, 'event_id' => $evAnniv40, 'quote_id' => null, 'status' => 'sent', 'paid_ratio' => 0, 'items' => [
        ['description' => 'Acompte anniversaire 40 ans', 'quantity' => 1, 'unit_price' => 1000],
    ]],
];
foreach ($invoicesData as $inv) {
    $subtotal = array_sum(array_map(fn($i) => $i['quantity'] * $i['unit_price'], $inv['items']));
    $taxAmount = round($subtotal * 0.20, 2);
    $total = round($subtotal + $taxAmount, 2);
    $amountPaid = round($total * $inv['paid_ratio'], 2);

    $id = Invoice::create([
        'invoice_number' => Invoice::nextNumber(),
        'client_id' => $inv['client_id'],
        'event_id' => $inv['event_id'],
        'quote_id' => $inv['quote_id'],
        'type' => 'standalone',
        'status' => $inv['status'],
        'issue_date' => date('Y-m-d', strtotime('-25 days')),
        'due_date' => date('Y-m-d', strtotime($inv['status'] === 'overdue' ? '-5 days' : '+10 days')),
        'subtotal' => $subtotal,
        'tax_rate' => 20,
        'tax_amount' => $taxAmount,
        'total' => $total,
        'amount_paid' => $amountPaid,
    ]);
    Invoice::replaceItems($id, array_map(fn($i) => array_merge($i, ['total' => $i['quantity'] * $i['unit_price']]), $inv['items']));

    if ($amountPaid > 0) {
        $pdo->prepare('INSERT INTO payments (organization_id, invoice_id, amount, payment_date, method, reference, notes) VALUES (?, ?, ?, ?, "virement", ?, "Paiement de démonstration")')
            ->execute([$orgId, $id, $amountPaid, date('Y-m-d', strtotime('-20 days')), 'DEMO-' . $id]);
    }
}

// --- 10. Purchase orders (bons de commande), confirmés pour le mariage ---
foreach ([
    ['provider_id' => $providerIds[0], 'event_id' => $evMariage, 'items' => [['description' => 'Menu 3 services (120 pers.)', 'quantity' => 120, 'unit_price' => 62]]],
    ['provider_id' => $providerIds[2], 'event_id' => $evMariage, 'items' => [['description' => 'Décoration florale mariage', 'quantity' => 1, 'unit_price' => 1100]]],
] as $po) {
    $total = array_sum(array_map(fn($i) => $i['quantity'] * $i['unit_price'], $po['items']));
    $id = PurchaseOrder::create([
        'po_number' => PurchaseOrder::nextNumber(),
        'provider_id' => $po['provider_id'],
        'event_id' => $po['event_id'],
        'status' => 'confirmed',
        'issue_date' => date('Y-m-d', strtotime('-8 days')),
        'total' => $total,
    ]);
    PurchaseOrder::replaceItems($id, array_map(fn($i) => array_merge($i, ['total' => $i['quantity'] * $i['unit_price']]), $po['items']));
    PurchaseOrder::syncEventProvider($id);
}

// --- 11. Contract, signé pour le mariage ---
Contract::create([
    'client_id' => $cCamille,
    'event_id' => $evMariage,
    'quote_id' => $qMariage,
    'title' => 'Contrat de prestation — Mariage Camille & Antoine',
    'content' => "Le présent contrat engage l'Agence Événements Démo à organiser le mariage de Camille et Antoine le "
        . date('d/m/Y', strtotime((clone $today)->modify('+45 days')->format('Y-m-d'))) . ", conformément au devis accepté.\n\nCeci est un contrat de démonstration.",
    'status' => 'signed',
    'sent_at' => date('Y-m-d H:i:s', strtotime('-9 days')),
    'signed_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
    'signer_name' => 'Camille Girard',
    'signer_ip' => '203.0.113.42',
]);

// --- 12. Guests + tables + RSVP pour le mariage ---
$tableIds = [];
foreach (['Table Famille', 'Table Amis', 'Table Collègues'] as $tName) {
    $tableIds[] = EventTable::create(['event_id' => $evMariage, 'name' => $tName, 'capacity' => 8]);
}
$guestSeed = [
    ['first_name' => 'Antoine', 'last_name' => 'Moreau', 'rsvp_status' => 'confirmed', 'plus_ones' => 1],
    ['first_name' => 'Émilie', 'last_name' => 'Girard', 'rsvp_status' => 'confirmed', 'plus_ones' => 0],
    ['first_name' => 'Paul', 'last_name' => 'Girard', 'rsvp_status' => 'confirmed', 'plus_ones' => 1],
    ['first_name' => 'Chloé', 'last_name' => 'Dubois', 'rsvp_status' => 'pending', 'plus_ones' => 0],
    ['first_name' => 'Hugo', 'last_name' => 'Lambert', 'rsvp_status' => 'declined', 'plus_ones' => 0],
    ['first_name' => 'Sarah', 'last_name' => 'Petit', 'rsvp_status' => 'confirmed', 'plus_ones' => 1],
];
foreach ($guestSeed as $i => $g) {
    Guest::create(array_merge($g, [
        'event_id' => $evMariage,
        'table_id' => $tableIds[$i % count($tableIds)],
        'email' => strtolower($g['first_name'] . '.' . $g['last_name']) . '@example-demo.fr',
    ]));
}

// --- 13. Billetterie + check-in pour le festival ---
$catId = TicketCategory::create(['event_id' => $evFestival, 'name' => 'Standard', 'price' => 35, 'quantity_available' => 300]);
for ($i = 1; $i <= 6; $i++) {
    $ticketId = Ticket::create([
        'ticket_category_id' => $catId,
        'code' => Ticket::generateCode(),
        'holder_name' => 'Visiteur Démo ' . $i,
        'status' => $i <= 3 ? 'checked_in' : 'valid',
    ]);
    if ($i <= 3) {
        $pdo->prepare('UPDATE tickets SET checked_in_at = NOW() WHERE id = ?')->execute([$ticketId]);
    }
}

// --- 14. Caisse (POS) : une session clôturée avec quelques ventes, pour la soirée de lancement ---
$stmt = $pdo->prepare('INSERT INTO pos_sessions (organization_id, event_id, status, opening_float, counted_cash, cash_difference, opened_by, closed_by, opened_at, closed_at) VALUES (?, ?, "closed", 100, 348, 0, ?, ?, ?, ?)');
$stmt->execute([$orgId, $evLancement, $userId, $userId, date('Y-m-d H:i:s', strtotime('-15 days')), date('Y-m-d H:i:s', strtotime('-15 days +4 hours'))]);
$posSessionId = (int) $pdo->lastInsertId();

foreach ([
    ['product_id' => $productIds[0], 'description' => 'Coupe de champagne', 'quantity' => 12, 'unit_price' => 8, 'method' => 'cash'],
    ['product_id' => $productIds[2], 'description' => 'Part de gâteau', 'quantity' => 8, 'unit_price' => 6, 'method' => 'card'],
] as $i => $sale) {
    $total = $sale['quantity'] * $sale['unit_price'];
    $stmt = $pdo->prepare(
        'INSERT INTO pos_sales (organization_id, pos_session_id, sale_number, payment_method, subtotal, total, status, access_token, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, "completed", ?, ?, ?)'
    );
    $stmt->execute([$orgId, $posSessionId, 'DEMO-V-' . ($i + 1), $sale['method'], $total, $total, bin2hex(random_bytes(32)), $userId, date('Y-m-d H:i:s', strtotime('-15 days +' . $i . ' hours'))]);
    $saleId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO pos_sale_items (organization_id, pos_sale_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$orgId, $saleId, $sale['product_id'], $sale['description'], $sale['quantity'], $sale['unit_price'], $total]);
}

// --- 15. Tasks ---
foreach ([
    ['event_id' => $evMariage, 'title' => 'Confirmer le menu final avec le traiteur', 'status' => 'todo', 'priority' => 'high'],
    ['event_id' => $evMariage, 'title' => 'Envoyer le plan de table définitif', 'status' => 'in_progress', 'priority' => 'normal'],
    ['event_id' => $evSeminaire, 'title' => 'Valider le programme de la journée', 'status' => 'done', 'priority' => 'normal'],
    ['event_id' => $evFestival, 'title' => 'Briefing sécurité avec le prestataire', 'status' => 'todo', 'priority' => 'high'],
] as $t) {
    $pdo->prepare('INSERT INTO tasks (organization_id, event_id, title, status, priority, assigned_to) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$orgId, $t['event_id'], $t['title'], $t['status'], $t['priority'], $userId]);
}

// --- 16. Prise de rendez-vous en ligne : activée avec des horaires types ---
BookingSettings::save([
    'is_enabled' => 1,
    'public_slug' => BookingSettings::ensureSlug(),
    'slot_duration_minutes' => 30,
    'buffer_minutes' => 0,
    'min_notice_hours' => 24,
    'max_advance_days' => 60,
    'weekly_hours' => [
        '1' => ['start' => '09:00', 'end' => '18:00'],
        '2' => ['start' => '09:00', 'end' => '18:00'],
        '3' => ['start' => '09:00', 'end' => '18:00'],
        '4' => ['start' => '09:00', 'end' => '18:00'],
        '5' => ['start' => '09:00', 'end' => '17:00'],
        '6' => null,
        '7' => null,
    ],
    'location_type' => 'Visioconférence',
    'meeting_instructions' => 'Ceci est un créneau de démonstration — aucun rendez-vous réel ne sera honoré.',
]);

// --- 17. Satisfaction (avis client) ---
$pdo->prepare('INSERT INTO satisfaction_surveys (organization_id, event_id, client_id, token, rating, comments, sent_at, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$orgId, $evAnnivLaura, $cLaura, bin2hex(random_bytes(16)), 5, 'Une organisation impeccable, merci à toute l\'équipe !', date('Y-m-d H:i:s', strtotime('-38 days')), date('Y-m-d H:i:s', strtotime('-37 days'))]);

ActivityLog::record('Données de démonstration régénérées', 'organization', $orgId);

unset($_SESSION['organization_id'], $_SESSION['user_id']);

echo "Organisation démo recréée (id={$orgId}). Identifiants : " . Demo::EMAIL . ' / ' . Demo::PASSWORD . "\n";
