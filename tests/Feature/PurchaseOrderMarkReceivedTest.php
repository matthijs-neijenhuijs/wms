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
use Modules\Orders\Models\PurchaseOrderStatus;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: PurchaseOrder, 2: StockProduct}
 */
function createMarkReceivedContext(): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'po-mark-received',
        'name' => 'PO Mark Received',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Main Warehouse',
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Warehouse Manager',
        'email' => 'manager@example.com',
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
        'on_stock_quantity' => 3,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => 3,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'status' => PurchaseOrderStatus::Concept,
        'expected_delivery_date' => now()->addDay()->toDateString(),
    ]);

    return [$user, $purchaseOrder, $stockProduct];
}

it('cannot be marked received before it has been purchased', function () {
    [, $purchaseOrder] = createMarkReceivedContext();

    expect($purchaseOrder->canTransitionTo(PurchaseOrderStatus::Received))->toBeFalse();
});

it('can be marked received once purchased, and not again after', function () {
    [, $purchaseOrder] = createMarkReceivedContext();

    app(PurchaseOrderProcessingService::class)->markPurchased($purchaseOrder, now()->addWeek());
    expect($purchaseOrder->fresh()->canTransitionTo(PurchaseOrderStatus::Received))->toBeTrue();

    app(PurchaseOrderProcessingService::class)->markReceived($purchaseOrder->fresh());
    expect($purchaseOrder->fresh()->canTransitionTo(PurchaseOrderStatus::Received))->toBeFalse();
});

it('marking received does not touch stock or set received_date', function () {
    [, $purchaseOrder, $stockProduct] = createMarkReceivedContext();

    app(PurchaseOrderProcessingService::class)->markPurchased($purchaseOrder, now()->addWeek());
    app(PurchaseOrderProcessingService::class)->markReceived($purchaseOrder->fresh());

    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Received);
    expect($purchaseOrder->received_date)->toBeNull();
    expect($stockProduct->fresh()->on_stock_quantity)->toBe(3);
});

it('hides the Mark Received action until the purchase order is purchased', function () {
    [$user, $purchaseOrder] = createMarkReceivedContext();

    $this->actingAs($user);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->assertActionHidden('markReceived');

    app(PurchaseOrderProcessingService::class)->markPurchased($purchaseOrder, now()->addWeek());

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->assertActionVisible('markReceived');
});
