<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Brand;
use App\Models\Client;
use App\Models\ClientAddresses;
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
        $this->seedClients($warehousePhilandphae1);

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
        $this->seedClients($warehousePhae1);
        $this->seedClients($warehousePhae2);

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

    private function seedClients(Warehouse $warehouse): void
    {
        // Client 1: John Doe
        $client1 = Client::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'email' => 'john.doe@example.com',
            ],
            [
                'active' => true,
                'vat_number' => 'NL123456789B01',
                'coc_number' => '12345678',
                'debtor_number' => 'DEB-001',
                'iban_number' => 'NL91ABNA0417164300',
                'company' => 'Doe Enterprises',
                'comments' => 'VIP customer',
            ]
        );

        $deliveryAddress1 = ClientAddresses::query()->firstOrCreate(
            [
                'client_id' => $client1->id,
                'email' => 'john.doe@example.com',
            ],
            [
                'company' => 'Doe Enterprises',
                'gender' => 'male',
                'initials' => 'J.',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'street' => 'Main Street',
                'housenumber' => 123,
                'housenumber_suffix' => 'A',
                'zipcode' => '1234AB',
                'city' => 'Amsterdam',
                'country' => 'Netherlands',
                'phone' => '+31201234567',
                'mobile' => '+31612345678',
            ]
        );

        $billAddress1 = ClientAddresses::query()->firstOrCreate(
            [
                'client_id' => $client1->id,
                'email' => 'billing@doe-enterprises.com',
            ],
            [
                'company' => 'Doe Enterprises',
                'gender' => 'male',
                'initials' => 'J.',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'street' => 'Business Park',
                'housenumber' => 456,
                'zipcode' => '5678CD',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'phone' => '+31102345678',
                'mobile' => '+31612345678',
            ]
        );

        $client1->update([
            'delivery_client_address_id' => $deliveryAddress1->id,
            'bill_client_address_id' => $billAddress1->id,
        ]);

        // Client 2: Jane Smith
        $client2 = Client::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'email' => 'jane.smith@example.com',
            ],
            [
                'active' => true,
                'vat_number' => 'NL987654321B01',
                'coc_number' => '87654321',
                'debtor_number' => 'DEB-002',
                'iban_number' => 'NL20INGB0001234567',
                'company' => 'Smith & Co',
                'comments' => 'Regular customer',
            ]
        );

        $deliveryAddress2 = ClientAddresses::query()->firstOrCreate(
            [
                'client_id' => $client2->id,
                'email' => 'jane.smith@example.com',
            ],
            [
                'company' => 'Smith & Co',
                'gender' => 'female',
                'initials' => 'J.',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
                'street' => 'High Street',
                'housenumber' => 789,
                'zipcode' => '9012EF',
                'city' => 'Utrecht',
                'country' => 'Netherlands',
                'phone' => '+31301234567',
                'mobile' => '+31687654321',
            ]
        );

        $billAddress2 = ClientAddresses::query()->firstOrCreate(
            [
                'client_id' => $client2->id,
                'email' => 'accounting@smith-co.com',
            ],
            [
                'company' => 'Smith & Co',
                'gender' => 'female',
                'initials' => 'J.',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
                'street' => 'Commerce Road',
                'housenumber' => 321,
                'zipcode' => '3456GH',
                'city' => 'The Hague',
                'country' => 'Netherlands',
                'phone' => '+31703456789',
                'mobile' => '+31687654321',
            ]
        );

        $client2->update([
            'delivery_client_address_id' => $deliveryAddress2->id,
            'bill_client_address_id' => $billAddress2->id,
        ]);

        // Client 3: Bob Johnson (Private individual)
        $client3 = Client::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'email' => 'bob.johnson@personal.com',
            ],
            [
                'active' => true,
                'debtor_number' => 'DEB-003',
                'comments' => 'Private customer - no VAT',
            ]
        );

        $address3 = ClientAddresses::query()->firstOrCreate(
            [
                'client_id' => $client3->id,
                'email' => 'bob.johnson@personal.com',
            ],
            [
                'gender' => 'male',
                'initials' => 'B.',
                'firstname' => 'Bob',
                'lastname' => 'Johnson',
                'street' => 'Elm Avenue',
                'housenumber' => 42,
                'zipcode' => '6789IJ',
                'city' => 'Eindhoven',
                'country' => 'Netherlands',
                'mobile' => '+31623456789',
            ]
        );

        $client3->update([
            'delivery_client_address_id' => $address3->id,
            'bill_client_address_id' => $address3->id,
        ]);
    }
}
