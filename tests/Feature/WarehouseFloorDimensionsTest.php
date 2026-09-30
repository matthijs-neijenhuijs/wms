<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Settings\Filament\Resources\Warehouses\Pages\EditWarehouse;
use Modules\Users\Models\User;

/**
 * @return array{0: User, 1: Warehouse}
 */
function createWarehouseFloorDimensionsContext(string $suffix): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => "warehouse-floor-{$suffix}",
        'name' => "Warehouse Floor {$suffix}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Warehouse Floor Warehouse {$suffix}",
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Warehouse Floor Manager {$suffix}",
        'email' => "warehouse-floor-manager-{$suffix}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    return [$user, $warehouse];
}

it('validates the floor dimension fields', function (array $data, array $errors) {
    [, $warehouse] = createWarehouseFloorDimensionsContext('validation');

    Livewire::test(EditWarehouse::class, ['record' => $warehouse->getKey()])
        ->fillForm([
            'name' => $warehouse->name,
            'currency' => 'EUR',
            ...$data,
        ])
        ->call('save')
        ->assertHasFormErrors($errors);
})->with([
    '`floor_width` must be numeric' => [['floor_width' => 'not-a-number'], ['floor_width' => 'numeric']],
    '`floor_width` must be at least 0.1' => [['floor_width' => 0.0], ['floor_width' => 'min']],
    '`floor_height` must be numeric' => [['floor_height' => 'not-a-number'], ['floor_height' => 'numeric']],
    '`floor_height` must be at least 0.1' => [['floor_height' => 0.0], ['floor_height' => 'min']],
]);

it('allows floor_width and floor_height to be left blank', function () {
    [, $warehouse] = createWarehouseFloorDimensionsContext('blank');

    Livewire::test(EditWarehouse::class, ['record' => $warehouse->getKey()])
        ->fillForm([
            'name' => $warehouse->name,
            'currency' => 'EUR',
            'floor_width' => null,
            'floor_height' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($warehouse->fresh()->floor_width)->toBeNull()
        ->and($warehouse->fresh()->floor_height)->toBeNull();
});

it('persists decimal floor dimensions unchanged through the cast', function () {
    [, $warehouse] = createWarehouseFloorDimensionsContext('decimal');

    Livewire::test(EditWarehouse::class, ['record' => $warehouse->getKey()])
        ->fillForm([
            'name' => $warehouse->name,
            'currency' => 'EUR',
            'floor_width' => 12.5,
            'floor_height' => 8.25,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $warehouse->refresh();

    expect((float) $warehouse->floor_width)->toBe(12.5)
        ->and((float) $warehouse->floor_height)->toBe(8.25);
});
