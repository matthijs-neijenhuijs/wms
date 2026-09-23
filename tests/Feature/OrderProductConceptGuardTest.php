<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clients\Models\Client;
use Modules\Orders\Exceptions\OrderProductsLockedException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Products\Models\Product;

uses(RefreshDatabase::class);

/**
 * @return array{0: Order, 1: Product}
 */
function createConceptGuardTestOrder(?bool $concepted): array
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "concept-guard-{$counter}",
        'name' => "Concept Guard {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "CG{$counter} Warehouse",
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => "client-cg-{$counter}@example.com",
    ]);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => "REF-CG-{$counter}",
        'product_code' => "PROD-CG-{$counter}",
        'barcode' => "3000000000{$counter}",
        'name' => 'Product CG',
        'description' => 'Product CG description',
    ]);

    $statusId = null;

    if ($concepted !== null) {
        $status = OrderStatus::query()->create([
            'warehouse_id' => $warehouse->id,
            'name' => $concepted ? 'Concept' : 'Confirmed',
            'color' => '#22c55e',
            'concepted' => $concepted,
        ]);

        $statusId = $status->id;
    }

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
        'email' => $client->email,
        'order_statuses_id' => $statusId,
    ]);

    return [$order, $product];
}

it('reports canModifyProducts true only when the status is concepted', function () {
    expect((new OrderStatus(['concepted' => true]))->canModifyProducts())->toBeTrue()
        ->and((new OrderStatus(['concepted' => false]))->canModifyProducts())->toBeFalse();
});

it('allows adding a line item when the order status is concepted', function () {
    [$order, $product] = createConceptGuardTestOrder(concepted: true);

    $orderProduct = OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    expect($orderProduct->exists)->toBeTrue();
});

it('blocks adding a line item when the order status is not concepted', function () {
    [$order, $product] = createConceptGuardTestOrder(concepted: false);

    expect(fn () => OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]))->toThrow(OrderProductsLockedException::class);

    expect(OrderProduct::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

it('blocks editing a line item when the order status is not concepted', function () {
    [$order, $product] = createConceptGuardTestOrder(concepted: true);

    $orderProduct = OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    $order->orderStatus->update(['concepted' => false]);

    expect(fn () => $orderProduct->update(['quantity' => 5]))
        ->toThrow(OrderProductsLockedException::class);

    expect($orderProduct->fresh()->quantity)->toBe(2);
});

it('blocks deleting a line item when the order status is not concepted', function () {
    [$order, $product] = createConceptGuardTestOrder(concepted: true);

    $orderProduct = OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    $order->orderStatus->update(['concepted' => false]);

    expect(fn () => $orderProduct->delete())
        ->toThrow(OrderProductsLockedException::class);

    expect(OrderProduct::query()->whereKey($orderProduct->id)->exists())->toBeTrue();
});

it('allows adding a line item when the order has no status yet', function () {
    [$order, $product] = createConceptGuardTestOrder(concepted: null);

    $orderProduct = OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 2,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    expect($orderProduct->exists)->toBeTrue();
});
