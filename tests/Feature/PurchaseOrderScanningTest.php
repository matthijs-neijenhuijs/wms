<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use App\Services\PurchaseOrderProcessingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Orders\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderFailedProduct;
use Modules\Orders\Models\PurchaseOrderProduct;
use Modules\Orders\Models\PurchaseOrderStatus;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

function createPurchaseOrderScanningContext(int $onStockQuantity = 5, PurchaseOrderStatus $status = PurchaseOrderStatus::Received): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'po-scanning',
        'name' => 'PO Scanning',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Main Warehouse',
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Scanner Operator',
        'email' => 'scanner@example.com',
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setTenant($warehouse);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'REF-001',
        'product_code' => 'PROD-001',
        'barcode' => '1111111111111',
        'name' => 'Demo Product',
        'description' => 'Demo description',
    ]);

    $stockProduct = StockProduct::query()->create([
        'product_id' => $product->id,
        'on_stock_quantity' => $onStockQuantity,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => $onStockQuantity,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'status' => $status,
        'expected_delivery_date' => now()->addDay()->toDateString(),
    ]);

    return [$user, $warehouse, $product, $stockProduct, $purchaseOrder];
}

it('marks a matching purchase order product as scanned', function () {
    [$user, , $product, , $purchaseOrder] = createPurchaseOrderScanningContext();

    $item = PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'product_title' => $product->name,
        'scanned' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->call('scanProductBarcode', $product->barcode);

    expect($item->fresh()->scanned)->toBeTrue();
    expect(PurchaseOrderFailedProduct::query()->count())->toBe(0);
});

it('records a failed scan for an unknown barcode and increments repeated failures', function () {
    [$user, , , , $purchaseOrder] = createPurchaseOrderScanningContext();

    $this->actingAs($user);

    $component = Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()]);

    $component->call('scanProductBarcode', 'unknown-barcode');
    $component->call('scanProductBarcode', 'unknown-barcode');

    $failedProduct = PurchaseOrderFailedProduct::query()
        ->where('purchase_order_id', $purchaseOrder->id)
        ->where('barcode', 'unknown-barcode')
        ->first();

    expect($failedProduct)->not->toBeNull();
    expect($failedProduct->total_quantity_scanned)->toBe(2);
});

it('records a failed scan when a barcode is scanned twice', function () {
    [$user, , $product, , $purchaseOrder] = createPurchaseOrderScanningContext();

    PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'product_title' => $product->name,
        'scanned' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()]);
    $component->call('scanProductBarcode', $product->barcode);
    $component->call('scanProductBarcode', $product->barcode);

    $failedProduct = PurchaseOrderFailedProduct::query()
        ->where('purchase_order_id', $purchaseOrder->id)
        ->where('barcode', $product->barcode)
        ->first();

    expect($failedProduct)->not->toBeNull();
    expect($failedProduct->reference_code)->toBe($product->reference_code);
    expect($failedProduct->product_title)->toBe($product->name);
});

it('marks the purchase order status as scanned without touching stock once all rows are scanned', function () {
    [$user, , $product, $stockProduct, $purchaseOrder] = createPurchaseOrderScanningContext(onStockQuantity: 5);

    PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'product_title' => $product->name,
        'scanned' => false,
    ]);

    PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'product_title' => $product->name,
        'scanned' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()]);
    $component->call('scanProductBarcode', $product->barcode);
    $component->call('scanProductBarcode', $product->barcode);

    $purchaseOrder->refresh();
    $stockProduct->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Scanned);
    expect($stockProduct->on_stock_quantity)->toBe(5);
});

it('counts an unmatched product row toward full-scan completion without affecting stock', function () {
    [$user, , $product, $stockProduct, $purchaseOrder] = createPurchaseOrderScanningContext(onStockQuantity: 5);

    PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'product_title' => $product->name,
        'scanned' => false,
    ]);

    PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => null,
        'barcode' => 'unrecognized-barcode',
        'product_title' => 'Unknown product',
        'scanned' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()]);
    $component->call('scanProductBarcode', $product->barcode);
    $component->call('scanProductBarcode', 'unrecognized-barcode');

    $purchaseOrder->refresh();
    $stockProduct->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Scanned);
    expect($stockProduct->on_stock_quantity)->toBe(5);
});

it('transitions directly from purchased to scanned without requiring received first', function () {
    [$user, , $product, , $purchaseOrder] = createPurchaseOrderScanningContext(status: PurchaseOrderStatus::Purchased);

    PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'product_title' => $product->name,
        'scanned' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->call('scanProductBarcode', $product->barcode);

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Scanned);
});

it('only increases stock once markProcessed is called after scanning completes', function () {
    [$user, , $product, $stockProduct, $purchaseOrder] = createPurchaseOrderScanningContext(onStockQuantity: 5);

    PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'product_title' => $product->name,
        'scanned' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->call('scanProductBarcode', $product->barcode);

    $purchaseOrder->refresh();
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Scanned);
    expect($stockProduct->fresh()->on_stock_quantity)->toBe(5);

    app(PurchaseOrderProcessingService::class)->markProcessed($purchaseOrder, $user->id);

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Processed);
    expect($stockProduct->fresh()->on_stock_quantity)->toBe(6);
});
