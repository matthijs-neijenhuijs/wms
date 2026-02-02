<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VatRate;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create philandphae subdomain
        $philandphae = Subdomain::firstOrCreate(
            ['subdomain' => 'philandphae'],
            ['name' => 'Phil & Phae']
        );

        // Create user for philandphae
        $userPhilandphae = User::firstOrCreate(
            ['email' => 'matthijs@philandphae.com'],
            [
                'subdomain_id' => $philandphae->id,
                'name' => 'Matthijs',
                'password' => Hash::make('cool'),
                'email_verified_at' => now(),
            ]
        );

        // Create warehouse for philandphae
        $warehousePhilandphae1 = Warehouse::firstOrCreate(
            ['name' => 'philandphae1', 'subdomain_id' => $philandphae->id]
        );

        // Attach user to warehouse
        $userPhilandphae->warehouses()->syncWithoutDetaching([$warehousePhilandphae1->id]);

        $this->seedVatRates($warehousePhilandphae1);
        $this->seedWarehouseDefaults($warehousePhilandphae1);

        // Create phaewomen subdomain
        $phaewomen = Subdomain::firstOrCreate(
            ['subdomain' => 'phaewomen'],
            ['name' => 'Phae Women']
        );

        // Create user for phaewomen
        $userPhaewomen = User::firstOrCreate(
            ['email' => 'matthijs@phaewomen.com'],
            [
                'subdomain_id' => $phaewomen->id,
                'name' => 'Matthijs',
                'password' => Hash::make('cool'),
                'email_verified_at' => now(),
            ]
        );

        // Create warehouses for phaewomen
        $warehousePhae1 = Warehouse::firstOrCreate(
            ['name' => 'phae1', 'subdomain_id' => $phaewomen->id]
        );

        $warehousePhae2 = Warehouse::firstOrCreate(
            ['name' => 'phae2', 'subdomain_id' => $phaewomen->id]
        );

        // Attach user to warehouses
        $userPhaewomen->warehouses()->syncWithoutDetaching([$warehousePhae1->id, $warehousePhae2->id]);

        $this->seedVatRates($warehousePhae1);
        $this->seedVatRates($warehousePhae2);
        $this->seedWarehouseDefaults($warehousePhae1);
        $this->seedWarehouseDefaults($warehousePhae2);

        $this->command->info('Successfully seeded subdomains, users, and warehouses.');
        $this->command->info('');
        $this->command->info('Phil & Phae:');
        $this->command->info('  URL: http://philandphae.wms.test');
        $this->command->info('  Email: matthijs@philandphae.com');
        $this->command->info('  Password: cool');
        $this->command->info('  Warehouse: philandphae1');
        $this->command->info('');
        $this->command->info('Phae Women:');
        $this->command->info('  URL: http://phaewomen.wms.test');
        $this->command->info('  Email: matthijs@phaewomen.com');
        $this->command->info('  Password: cool');
        $this->command->info('  Warehouses: phae1, phae2');
    }

    private function seedVatRates(Warehouse $warehouse): void
    {
        $rates = [
            ['name' => '6%', 'rate' => 6.0000],
            ['name' => '9%', 'rate' => 9.0000],
            ['name' => '21%', 'rate' => 21.0000],
        ];

        foreach ($rates as $rate) {
            VatRate::firstOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'name' => $rate['name'],
                    'rate' => $rate['rate'],
                ]
            );
        }
    }

    private function seedWarehouseDefaults(Warehouse $warehouse): void
    {
        $brand = Brand::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'reference_code' => 'BRD-001',
            ],
            [
                'active' => true,
                'name' => 'WMS Essentials',
                'description' => 'Default seeded brand.',
            ]
        );

        $colorGroup = AttributeGroup::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'name' => 'Color',
            ]
        );

        $sizeGroup = AttributeGroup::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'name' => 'Size',
            ]
        );

        $red = Attribute::query()->firstOrCreate(
            [
                'attribute_group_id' => $colorGroup->id,
                'name' => 'Red',
            ]
        );

        $blue = Attribute::query()->firstOrCreate(
            [
                'attribute_group_id' => $colorGroup->id,
                'name' => 'Blue',
            ]
        );

        $small = Attribute::query()->firstOrCreate(
            [
                'attribute_group_id' => $sizeGroup->id,
                'name' => 'S',
            ]
        );

        $medium = Attribute::query()->firstOrCreate(
            [
                'attribute_group_id' => $sizeGroup->id,
                'name' => 'M',
            ]
        );

        $vatRate = VatRate::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('name', '21%')
            ->first();

        $product = Product::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_code', 'SKU-001')
            ->first();

        if (! $product) {
            $product = Product::forceCreate([
                'warehouse_id' => $warehouse->id,
                'active' => true,
                'reference_code' => 'REF-001',
                'price' => 19.9900,
                'product_code' => 'SKU-001',
                'stock_unlimited' => true,
                'barcode' => '1234567890123',
                'name' => 'Basic T-Shirt',
                'weight' => '200g',
                'height' => '2cm',
                'length' => '30cm',
                'hs_code' => '61091000',
                'country_of_origin' => 'NL',
                'description' => 'Seeded product for demo purposes.',
                'vat_rate_id' => $vatRate?->id,
                'brand_id' => $brand->id,
            ]);
        }

        $product->attributes()->syncWithoutDetaching([
            $red->id,
            $blue->id,
            $small->id,
            $medium->id,
        ]);
    }
}
