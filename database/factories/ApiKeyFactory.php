<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    public function definition(): array
    {
        $subdomain = Subdomain::query()->firstOrCreate(
            ['subdomain' => $this->faker->unique()->slug(2)],
            ['name' => $this->faker->company()]
        );

        $warehouse = Warehouse::query()->firstOrCreate(
            [
                'subdomain_id' => $subdomain->id,
                'name' => $this->faker->unique()->company(),
            ]
        );

        return [
            'warehouse_id' => $warehouse->id,
            'name' => $this->faker->words(2, true),
            'key_hash' => hash('sha256', Str::random(64)),
            'is_active' => true,
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}
