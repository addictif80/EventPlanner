<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

class EmailTemplate
{
    public static function get(string $key): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM email_templates WHERE template_key = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$key, Auth::organizationId()]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM email_templates WHERE organization_id = ? ORDER BY template_key');
        $stmt->execute([Auth::organizationId()]);
        return $stmt->fetchAll();
    }

    public static function update(string $key, string $subject, string $intro): void
    {
        $stmt = Database::connection()->prepare('UPDATE email_templates SET subject = ?, intro = ? WHERE template_key = ? AND organization_id = ?');
        $stmt->execute([$subject, $intro, $key, Auth::organizationId()]);
    }

    public static function createDefaults(int $organizationId): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO email_templates (organization_id, template_key, subject, intro) VALUES (?, ?, ?, ?)');
        $stmt->execute([$organizationId, 'quote', 'Votre devis {number}', 'Veuillez trouver ci-dessous le récapitulatif de votre devis.']);
        $stmt->execute([$organizationId, 'invoice', 'Votre facture {number}', 'Veuillez trouver ci-dessous votre facture.']);
        $stmt->execute([$organizationId, 'reminder', 'Rappel — facture {number} en attente de paiement', 'Nous vous rappelons que la facture suivante reste impayée à ce jour.']);
    }
}
