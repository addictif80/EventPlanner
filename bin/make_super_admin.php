<?php

/**
 * CLI helper to promote an existing user to platform super administrator.
 * Usage: php bin/make_super_admin.php jane@example.com
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

[$script, $email] = array_pad($argv, 2, null);

if (!$email) {
    die("Usage: php bin/make_super_admin.php email@example.com\n");
}

$pdo = Database::connection();

$stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    die("Aucun utilisateur trouvé avec l'email {$email}.\n");
}

$pdo->prepare('UPDATE users SET is_super_admin = 1 WHERE id = ?')->execute([$user['id']]);

echo "{$user['name']} ({$email}) est maintenant super administrateur de la plateforme.\n";
