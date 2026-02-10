<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockProduct;
use Illuminate\Database\Seeder;

class StockProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::query()->each(function (Product $product) {
            $onStock = fake()->numberBetween(0, 500);
            $reserved = fake()->numberBetween(0, $onStock);
            $reservedOnPicklists = fake()->numberBetween(0, max(0, $onStock - $reserved));

            StockProduct::query()->firstOrCreate(
                ['product_id' => $product->id],
                [
                    'on_stock_quantity' => $onStock,
                    'reserved_quantity' => $reserved,
                    'reserved_on_picklists' => $reservedOnPicklists,
                    'free_on_stock_quantity' => max(0, $onStock - $reserved - $reservedOnPicklists),
                ]
            );
        });
    }
}
