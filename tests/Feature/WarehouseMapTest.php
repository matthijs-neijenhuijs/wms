<?php

declare(strict_types=1);

use App\Filament\Pages\WarehouseMap;
use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Modules\Products\Models\StockLocation;
use Modules\Users\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * @return array{0: User, 1: Warehouse}
 */
function createWarehouseMapContext(string $suffix, ?float $floorWidth = 20.0, ?float $floorHeight = 10.0): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => "warehouse-map-{$suffix}",
        'name' => "Warehouse Map {$suffix}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Warehouse Map Warehouse {$suffix}",
        'currency' => 'EUR',
        'floor_width' => $floorWidth,
        'floor_height' => $floorHeight,
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Warehouse Map Manager {$suffix}",
        'email' => "warehouse-map-manager-{$suffix}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    return [$user, $warehouse];
}

it('allows any authenticated user to load the map page', function () {
    createWarehouseMapContext('access');

    Livewire::test(WarehouseMap::class)->assertOk();
});

it('persists x, y, width and height for a location in the current tenant', function () {
    [, $warehouse] = createWarehouseMapContext('persist');

    $location = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'A-1',
        'rank' => 1,
    ]);

    Livewire::test(WarehouseMap::class)
        ->call('saveLocationLayout', $location->id, 2.5, 3.5, 1.5, 1.0);

    $location->refresh();

    expect((float) $location->x)->toBe(2.5)
        ->and((float) $location->y)->toBe(3.5)
        ->and((float) $location->width)->toBe(1.5)
        ->and((float) $location->height)->toBe(1.0);
});

it('clamps width and height up to a minimum of 0.1', function () {
    [, $warehouse] = createWarehouseMapContext('min-size');

    $location = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'A-1',
        'rank' => 1,
    ]);

    Livewire::test(WarehouseMap::class)
        ->call('saveLocationLayout', $location->id, 0.0, 0.0, 0.0, -5.0);

    $location->refresh();

    expect((float) $location->width)->toBe(0.1)
        ->and((float) $location->height)->toBe(0.1);
});

it('clamps a square exactly to the floor edge without letting it overflow', function () {
    [, $warehouse] = createWarehouseMapContext('boundary', floorWidth: 10.0, floorHeight: 10.0);

    $exactFit = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Exact fit',
        'rank' => 1,
    ]);

    $overflowing = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Overflowing',
        'rank' => 2,
    ]);

    $livewire = Livewire::test(WarehouseMap::class);

    // Requesting a position that exactly fits the floor should be left alone.
    $livewire->call('saveLocationLayout', $exactFit->id, 8.0, 8.0, 2.0, 2.0);
    $exactFit->refresh();

    expect((float) $exactFit->x)->toBe(8.0)
        ->and((float) $exactFit->y)->toBe(8.0);

    // Requesting a position that would overflow past the floor's edge must
    // be pulled back to fit exactly, not merely reduced.
    $livewire->call('saveLocationLayout', $overflowing->id, 9.0, 9.0, 2.0, 2.0);
    $overflowing->refresh();

    expect((float) $overflowing->x)->toBe(8.0)
        ->and((float) $overflowing->y)->toBe(8.0);
});

it('does not clamp x/y against floor bounds when the warehouse has no floor size set', function () {
    [, $warehouse] = createWarehouseMapContext('no-floor-size', floorWidth: null, floorHeight: null);

    $location = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'A-1',
        'rank' => 1,
    ]);

    Livewire::test(WarehouseMap::class)
        ->call('saveLocationLayout', $location->id, 500.0, 500.0, 2.0, 2.0);

    $location->refresh();

    expect((float) $location->x)->toBe(500.0)
        ->and((float) $location->y)->toBe(500.0);
});

it('404s when saving the layout of a location belonging to a different warehouse', function () {
    createWarehouseMapContext('tenant-a');

    $otherSubdomain = Subdomain::query()->create([
        'subdomain' => 'warehouse-map-tenant-b',
        'name' => 'Warehouse Map Tenant B',
    ]);

    $warehouseB = Warehouse::query()->create([
        'subdomain_id' => $otherSubdomain->id,
        'name' => 'Warehouse Map Warehouse B',
        'currency' => 'EUR',
    ]);

    $locationInWarehouseB = StockLocation::query()->create([
        'warehouse_id' => $warehouseB->id,
        'name' => 'Foreign location',
        'rank' => 1,
    ]);

    expect(fn () => Livewire::test(WarehouseMap::class)
        ->call('saveLocationLayout', $locationInWarehouseB->id, 1.0, 1.0, 1.0, 1.0))
        ->toThrow(ModelNotFoundException::class);
});

it('does not add a new activity log entry when saving identical layout values twice', function () {
    [, $warehouse] = createWarehouseMapContext('idempotent');

    $location = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'A-1',
        'rank' => 1,
    ]);

    $livewire = Livewire::test(WarehouseMap::class);

    $livewire->call('saveLocationLayout', $location->id, 2.0, 2.0, 1.0, 1.0);
    $countAfterFirst = Activity::query()->where('subject_type', $location->getMorphClass())
        ->where('subject_id', $location->id)
        ->count();

    $livewire->call('saveLocationLayout', $location->id, 2.0, 2.0, 1.0, 1.0);
    $countAfterSecond = Activity::query()->where('subject_type', $location->getMorphClass())
        ->where('subject_id', $location->id)
        ->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});

it('returns locations ordered by rank, including both placed and unplaced ones', function () {
    [, $warehouse] = createWarehouseMapContext('get-locations');

    $second = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Second',
        'rank' => 2,
        'x' => 1.0,
        'y' => 1.0,
        'width' => 1.0,
        'height' => 1.0,
    ]);

    $first = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'First',
        'rank' => 1,
    ]);

    $page = new WarehouseMap;

    $locations = $page->getLocations();

    expect($locations->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and($locations->firstWhere('id', $first->id)->x)->toBeNull()
        ->and($locations->firstWhere('id', $second->id)->x)->not->toBeNull();
});
