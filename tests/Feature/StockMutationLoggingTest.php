<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use App\Services\PurchaseOrderProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderProduct;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Users\Models\User;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('logs one order-attributed stock_product activity when reduceStock runs, with no duplicate dirty-diff entry', function () {
    $subdomain = Subdomain::query()->create(['subdomain' => 'stock-mutation-log', 'name' => 'Stock Mutation Log']);
    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create(['subdomain_id' => $subdomain->id, 'name' => 'Main Warehouse', 'currency' => 'EUR']);
    $user = User::query()->create(['subdomain_id' => $subdomain->id, 'name' => 'Picker', 'email' => 'picker@example.com', 'password' => 'password']);
    test()->actingAs($user);

    $client = Client::query()->create(['warehouse_id' => $warehouse->id, 'active' => true, 'email' => 'client@example.com']);
    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id, 'active' => true, 'reference_code' => 'REF-001',
        'product_code' => 'PROD-001', 'barcode' => '1234567890123', 'name' => 'Demo Product', 'description' => 'Demo',
    ]);
    $stockProduct = StockProduct::query()->create([
        'product_id' => $product->id, 'on_stock_quantity' => 10, 'reserved_quantity' => 0,
        'reserved_on_picklists' => 3, 'free_on_stock_quantity' => 7,
    ]);
    $order = Order::query()->create(['warehouse_id' => $warehouse->id, 'client_id' => $client->id, 'email' => $client->email]);
    OrderProduct::query()->create([
        'order_id' => $order->id, 'product_id' => $product->id, 'name' => $product->name,
        'quantity' => 3, 'barcode' => $product->barcode, 'reference_code' => $product->reference_code, 'price' => 9.99,
    ]);

    $status = OrderStatus::query()->create(['warehouse_id' => $warehouse->id, 'name' => 'Picked', 'color' => '#3b82f6', 'reduce_stock' => true]);
    $order->update(['order_statuses_id' => $status->id]);

    $stockProduct->refresh();
    expect($stockProduct->on_stock_quantity)->toBe(7);

    $activities = Activity::query()
        ->where('subject_type', $stockProduct->getMorphClass())
        ->where('subject_id', $stockProduct->id)
        ->get();

    // "created" (fixture) + exactly one manual "updated" entry — no auto dirty-diff duplicate.
    expect($activities)->toHaveCount(2);

    $mutation = $activities->firstWhere('event', 'updated');
    expect($mutation)->not->toBeNull();
    expect($mutation->causer_id)->toBe($user->id);
    expect($mutation->getProperty('order_id'))->toBe($order->id);
    expect($mutation->getProperty('order_reference'))->toBe($order->generated_custom_order_id);
    expect($mutation->getProperty('quantity_delta'))->toBe(-3);
    expect($mutation->getProperty('quantity_before'))->toBe(10);
    expect($mutation->getProperty('quantity_after'))->toBe(7);
    expect($mutation->getProperty('direction'))->toBe('decrease');
    expect($mutation->properties->get('old'))->toBeNull(); // Revert action must stay disabled for this entry.
});

it('logs one purchase-order-attributed stock_product activity when a purchase order is fully scanned', function () {
    $subdomain = Subdomain::query()->create(['subdomain' => 'stock-mutation-log-po', 'name' => 'Stock Mutation Log PO']);
    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create(['subdomain_id' => $subdomain->id, 'name' => 'Main Warehouse', 'currency' => 'EUR']);
    $user = User::query()->create(['subdomain_id' => $subdomain->id, 'name' => 'Receiver', 'email' => 'receiver@example.com', 'password' => 'password']);
    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id, 'active' => true, 'reference_code' => 'REF-002',
        'product_code' => 'PROD-002', 'barcode' => '9876543210987', 'name' => 'Restocked Product', 'description' => 'Demo',
    ]);
    $stockProduct = StockProduct::query()->create([
        'product_id' => $product->id, 'on_stock_quantity' => 2, 'reserved_quantity' => 0,
        'reserved_on_picklists' => 0, 'free_on_stock_quantity' => 2,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create(['warehouse_id' => $warehouse->id, 'expected_delivery_date' => now()->addDay()->toDateString()]);
    for ($i = 0; $i < 5; $i++) {
        PurchaseOrderProduct::query()->create([
            'purchase_order_id' => $purchaseOrder->id, 'product_id' => $product->id,
            'barcode' => $product->barcode, 'reference_code' => $product->reference_code,
            'product_title' => $product->name, 'scanned' => true,
        ]);
    }

    app(PurchaseOrderProcessingService::class)->processScanCompletion($purchaseOrder->fresh(), causerId: $user->id);

    $stockProduct->refresh();
    expect($stockProduct->on_stock_quantity)->toBe(7);

    $mutation = Activity::query()
        ->where('subject_type', $stockProduct->getMorphClass())
        ->where('subject_id', $stockProduct->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($mutation)->not->toBeNull();
    expect($mutation->causer_id)->toBe($user->id);
    expect($mutation->getProperty('purchase_order_id'))->toBe($purchaseOrder->id);
    expect($mutation->getProperty('purchase_order_reference'))->toBe($purchaseOrder->fresh()->generated_custom_purchase_order_id);
    expect($mutation->getProperty('quantity_delta'))->toBe(5);
    expect($mutation->getProperty('direction'))->toBe('increase');
});
