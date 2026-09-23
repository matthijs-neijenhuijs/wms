<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

/**
 * @return array{0: Order, 1: list<Product>, 2: OrderStatus}
 */
function createStockReductionTestContext(int $productCount): array
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "stock-reduce-notify-{$counter}",
        'name' => "Stock Reduce Notify {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "SRN{$counter} Warehouse",
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => "client-srn-{$counter}@example.com",
    ]);

    $conceptStatus = OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Concept',
        'color' => '#22c55e',
        'concepted' => true,
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
        'email' => $client->email,
        'order_statuses_id' => $conceptStatus->id,
    ]);

    $products = [];

    for ($i = 1; $i <= $productCount; $i++) {
        $product = Product::query()->create([
            'warehouse_id' => $warehouse->id,
            'active' => true,
            'reference_code' => "REF-SRN-{$counter}-{$i}",
            'product_code' => "PROD-SRN-{$counter}-{$i}",
            'barcode' => "400000{$counter}0{$i}",
            'name' => "Notify Product {$i}",
            'description' => 'Notify product description',
        ]);

        StockProduct::query()->create([
            'product_id' => $product->id,
            'on_stock_quantity' => 100,
            'reserved_quantity' => 0,
            'reserved_on_picklists' => 0,
            'free_on_stock_quantity' => 100,
        ]);

        OrderProduct::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'name' => "Notify Product {$i}",
            'quantity' => $i + 1,
            'barcode' => $product->barcode,
            'reference_code' => $product->reference_code,
            'price' => 9.99,
        ]);

        $products[] = $product;
    }

    $reduceStockStatus = OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Processing',
        'color' => '#3b82f6',
        'reduce_stock' => true,
    ]);

    return [$order, $products, $reduceStockStatus];
}

it('creates one database notification for a single-product stock reduction', function () {
    [$order, , $reduceStockStatus] = createStockReductionTestContext(productCount: 1);

    $user = User::query()->create([
        'subdomain_id' => $order->warehouse->subdomain_id,
        'name' => 'Warehouse Manager',
        'email' => 'manager-srn-1@example.com',
        'password' => 'password',
    ]);

    test()->actingAs($user);

    $order->update(['order_statuses_id' => $reduceStockStatus->id]);

    $notifications = DatabaseNotification::query()
        ->where('notifiable_id', $user->id)
        ->where('notifiable_type', $user->getMorphClass())
        ->get();

    expect($notifications)->toHaveCount(1);

    $data = $notifications->first()->data;

    expect($data['title'])->toBe('Stock reduced')
        ->and($data['body'])->toContain('Notify Product 1')
        ->and($data['body'])->toContain('2');
});

it('creates one database notification per product line, not one aggregate message', function () {
    [$order, , $reduceStockStatus] = createStockReductionTestContext(productCount: 2);

    $user = User::query()->create([
        'subdomain_id' => $order->warehouse->subdomain_id,
        'name' => 'Warehouse Manager',
        'email' => 'manager-srn-2@example.com',
        'password' => 'password',
    ]);

    test()->actingAs($user);

    $order->update(['order_statuses_id' => $reduceStockStatus->id]);

    $notifications = DatabaseNotification::query()
        ->where('notifiable_id', $user->id)
        ->where('notifiable_type', $user->getMorphClass())
        ->get();

    expect($notifications)->toHaveCount(2);

    $bodies = $notifications->pluck('data')->pluck('body')->implode(' | ');

    expect($bodies)->toContain('Notify Product 1')
        ->toContain('Notify Product 2')
        ->toContain('2')
        ->toContain('3');
});

it('reduces stock without creating a notification when there is no causer', function () {
    [$order, $products, $reduceStockStatus] = createStockReductionTestContext(productCount: 1);

    $order->update(['order_statuses_id' => $reduceStockStatus->id]);

    expect(DatabaseNotification::query()->count())->toBe(0);

    $stockProduct = StockProduct::where('product_id', $products[0]->id)->first();
    expect($stockProduct->on_stock_quantity)->toBe(98);
});
