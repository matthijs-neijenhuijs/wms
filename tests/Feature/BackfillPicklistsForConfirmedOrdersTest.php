<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Picklists\Models\Picklist;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;

use function Pest\Laravel\artisan;

function createBackfillOrder(bool $generatePicklist): Order
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "backfill-{$counter}",
        'name' => "Backfill {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "WH{$counter} Warehouse",
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => "client-{$counter}@example.com",
    ]);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => "REF-{$counter}",
        'product_code' => "PROD-{$counter}",
        'barcode' => "12345678901{$counter}",
        'name' => 'Demo Product',
        'description' => 'Demo description',
    ]);

    StockProduct::query()->create([
        'product_id' => $product->id,
        'on_stock_quantity' => 10,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => 10,
    ]);

    $status = OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Confirmed',
        'color' => '#22c55e',
        'concepted' => true,
        'generate_picklist' => $generatePicklist,
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
        'quantity' => 2,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    return $order;
}

it('creates a picklist for an order whose status requires one and has none yet', function () {
    $order = createBackfillOrder(generatePicklist: true);

    artisan('app:backfill-picklists-for-confirmed-orders')->assertSuccessful();

    expect(Picklist::query()->where('order_id', $order->id)->exists())->toBeTrue();
});

it('does not create a second picklist for an order that already has one', function () {
    $order = createBackfillOrder(generatePicklist: true);

    Picklist::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'order_id' => $order->id,
    ]);

    artisan('app:backfill-picklists-for-confirmed-orders')->assertSuccessful();

    expect(Picklist::query()->where('order_id', $order->id)->count())->toBe(1);
});

it('does not create a picklist for an order whose status does not require one', function () {
    $order = createBackfillOrder(generatePicklist: false);

    artisan('app:backfill-picklists-for-confirmed-orders')->assertSuccessful();

    expect(Picklist::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

it('limits processing to the order targeted by --order-id', function () {
    $targetOrder = createBackfillOrder(generatePicklist: true);
    $otherOrder = createBackfillOrder(generatePicklist: true);

    artisan('app:backfill-picklists-for-confirmed-orders', ['--order-id' => $targetOrder->id])
        ->assertSuccessful();

    expect(Picklist::query()->where('order_id', $targetOrder->id)->exists())->toBeTrue()
        ->and(Picklist::query()->where('order_id', $otherOrder->id)->exists())->toBeFalse();
});

it('reports when no orders require picklist backfill', function () {
    createBackfillOrder(generatePicklist: false);

    artisan('app:backfill-picklists-for-confirmed-orders')
        ->assertSuccessful()
        ->expectsOutput('No orders found that require picklist backfill.');
});
