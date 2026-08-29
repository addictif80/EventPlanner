<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Product extends Model
{
    protected static string $table = 'products';

    /**
     * Atomically decrements stock for a product that tracks it, refusing if
     * that would take it below zero (an event-day sale must never oversell a
     * limited catalogue item). No-op (always succeeds) for a product whose
     * stock_quantity is NULL — those are untracked services/prestations.
     */
    public static function decrementStock(int $productId, float $quantity): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE products SET stock_quantity = stock_quantity - ?
             WHERE id = ? AND organization_id = ? AND stock_quantity IS NOT NULL AND stock_quantity >= ?'
        );
        $stmt->execute([$quantity, $productId, Auth::organizationId(), $quantity]);
        if ($stmt->rowCount() > 0) {
            return true;
        }

        $product = self::find($productId);
        return $product === null || $product['stock_quantity'] === null;
    }
}
