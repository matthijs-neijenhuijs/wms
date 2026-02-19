<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Warehouse::query()->each(function (Warehouse $warehouse): void {
            ApiKey::query()->firstOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'name' => 'Default API Key',
                ],
                [
                    'key_hash' => hash('sha256', Str::random(64)),
                    'is_active' => true,
                ]
            );
        });
    }
}
