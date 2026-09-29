<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use App\Services\StockLocationAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockLocation;

uses(RefreshDatabase::class);

function createAllocatorWarehouse(string $suffix): Warehouse
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => "allocator-{$suffix}",
        'name' => "Allocator {$suffix}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    return Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Allocator Warehouse {$suffix}",
        'currency' => 'EUR',
    ]);
}

function createAllocatorProduct(Warehouse $warehouse, string $suffix): Product
{
    return Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => "REF-{$suffix}",
        'product_code' => "PROD-{$suffix}",
        'barcode' => "1000000{$suffix}",
        'name' => "Allocator Product {$suffix}",
        'description' => 'Allocator product description',
    ]);
}

function createAllocatorLocation(Warehouse $warehouse, string $name, int $rank): StockLocation
{
    return StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => $name,
        'rank' => $rank,
    ]);
}

it('allocates all units to a single location when it has enough quantity', function () {
    $warehouse = createAllocatorWarehouse('1');
    $product = createAllocatorProduct($warehouse, '1');
    $location = createAllocatorLocation($warehouse, 'A-1', 1);

    $product->stockLocations()->attach($location->id, ['quantity' => 5]);

    $assignments = app(StockLocationAllocator::class)->allocate($product->id, 3);

    expect($assignments)->toBe([$location->id, $location->id, $location->id]);
});

it('spills over to the next-lowest-rank location exactly at the boundary', function () {
    $warehouse = createAllocatorWarehouse('2');
    $product = createAllocatorProduct($warehouse, '2');
    $locationA = createAllocatorLocation($warehouse, 'A-1', 1);
    $locationB = createAllocatorLocation($warehouse, 'B-1', 2);

    $product->stockLocations()->attach($locationA->id, ['quantity' => 2]);
    $product->stockLocations()->attach($locationB->id, ['quantity' => 3]);

    $assignments = app(StockLocationAllocator::class)->allocate($product->id, 4);

    expect($assignments)->toBe([$locationA->id, $locationA->id, $locationB->id, $locationB->id]);
});

it('leaves unmet demand unlocated when total location quantity is undersupplied', function () {
    $warehouse = createAllocatorWarehouse('3');
    $product = createAllocatorProduct($warehouse, '3');
    $location = createAllocatorLocation($warehouse, 'A-1', 1);

    $product->stockLocations()->attach($location->id, ['quantity' => 1]);

    $assignments = app(StockLocationAllocator::class)->allocate($product->id, 3);

    expect($assignments)->toBe([$location->id, null, null]);
});

it('returns all-null assignments for a product with no stock locations', function () {
    $warehouse = createAllocatorWarehouse('4');
    $product = createAllocatorProduct($warehouse, '4');

    $assignments = app(StockLocationAllocator::class)->allocate($product->id, 2);

    expect($assignments)->toBe([null, null]);
});

it('never allocates a location belonging to a different product', function () {
    $warehouse = createAllocatorWarehouse('5');
    $productA = createAllocatorProduct($warehouse, '5a');
    $productB = createAllocatorProduct($warehouse, '5b');
    $locationA = createAllocatorLocation($warehouse, 'A-1', 1);
    $locationB = createAllocatorLocation($warehouse, 'B-1', 2);

    $productA->stockLocations()->attach($locationA->id, ['quantity' => 5]);
    $productB->stockLocations()->attach($locationB->id, ['quantity' => 5]);

    $assignments = app(StockLocationAllocator::class)->allocate($productA->id, 2);

    expect($assignments)->toBe([$locationA->id, $locationA->id])
        ->and($assignments)->not->toContain($locationB->id);
});

it('returns an empty array when requesting zero units', function () {
    $warehouse = createAllocatorWarehouse('6');
    $product = createAllocatorProduct($warehouse, '6');

    $assignments = app(StockLocationAllocator::class)->allocate($product->id, 0);

    expect($assignments)->toBe([]);
});
