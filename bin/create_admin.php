<?php

/**
 * CLI helper to create a new organization with its first administrator account.
 * Usage: php bin/create_admin.php "Mon Agence" "Jane Doe" jane@example.com SomeStrongPassword
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Database;
use App\Models\CompanySettings;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

[$script, $orgName, $name, $email, $password] = array_pad($argv, 5, null);

if (!$orgName || !$name || !$email || !$password) {
    die("Usage: php bin/create_admin.php \"Nom de l'agence\" \"Nom complet\" email@example.com MotDePasse\n");
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

$pdo->beginTransaction();

try {
    $organizationId = Organization::create(['name' => $orgName]);

    $stmt = $pdo->prepare('INSERT INTO users (organization_id, name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, "admin", 1)');
    $stmt->execute([$organizationId, $name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();

    CompanySettings::createDefaults($organizationId);
    $pdo->prepare('INSERT INTO smtp_settings (organization_id) VALUES (?)')->execute([$organizationId]);

    $pdo->prepare(
        'INSERT INTO event_types (organization_id, name) VALUES (?, ?), (?, ?), (?, ?), (?, ?), (?, ?), (?, ?), (?, ?), (?, ?)'
    )->execute([
        $organizationId, 'Mariage', $organizationId, 'Anniversaire', $organizationId, 'Séminaire / Corporate',
        $organizationId, 'Conférence', $organizationId, 'Baptême', $organizationId, 'Soirée privée',
        $organizationId, 'Festival / Concert', $organizationId, 'Autre',
    ]);

    EmailTemplate::createDefaults($organizationId);

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    die('Échec de la création : ' . $e->getMessage() . "\n");
}

echo "Organisation « {$orgName} » créée (id {$organizationId}) avec l'administrateur {$email} (id {$userId}).\n";
