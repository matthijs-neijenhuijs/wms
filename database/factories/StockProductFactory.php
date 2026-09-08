<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Brands\Models\Brand;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Settings\Models\VatRate;

/**
 * @extends Factory<StockProduct>
 */
class StockProductFactory extends Factory
{
    public function definition(): array
    {
        $subdomain = Subdomain::query()->firstOrCreate(
            ['subdomain' => Str::slug($this->faker->unique()->company)],
            ['name' => $this->faker->company]
        );

        $warehouse = Warehouse::query()->firstOrCreate(
            [
                'subdomain_id' => $subdomain->id,
                'name' => $this->faker->unique()->company,
            ]
        );

        $vatRate = VatRate::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'name' => '21%',
            ],
            [
                'rate' => 21.0000,
            ]
        );

        $brand = Brand::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'reference_code' => 'BRD-'.$this->faker->unique()->numerify('###'),
            ],
            [
                'active' => true,
                'name' => $this->faker->company,
                'description' => $this->faker->sentence,
            ]
        );

        $product = Product::query()
            ->where('warehouse_id', $warehouse->id)
            ->inRandomOrder()
            ->first();

        if (! $product) {
            $product = Product::query()->create([
                'warehouse_id' => $warehouse->id,
                'active' => true,
                'reference_code' => 'REF-'.$this->faker->unique()->numerify('####'),
                'price' => $this->faker->randomFloat(2, 1, 200),
                'product_code' => 'SKU-'.$this->faker->unique()->numerify('####'),
                'stock_unlimited' => false,
                'barcode' => $this->faker->ean13(),
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->sentence,
                'vat_rate_id' => $vatRate->id,
                'brand_id' => $brand->id,
            ]);
        }

        $onStock = $this->faker->numberBetween(0, 500);
        $reserved = $this->faker->numberBetween(0, $onStock);
        $reservedOnPicklists = $this->faker->numberBetween(0, max(0, $onStock - $reserved));

        return [
            'product_id' => $product->id,
            'on_stock_quantity' => $onStock,
            'reserved_quantity' => $reserved,
            'reserved_on_picklists' => $reservedOnPicklists,
            'free_on_stock_quantity' => max(0, $onStock - $reserved - $reservedOnPicklists),
        ];
    }
}
