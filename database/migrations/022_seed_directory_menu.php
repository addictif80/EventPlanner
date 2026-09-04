<?php
// Data seed (see Migrator docblock): adds a footer link to the new public
// annuaire (App\Controllers\DirectoryController) if not already present.
// Idempotent: only inserts when no footer link to /annuaire exists yet.

$existsStmt = $pdo->prepare("SELECT id FROM site_menu_items WHERE location = 'footer' AND url = ? LIMIT 1");
$existsStmt->execute(['/annuaire']);

if (!$existsStmt->fetch()) {
    $pdo->prepare(
        "INSERT INTO site_menu_items (location, label, url, sort_order, is_active) VALUES ('footer', 'Trouver un organisateur', '/annuaire', 5, 1)"
    )->execute();
}
