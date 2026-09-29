<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use Modules\Products\Filament\Resources\Products\Pages\EditProduct;
use Modules\Products\Filament\Resources\Products\RelationManagers\StockLocationsRelationManager;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockLocation;
use Modules\Users\Models\User;

/**
 * @return array{0: Product, 1: Warehouse}
 */
function createProductStockLocationsContext(string $suffix): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => "product-stock-locations-{$suffix}",
        'name' => "Product Stock Locations {$suffix}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Product Stock Locations Warehouse {$suffix}",
        'currency' => 'EUR',
    ]);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => "REF-PSL-{$suffix}",
        'product_code' => "PROD-PSL-{$suffix}",
        'barcode' => "4000000{$suffix}",
        'name' => "Product Stock Locations Product {$suffix}",
        'description' => 'Demo description',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Product Stock Locations Manager {$suffix}",
        'email' => "product-stock-locations-manager-{$suffix}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    return [$product, $warehouse];
}

it('attaches a location to a product with a quantity', function () {
    [$product, $warehouse] = createProductStockLocationsContext('attach');

    $location = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'A-1', 'rank' => 1]);

    Livewire::test(StockLocationsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make('attach')->table(), data: [
            'recordId' => $location->id,
            'quantity' => 12,
        ])
        ->assertHasNoFormErrors();

    expect(DB::table('stock_location_product')
        ->where('product_id', $product->id)
        ->where('stock_location_id', $location->id)
        ->where('quantity', 12)
        ->exists())->toBeTrue();
});

it('updates the pivot quantity via the edit action without creating a duplicate row', function () {
    [$product, $warehouse] = createProductStockLocationsContext('edit');

    $location = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'A-1', 'rank' => 1]);
    $product->stockLocations()->attach($location->id, ['quantity' => 5]);

    Livewire::test(StockLocationsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make('edit')->table($location), data: [
            'quantity' => 9,
        ])
        ->assertHasNoFormErrors();

    expect(DB::table('stock_location_product')
        ->where('product_id', $product->id)
        ->where('stock_location_id', $location->id)
        ->count())->toBe(1)
        ->and(DB::table('stock_location_product')
            ->where('product_id', $product->id)
            ->where('stock_location_id', $location->id)
            ->value('quantity'))->toBe(9);
});

it('detaches a location without deleting the product or the location', function () {
    [$product, $warehouse] = createProductStockLocationsContext('detach');

    $location = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'A-1', 'rank' => 1]);
    $product->stockLocations()->attach($location->id, ['quantity' => 5]);

    Livewire::test(StockLocationsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make('detach')->table($location));

    expect(DB::table('stock_location_product')
        ->where('product_id', $product->id)
        ->where('stock_location_id', $location->id)
        ->exists())->toBeFalse()
        ->and(Product::query()->find($product->id))->not->toBeNull()
        ->and(StockLocation::query()->find($location->id))->not->toBeNull();
});

it('rejects attaching the same location to a product twice', function () {
    [$product, $warehouse] = createProductStockLocationsContext('duplicate');

    $location = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'A-1', 'rank' => 1]);
    $product->stockLocations()->attach($location->id, ['quantity' => 5]);

    expect(fn () => $product->stockLocations()->attach($location->id, ['quantity' => 3]))
        ->toThrow(QueryException::class);

    expect(DB::table('stock_location_product')
        ->where('product_id', $product->id)
        ->where('stock_location_id', $location->id)
        ->count())->toBe(1);
});
