<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;

uses(RefreshDatabase::class);

/**
 * @return array{0: Order, 1: Product, 2: StockProduct, 3: Product, 4: StockProduct}
 */
function createReservationTestContext(bool $reserveStock): array
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "reservation-{$counter}",
        'name' => "Reservation {$counter}",
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

    $productA = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => "REF-A-{$counter}",
        'product_code' => "PROD-A-{$counter}",
        'barcode' => "1000000000{$counter}",
        'name' => 'Product A',
        'description' => 'Product A description',
    ]);

    $stockA = StockProduct::query()->create([
        'product_id' => $productA->id,
        'on_stock_quantity' => 100,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => 100,
    ]);

    $productB = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => "REF-B-{$counter}",
        'product_code' => "PROD-B-{$counter}",
        'barcode' => "2000000000{$counter}",
        'name' => 'Product B',
        'description' => 'Product B description',
    ]);

    $stockB = StockProduct::query()->create([
        'product_id' => $productB->id,
        'on_stock_quantity' => 100,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => 100,
    ]);

    $status = OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => $reserveStock ? 'Reserved' : 'Concept',
        'color' => '#22c55e',
        'concepted' => true,
        'reserve_stock' => $reserveStock,
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
        'email' => $client->email,
        'order_statuses_id' => $status->id,
    ]);

    return [$order, $productA, $stockA, $productB, $stockB];
}

function createReservationOrderProduct(Order $order, Product $product, int $quantity): OrderProduct
{
    return OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => $quantity,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);
}

it('increases reserved_quantity when a line item is added to a reserve_stock order', function () {
    [$order, $productA, $stockA] = createReservationTestContext(reserveStock: true);

    createReservationOrderProduct($order, $productA, 4);

    expect($stockA->fresh()->reserved_quantity)->toBe(4);
});

it('recalculates reserved_quantity when a line item quantity increases and decreases', function () {
    [$order, $productA, $stockA] = createReservationTestContext(reserveStock: true);

    $orderProduct = createReservationOrderProduct($order, $productA, 4);
    expect($stockA->fresh()->reserved_quantity)->toBe(4);

    $orderProduct->update(['quantity' => 7]);
    expect($stockA->fresh()->reserved_quantity)->toBe(7);

    $orderProduct->update(['quantity' => 1]);
    expect($stockA->fresh()->reserved_quantity)->toBe(1);
});

it('moves the reservation to the new product when a line item product is swapped', function () {
    [$order, $productA, $stockA, $productB, $stockB] = createReservationTestContext(reserveStock: true);

    $orderProduct = createReservationOrderProduct($order, $productA, 3);
    expect($stockA->fresh()->reserved_quantity)->toBe(3);

    $orderProduct->update(['product_id' => $productB->id]);

    expect($stockA->fresh()->reserved_quantity)->toBe(0)
        ->and($stockB->fresh()->reserved_quantity)->toBe(3);
});

it('drops reserved_quantity to zero when a line item is deleted', function () {
    [$order, $productA, $stockA] = createReservationTestContext(reserveStock: true);

    $orderProduct = createReservationOrderProduct($order, $productA, 4);
    expect($stockA->fresh()->reserved_quantity)->toBe(4);

    $orderProduct->delete();

    expect($stockA->fresh()->reserved_quantity)->toBe(0);
});

it('never touches reserved_quantity when the order status does not require reservation', function () {
    [$order, $productA, $stockA] = createReservationTestContext(reserveStock: false);

    $orderProduct = createReservationOrderProduct($order, $productA, 4);
    expect($stockA->fresh()->reserved_quantity)->toBe(0);

    $orderProduct->update(['quantity' => 9]);
    expect($stockA->fresh()->reserved_quantity)->toBe(0);

    $orderProduct->delete();
    expect($stockA->fresh()->reserved_quantity)->toBe(0);
});
