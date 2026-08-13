<?php

/**
 * CLI helper to create the first administrator account.
 * Usage: php bin/create_admin.php "Jane Doe" jane@example.com SomeStrongPassword
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

[$script, $name, $email, $password] = array_pad($argv, 4, null);

if (!$name || !$email || !$password) {
    die("Usage: php bin/create_admin.php \"Nom complet\" email@example.com MotDePasse\n");
}

if (strlen($password) < 8) {
    die("Le mot de passe doit contenir au moins 8 caractères.\n");
}

$pdo = Database::connection();

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    die("Un utilisateur avec cet email existe déjà.\n");
}

$stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, "admin", 1)');
$stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

echo "Administrateur créé avec succès (id " . $pdo->lastInsertId() . ").\n";
