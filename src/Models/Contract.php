<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Contract extends Model
{
    protected static string $table = 'contracts';

    public static function allWithClient(): array
    {
        $sql = 'SELECT c.*, cl.first_name, cl.last_name, cl.company_name
                FROM contracts c LEFT JOIN clients cl ON cl.id = c.client_id
                ORDER BY c.created_at DESC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function findWithRelations(int $id): ?array
    {
        $sql = 'SELECT c.*, cl.first_name, cl.last_name, cl.company_name, cl.email AS client_email, e.title AS event_title
                FROM contracts c
                LEFT JOIN clients cl ON cl.id = c.client_id
                LEFT JOIN events e ON e.id = c.event_id
                WHERE c.id = ? LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cl.first_name, cl.last_name, cl.company_name FROM contracts c LEFT JOIN clients cl ON cl.id = c.client_id WHERE c.sign_token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }
}
