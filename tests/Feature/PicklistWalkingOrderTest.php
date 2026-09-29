<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use App\Services\OrderStatusTransitionService;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Picklists\Models\Picklist;
use Modules\Picklists\Models\PicklistProduct;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockLocation;

function createWalkingOrderWarehouse(string $suffix): Warehouse
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => "walking-order-{$suffix}",
        'name' => "Walking Order {$suffix}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    return Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Walking Order Warehouse {$suffix}",
        'currency' => 'EUR',
    ]);
}

function createWalkingOrderPicklist(Warehouse $warehouse): Picklist
{
    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
    ]);

    return Picklist::query()->create([
        'warehouse_id' => $warehouse->id,
        'order_id' => $order->id,
    ]);
}

it('sorts picklist rows by the assigned location\'s current rank, with unlocated rows last', function () {
    $warehouse = createWalkingOrderWarehouse('1');

    $locationHighRank = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'Rank 5', 'rank' => 5]);
    $locationLowRank = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'Rank 2', 'rank' => 2]);

    $picklist = createWalkingOrderPicklist($warehouse);

    $unlocated = PicklistProduct::query()->create([
        'picklist_id' => $picklist->id,
        'stock_location_id' => null,
        'barcode' => 'BC-UNLOCATED',
        'product_title' => 'Unlocated Product',
    ]);

    $atHighRank = PicklistProduct::query()->create([
        'picklist_id' => $picklist->id,
        'stock_location_id' => $locationHighRank->id,
        'barcode' => 'BC-HIGH',
        'product_title' => 'High Rank Product',
    ]);

    $atLowRank = PicklistProduct::query()->create([
        'picklist_id' => $picklist->id,
        'stock_location_id' => $locationLowRank->id,
        'barcode' => 'BC-LOW',
        'product_title' => 'Low Rank Product',
    ]);

    $orderedIds = $picklist->fresh()->products->pluck('id')->all();

    expect($orderedIds)->toBe([$atLowRank->id, $atHighRank->id, $unlocated->id]);

    // Re-rank the high-rank location below the low-rank one: order must flip
    // on next fetch, proving the sort re-reads the location's live rank
    // rather than a value captured when the PicklistProduct row was made.
    $locationHighRank->update(['rank' => 1]);

    $reorderedIds = $picklist->fresh()->products->pluck('id')->all();

    expect($reorderedIds)->toBe([$atHighRank->id, $atLowRank->id, $unlocated->id]);
});

it('keeps two unlocated rows tie-broken by barcode, both after every located row', function () {
    $warehouse = createWalkingOrderWarehouse('2');

    $location = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'A-1', 'rank' => 1]);

    $picklist = createWalkingOrderPicklist($warehouse);

    $unlocatedZ = PicklistProduct::query()->create([
        'picklist_id' => $picklist->id,
        'stock_location_id' => null,
        'barcode' => 'BC-Z',
        'product_title' => 'Unlocated Z',
    ]);

    $unlocatedA = PicklistProduct::query()->create([
        'picklist_id' => $picklist->id,
        'stock_location_id' => null,
        'barcode' => 'BC-A',
        'product_title' => 'Unlocated A',
    ]);

    $located = PicklistProduct::query()->create([
        'picklist_id' => $picklist->id,
        'stock_location_id' => $location->id,
        'barcode' => 'BC-LOCATED',
        'product_title' => 'Located Product',
    ]);

    $orderedIds = $picklist->fresh()->products->pluck('id')->all();

    expect($orderedIds)->toBe([$located->id, $unlocatedA->id, $unlocatedZ->id]);
});

it('freezes a picklist row\'s location assignment at generation time even if the product is reassigned afterward', function () {
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'walking-order-frozen',
        'name' => 'Walking Order Frozen',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Walking Order Frozen Warehouse',
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => 'client-frozen@example.com',
    ]);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'REF-FROZEN',
        'product_code' => 'PROD-FROZEN',
        'barcode' => '3000000001',
        'name' => 'Frozen Assignment Product',
        'description' => 'Demo description',
    ]);

    $originalLocation = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'Original', 'rank' => 1]);
    $newLocation = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'New Lower Rank', 'rank' => 0]);

    $product->stockLocations()->attach($originalLocation->id, ['quantity' => 1]);

    $status = OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Confirmed',
        'color' => '#22c55e',
        'concepted' => true,
        'generate_picklist' => true,
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
        'email' => $client->email,
        'order_statuses_id' => $status->id,
    ]);

    OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 1,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    app(OrderStatusTransitionService::class)->generatePicklist($order);

    $picklist = Picklist::query()->where('order_id', $order->id)->firstOrFail();

    $picklistProduct = PicklistProduct::query()
        ->where('picklist_id', $picklist->id)
        ->firstOrFail();

    expect($picklistProduct->stock_location_id)->toBe($originalLocation->id);

    // Reassign the product to a different (lower-rank) location after the
    // picklist already exists.
    $product->stockLocations()->updateExistingPivot($originalLocation->id, ['quantity' => 0]);
    $product->stockLocations()->attach($newLocation->id, ['quantity' => 1]);

    expect($picklistProduct->fresh()->stock_location_id)->toBe($originalLocation->id);
});
