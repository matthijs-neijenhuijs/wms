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
use Modules\Products\Models\Product;
use Modules\Products\Models\StockLocation;

function createLocationAssignmentOrder(string $suffix): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => "loc-assign-{$suffix}",
        'name' => "Location Assignment {$suffix}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Location Assignment Warehouse {$suffix}",
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => "client-loc-{$suffix}@example.com",
    ]);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => "REF-LOC-{$suffix}",
        'product_code' => "PROD-LOC-{$suffix}",
        'barcode' => "2000000{$suffix}",
        'name' => 'Location Assignment Product',
        'description' => 'Demo description',
    ]);

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
        'quantity' => 4,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    return [$order, $product, $warehouse];
}

it('assigns picklist rows to locations lowest-rank-first, spilling over when exhausted', function () {
    [$order, $product] = createLocationAssignmentOrder('1');
    $warehouse = $order->warehouse;

    $locationA = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'A-1', 'rank' => 1]);
    $locationB = StockLocation::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'B-1', 'rank' => 2]);

    $product->stockLocations()->attach($locationA->id, ['quantity' => 2]);
    $product->stockLocations()->attach($locationB->id, ['quantity' => 3]);

    app(OrderStatusTransitionService::class)->generatePicklist($order);

    $assignments = Picklist::query()->where('order_id', $order->id)->first()
        ->products()
        ->orderBy('id')
        ->pluck('stock_location_id')
        ->all();

    expect($assignments)->toHaveCount(4)
        ->and(array_slice($assignments, 0, 2))->each->toBe($locationA->id)
        ->and(array_slice($assignments, 2, 2))->each->toBe($locationB->id);
});

it('assigns null locations for a product that has no stock location', function () {
    [$order] = createLocationAssignmentOrder('2');

    app(OrderStatusTransitionService::class)->generatePicklist($order);

    $assignments = Picklist::query()->where('order_id', $order->id)->first()
        ->products()
        ->pluck('stock_location_id')
        ->all();

    expect($assignments)->toBe([null, null, null, null]);
});

it('assigns null locations without error for an order product with no matched product', function () {
    [$order] = createLocationAssignmentOrder('3');

    OrderProduct::query()->where('order_id', $order->id)->update(['product_id' => null]);

    app(OrderStatusTransitionService::class)->generatePicklist($order);

    $picklist = Picklist::query()->where('order_id', $order->id)->first();

    expect($picklist)->not->toBeNull()
        ->and($picklist->products()->pluck('stock_location_id')->all())->toBe([null, null, null, null]);
});
