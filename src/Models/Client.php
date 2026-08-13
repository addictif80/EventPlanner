<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Client extends Model
{
    protected static string $table = 'clients';

    public static function search(string $term): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM clients WHERE organization_id = ? AND (first_name LIKE ? OR last_name LIKE ? OR company_name LIKE ? OR email LIKE ?) ORDER BY created_at DESC'
        );
        $like = '%' . $term . '%';
        $stmt->execute([Auth::organizationId(), $like, $like, $like, $like]);
        return $stmt->fetchAll();
    }

    public static function displayName(array $client): string
    {
        if ($client['type'] === 'entreprise' && !empty($client['company_name'])) {
            return $client['company_name'];
        }
        return trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));
    }
}
