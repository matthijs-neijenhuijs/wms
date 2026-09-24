<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderProduct;
use Modules\Orders\Models\PurchaseOrderStatus;
use Modules\Products\Filament\Resources\Products\Pages\EditProductStock;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

function createIncomingStockContext(): array
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "incoming-stock-{$counter}",
        'name' => "Incoming Stock {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "IS{$counter} Warehouse",
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Warehouse Manager',
        'email' => "manager-{$counter}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setTenant($warehouse);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => "REF-{$counter}",
        'product_code' => "PROD-{$counter}",
        'barcode' => (string) random_int(1_000_000_000_000, 9_999_999_999_999),
        'name' => 'Incoming Stock Product',
        'description' => 'Description',
    ]);

    StockProduct::query()->create([
        'product_id' => $product->id,
        'on_stock_quantity' => 0,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => 0,
    ]);

    return [$user, $warehouse, $product];
}

function addPurchaseOrderBatch(Warehouse $warehouse, Product $product, int $quantity, string $expectedDeliveryDate, PurchaseOrderStatus $status): PurchaseOrder
{
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

it('sums multiple purchase orders sharing the same expected delivery date', function () {
    [, $warehouse, $product] = createIncomingStockContext();

    addPurchaseOrderBatch($warehouse, $product, 3, '2026-10-15', PurchaseOrderStatus::Purchased);
    addPurchaseOrderBatch($warehouse, $product, 4, '2026-10-15', PurchaseOrderStatus::Received);

    $batches = PurchaseOrder::incomingBatchesForProduct($product->id);

    expect($batches)->toHaveCount(1);
    expect($batches->first()->quantity)->toBe(7);
    expect($batches->first()->expected_delivery_date)->toStartWith('2026-10-15');
});

it('keeps different expected delivery dates separate and sorted ascending', function () {
    [, $warehouse, $product] = createIncomingStockContext();

    addPurchaseOrderBatch($warehouse, $product, 2, '2026-11-01', PurchaseOrderStatus::Purchased);
    addPurchaseOrderBatch($warehouse, $product, 5, '2026-10-01', PurchaseOrderStatus::Scanned);

    $batches = PurchaseOrder::incomingBatchesForProduct($product->id)->values();

    expect($batches)->toHaveCount(2);
    expect($batches[0]->expected_delivery_date)->toStartWith('2026-10-01');
    expect($batches[0]->quantity)->toBe(5);
    expect($batches[1]->expected_delivery_date)->toStartWith('2026-11-01');
    expect($batches[1]->quantity)->toBe(2);
});

it('excludes concept, processed, and cancelled purchase orders', function () {
    [, $warehouse, $product] = createIncomingStockContext();

    addPurchaseOrderBatch($warehouse, $product, 3, '2026-10-15', PurchaseOrderStatus::Concept);
    addPurchaseOrderBatch($warehouse, $product, 4, '2026-10-16', PurchaseOrderStatus::Processed);
    addPurchaseOrderBatch($warehouse, $product, 5, '2026-10-17', PurchaseOrderStatus::Cancelled);

    expect(PurchaseOrder::incomingBatchesForProduct($product->id))->toBeEmpty();
});

it('renders the incoming stock batch on the product stock page', function () {
    [$user, $warehouse, $product] = createIncomingStockContext();

    addPurchaseOrderBatch($warehouse, $product, 6, '2026-10-15', PurchaseOrderStatus::Purchased);

    $this->actingAs($user);

    Livewire::test(EditProductStock::class, ['record' => $product->getKey()])
        ->assertSee('6 units expected on');
});

it('omits processed and cancelled purchase orders from the rendered incoming stock', function () {
    [$user, $warehouse, $product] = createIncomingStockContext();

    addPurchaseOrderBatch($warehouse, $product, 6, '2026-10-15', PurchaseOrderStatus::Processed);

    $this->actingAs($user);

    Livewire::test(EditProductStock::class, ['record' => $product->getKey()])
        ->assertSee('No incoming stock expected.');
});
