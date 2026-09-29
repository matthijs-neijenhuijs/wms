<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StockLocationAllocator
{
    /**
     * Resolve which stock location each of $quantity units of $productId
     * should be picked from: ascending by the location's rank, spilling
     * over to the next-lowest-rank location once one is exhausted. A unit
     * that no location has recorded quantity for gets null (unlocated).
     *
     * @return array<int, int|null> exactly $quantity entries, in pick order
     */
    public function allocate(int $productId, int $quantity): array
    {
        if ($quantity <= 0) {
            return [];
        }

        $locations = DB::table('stock_location_product')
            ->join('stock_locations', 'stock_locations.id', '=', 'stock_location_product.stock_location_id')
            ->where('stock_location_product.product_id', $productId)
            ->where('stock_location_product.quantity', '>', 0)
            ->orderBy('stock_locations.rank')
            ->get(['stock_location_product.stock_location_id', 'stock_location_product.quantity']);

        $assignments = [];

        foreach ($locations as $location) {
            $remaining = (int) $location->quantity;

            while ($remaining > 0 && count($assignments) < $quantity) {
                $assignments[] = (int) $location->stock_location_id;
                $remaining--;
            }

            if (count($assignments) >= $quantity) {
                break;
            }
        }

        while (count($assignments) < $quantity) {
            $assignments[] = null;
        }

        return $assignments;
    }
}
