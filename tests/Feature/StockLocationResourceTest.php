<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Products\Filament\Resources\StockLocations\Pages\CreateStockLocation;
use Modules\Products\Filament\Resources\StockLocations\Pages\EditStockLocation;
use Modules\Products\Filament\Resources\StockLocations\Pages\ListStockLocations;
use Modules\Products\Models\StockLocation;
use Modules\Users\Models\User;

/**
 * @return array{0: User, 1: Warehouse}
 */
function createStockLocationResourceContext(string $suffix): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => "stock-location-{$suffix}",
        'name' => "Stock Location {$suffix}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Stock Location Warehouse {$suffix}",
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Stock Location Manager {$suffix}",
        'email' => "stock-location-manager-{$suffix}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    return [$user, $warehouse];
}

it('allows any authenticated user to view the list, create and edit pages', function () {
    [, $warehouse] = createStockLocationResourceContext('access');

    $location = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'A-1',
        'rank' => 1,
    ]);

    Livewire::test(ListStockLocations::class)->assertOk();
    Livewire::test(CreateStockLocation::class)->assertOk();
    Livewire::test(EditStockLocation::class, ['record' => $location->getKey()])->assertOk();
});

it('validates the create form data', function (array $data, array $errors) {
    createStockLocationResourceContext('validation');

    Livewire::test(CreateStockLocation::class)
        ->fillForm([
            'name' => 'Valid Name',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`name` is max 255 characters' => [['name' => str_repeat('a', 256)], ['name' => 'max']],
]);

it('rejects a location being nested under itself', function () {
    [, $warehouse] = createStockLocationResourceContext('self-parent');

    $location = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'A-1',
        'rank' => 1,
    ]);

    Livewire::test(EditStockLocation::class, ['record' => $location->getKey()])
        ->fillForm([
            'name' => 'A-1',
            'parent_id' => $location->id,
        ])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);
});

it('rejects a location being nested under one of its own descendants', function () {
    [, $warehouse] = createStockLocationResourceContext('descendant-parent');

    $a = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'A', 'rank' => 1]);
    $b = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'B', 'rank' => 2, 'parent_id' => $a->id]);
    $c = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'C', 'rank' => 3, 'parent_id' => $b->id]);

    Livewire::test(EditStockLocation::class, ['record' => $a->getKey()])
        ->fillForm([
            'name' => 'A',
            'parent_id' => $c->id,
        ])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);
});

it('accepts nesting a location under an unrelated location', function () {
    [, $warehouse] = createStockLocationResourceContext('unrelated-parent');

    $a = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'A', 'rank' => 1]);
    $b = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'B', 'rank' => 2]);

    Livewire::test(EditStockLocation::class, ['record' => $b->getKey()])
        ->fillForm([
            'name' => 'B',
            'parent_id' => $a->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($b->fresh()->parent_id)->toBe($a->id);
});

it('auto-assigns the next rank per warehouse on create, independently per warehouse', function () {
    $subdomainA = Subdomain::query()->create(['subdomain' => 'rank-wh-a', 'name' => 'Rank WH A']);
    app()->instance('current_subdomain', $subdomainA);
    $warehouseA = Warehouse::query()->create(['subdomain_id' => $subdomainA->id, 'name' => 'Rank Warehouse A', 'currency' => 'EUR']);

    $first = StockLocation::query()->create(['warehouse_id' => $warehouseA->id, 'name' => 'First']);
    $second = StockLocation::query()->create(['warehouse_id' => $warehouseA->id, 'name' => 'Second']);
    $third = StockLocation::query()->create(['warehouse_id' => $warehouseA->id, 'name' => 'Third']);

    expect($first->rank)->toBe(1)
        ->and($second->rank)->toBe(2)
        ->and($third->rank)->toBe(3);

    $subdomainB = Subdomain::query()->create(['subdomain' => 'rank-wh-b', 'name' => 'Rank WH B']);
    app()->instance('current_subdomain', $subdomainB);
    $warehouseB = Warehouse::query()->create(['subdomain_id' => $subdomainB->id, 'name' => 'Rank Warehouse B', 'currency' => 'EUR']);

    $otherWarehouseFirst = StockLocation::query()->create(['warehouse_id' => $warehouseB->id, 'name' => 'Other First']);

    expect($otherWarehouseFirst->rank)->toBe(1);
});

it('configures the table as reorderable by rank', function () {
    createStockLocationResourceContext('reorderable');

    $table = Livewire::test(ListStockLocations::class)->instance()->getTable();

    expect($table->isReorderable())->toBeTrue();
});

it('scopes stock locations to the active tenant warehouse', function () {
    [, $warehouseA] = createStockLocationResourceContext('tenant-a');

    $locationA = StockLocation::query()->create(['warehouse_id' => $warehouseA->id, 'name' => 'A-1', 'rank' => 1]);

    [, $warehouseB] = createStockLocationResourceContext('tenant-b');

    $locationB = StockLocation::query()->create(['warehouse_id' => $warehouseB->id, 'name' => 'B-1', 'rank' => 1]);

    // Currently acting under warehouse B's tenant context. Every table in
    // this app defers loading (App\Providers\FilamentServiceProvider), so
    // ->loadTable() is required before rows are queried/rendered.
    Livewire::test(ListStockLocations::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$locationB])
        ->assertCanNotSeeTableRecords([$locationA]);

    expect(StockLocation::query()->find($locationA->id))->toBeNull()
        ->and(StockLocation::query()->find($locationB->id))->not->toBeNull();
});
