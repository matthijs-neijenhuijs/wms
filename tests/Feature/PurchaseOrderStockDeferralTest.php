<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use App\Services\PurchaseOrderProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderProduct;
use Modules\Orders\Models\PurchaseOrderStatus;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;

uses(RefreshDatabase::class);

function createStockDeferralWarehouse(): Warehouse
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'po-stock-deferral-'.uniqid(),
        'name' => 'PO Stock Deferral',
    ]);

    app()->instance('current_subdomain', $subdomain);

    return Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Main Warehouse',
        'currency' => 'EUR',
    ]);
}

/**
 * @return array{0: Product, 1: StockProduct}
 */
function createDeferralProduct(Warehouse $warehouse, int $onStockQuantity): array
{
    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'REF-'.uniqid(),
        'product_code' => 'PROD-'.uniqid(),
        'barcode' => (string) random_int(1_000_000_000_000, 9_999_999_999_999),
        'name' => 'Deferral Product',
        'description' => 'Deferral description',
    ]);

    $stockProduct = StockProduct::query()->create([
        'product_id' => $product->id,
        'on_stock_quantity' => $onStockQuantity,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => $onStockQuantity,
    ]);

    return [$product, $stockProduct];
}

function createReservingOrder(Warehouse $warehouse, Product $product, int $quantity, string $deliveryDate): Order
{
    $status = OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Reserved '.uniqid(),
        'color' => '#22c55e',
        'reserve_stock' => true,
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'delivery_date' => $deliveryDate,
    ]);

    OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => $quantity,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    $order->update(['order_statuses_id' => $status->id]);

    return $order->fresh();
}

function createIncomingPurchaseOrder(
    Warehouse $warehouse,
    Product $product,
    int $quantity,
    string $expectedDeliveryDate,
    PurchaseOrderStatus $status = PurchaseOrderStatus::Purchased,
): PurchaseOrder {
    $purchaseOrder = PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'status' => $status,
        'expected_delivery_date' => $expectedDeliveryDate,
    ]);

    for ($i = 0; $i < $quantity; $i++) {
        PurchaseOrderProduct::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'barcode' => $product->barcode,
            'reference_code' => $product->reference_code,
            'product_title' => $product->name,
            'scanned' => false,
        ]);
    }

    return $purchaseOrder;
}

it('defers a client order\'s demand when a timely incoming purchase order covers it', function () {
    $warehouse = createStockDeferralWarehouse();
    [$product, $stockProduct] = createDeferralProduct($warehouse, onStockQuantity: 0);

    createIncomingPurchaseOrder($warehouse, $product, quantity: 10, expectedDeliveryDate: '2026-10-15');
    createReservingOrder($warehouse, $product, quantity: 10, deliveryDate: '2026-11-01');

    $stockProduct->refresh();

    expect($stockProduct->reserved_quantity)->toBe(0);
    expect($stockProduct->free_on_stock_quantity)->toBe(0);
});

it('leaves stock and reservations unchanged once the covering purchase order is fully scanned but not yet processed', function () {
    $warehouse = createStockDeferralWarehouse();
    [$product, $stockProduct] = createDeferralProduct($warehouse, onStockQuantity: 0);

    $purchaseOrder = createIncomingPurchaseOrder($warehouse, $product, quantity: 10, expectedDeliveryDate: '2026-10-15');
    createReservingOrder($warehouse, $product, quantity: 10, deliveryDate: '2026-11-01');

    PurchaseOrderProduct::query()
        ->where('purchase_order_id', $purchaseOrder->id)
        ->update(['scanned' => true]);

    app(PurchaseOrderProcessingService::class)->evaluateScanCompletion($purchaseOrder->fresh());

    $stockProduct->refresh();
    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Scanned);
    expect($stockProduct->on_stock_quantity)->toBe(0);
    expect($stockProduct->reserved_quantity)->toBe(0);
    expect($stockProduct->free_on_stock_quantity)->toBe(0);
});

it('increases stock and converts the deferred reservation once markProcessed is called', function () {
    $warehouse = createStockDeferralWarehouse();
    [$product, $stockProduct] = createDeferralProduct($warehouse, onStockQuantity: 0);

    $purchaseOrder = createIncomingPurchaseOrder($warehouse, $product, quantity: 10, expectedDeliveryDate: '2026-10-15');
    createReservingOrder($warehouse, $product, quantity: 10, deliveryDate: '2026-11-01');

    PurchaseOrderProduct::query()
        ->where('purchase_order_id', $purchaseOrder->id)
        ->update(['scanned' => true]);

    app(PurchaseOrderProcessingService::class)->evaluateScanCompletion($purchaseOrder->fresh());
    app(PurchaseOrderProcessingService::class)->markProcessed($purchaseOrder->fresh());

    $stockProduct->refresh();
    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Processed);
    expect($stockProduct->on_stock_quantity)->toBe(10);
    expect($stockProduct->reserved_quantity)->toBe(10);
    expect($stockProduct->free_on_stock_quantity)->toBe(0);
});

it('serves orders in delivery-date order when only some qualify for the incoming batch', function () {
    $warehouse = createStockDeferralWarehouse();
    [$product, $stockProduct] = createDeferralProduct($warehouse, onStockQuantity: 5);

    createIncomingPurchaseOrder($warehouse, $product, quantity: 8, expectedDeliveryDate: '2026-10-20');

    // Created in an order that would trip up a naive id/insertion-order
    // implementation: the later-delivery order is created first (lower id)
    // but must still be processed AFTER the earlier-delivery order, per the
    // delivery_date-ascending priority rule. If the implementation ever
    // dropped the explicit ->orderBy('orders.delivery_date'), this would
    // resolve to 8 instead of 5.
    createReservingOrder($warehouse, $product, quantity: 10, deliveryDate: '2026-11-01');
    createReservingOrder($warehouse, $product, quantity: 3, deliveryDate: '2026-10-10');

    $stockProduct->refresh();

    expect($stockProduct->reserved_quantity)->toBe(5);
    expect($stockProduct->free_on_stock_quantity)->toBe(0);
});

it('matches the legacy plain-sum reservation when no purchase orders exist for the product', function () {
    $warehouse = createStockDeferralWarehouse();
    [$product, $stockProduct] = createDeferralProduct($warehouse, onStockQuantity: 2);

    createReservingOrder($warehouse, $product, quantity: 5, deliveryDate: '2026-11-01');

    $stockProduct->refresh();

    expect($stockProduct->reserved_quantity)->toBe(5);
    expect($stockProduct->free_on_stock_quantity)->toBe(0);
});

it('contributes nothing to deferral while a purchase order is still in concept status', function () {
    $warehouse = createStockDeferralWarehouse();
    [$product, $stockProduct] = createDeferralProduct($warehouse, onStockQuantity: 0);

    createIncomingPurchaseOrder($warehouse, $product, quantity: 10, expectedDeliveryDate: '2026-10-15', status: PurchaseOrderStatus::Concept);
    createReservingOrder($warehouse, $product, quantity: 10, deliveryDate: '2026-11-01');

    $stockProduct->refresh();

    expect($stockProduct->reserved_quantity)->toBe(10);
    expect($stockProduct->free_on_stock_quantity)->toBe(0);
});

it('excludes a processed purchase order from the deferral batch query to avoid double-counting stock', function () {
    $warehouse = createStockDeferralWarehouse();
    [$product, $stockProduct] = createDeferralProduct($warehouse, onStockQuantity: 0);

    createIncomingPurchaseOrder($warehouse, $product, quantity: 10, expectedDeliveryDate: '2026-10-15', status: PurchaseOrderStatus::Processed);
    createReservingOrder($warehouse, $product, quantity: 10, deliveryDate: '2026-11-01');

    $stockProduct->refresh();

    expect($stockProduct->reserved_quantity)->toBe(10);
    expect($stockProduct->free_on_stock_quantity)->toBe(0);
});

it('refreshes reserved quantity back up once a purchased purchase order is cancelled', function () {
    $warehouse = createStockDeferralWarehouse();
    [$product, $stockProduct] = createDeferralProduct($warehouse, onStockQuantity: 0);

    $purchaseOrder = createIncomingPurchaseOrder($warehouse, $product, quantity: 10, expectedDeliveryDate: '2026-10-15');
    createReservingOrder($warehouse, $product, quantity: 10, deliveryDate: '2026-11-01');

    $stockProduct->refresh();
    expect($stockProduct->reserved_quantity)->toBe(0);

    app(PurchaseOrderProcessingService::class)->cancel($purchaseOrder->fresh());

    $stockProduct->refresh();

    expect($stockProduct->reserved_quantity)->toBe(10);
    expect($stockProduct->free_on_stock_quantity)->toBe(0);
});
