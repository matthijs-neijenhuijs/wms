<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use App\Services\OrderStatusTransitionService;
use App\Services\PurchaseOrderProcessingService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Orders\Exceptions\PurchaseOrderStatusLockedException;
use Modules\Orders\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Modules\Orders\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderProduct;
use Modules\Orders\Models\PurchaseOrderStatus;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

function createStatusTransitionContext(): array
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "po-status-transitions-{$counter}",
        'name' => "PO Status Transitions {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "PST{$counter} Warehouse",
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

    return [$user, $warehouse];
}

it('defaults to concept status on creation', function () {
    [, $warehouse] = createStatusTransitionContext();

    $purchaseOrder = PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
    ]);

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Concept);
});

it('moves from concept to purchased and sets the expected delivery date', function () {
    [, $warehouse] = createStatusTransitionContext();

    $purchaseOrder = PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
    ])->fresh();

    $date = now()->addWeek()->startOfDay();

    app(PurchaseOrderProcessingService::class)->markPurchased($purchaseOrder, $date);
    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Purchased);
    expect($purchaseOrder->expected_delivery_date->isSameDay($date))->toBeTrue();
});

it('can be cancelled from concept', function () {
    [, $warehouse] = createStatusTransitionContext();

    $purchaseOrder = PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
    ])->fresh();

    app(PurchaseOrderProcessingService::class)->cancel($purchaseOrder);

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled);
});

it('can be cancelled from purchased', function () {
    [, $warehouse] = createStatusTransitionContext();

    $purchaseOrder = PurchaseOrder::query()->create(['warehouse_id' => $warehouse->id])->fresh();
    app(PurchaseOrderProcessingService::class)->markPurchased($purchaseOrder, now()->addWeek());

    app(PurchaseOrderProcessingService::class)->cancel($purchaseOrder->fresh());

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled);
});

it('cannot be cancelled once received', function () {
    [$user, $warehouse] = createStatusTransitionContext();

    $purchaseOrder = PurchaseOrder::query()->create(['warehouse_id' => $warehouse->id])->fresh();
    app(PurchaseOrderProcessingService::class)->markPurchased($purchaseOrder, now()->addWeek());
    app(PurchaseOrderProcessingService::class)->markReceived($purchaseOrder->fresh());

    expect(fn () => app(PurchaseOrderProcessingService::class)->cancel($purchaseOrder->fresh()))
        ->toThrow(PurchaseOrderStatusLockedException::class);

    $this->actingAs($user);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->assertActionHidden('cancel');
});

it('cannot be marked processed before scanned', function () {
    [$user, $warehouse] = createStatusTransitionContext();

    $purchaseOrder = PurchaseOrder::query()->create(['warehouse_id' => $warehouse->id])->fresh();

    expect(fn () => app(PurchaseOrderProcessingService::class)->markProcessed($purchaseOrder))
        ->toThrow(PurchaseOrderStatusLockedException::class);

    $this->actingAs($user);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->assertActionHidden('markProcessed');

    app(PurchaseOrderProcessingService::class)->markPurchased($purchaseOrder, now()->addWeek());

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->assertActionHidden('markProcessed');

    app(PurchaseOrderProcessingService::class)->markReceived($purchaseOrder->fresh());

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->assertActionHidden('markProcessed');
});

it('throws when an invalid status transition is attempted directly', function () {
    [, $warehouse] = createStatusTransitionContext();

    $purchaseOrder = PurchaseOrder::query()->create(['warehouse_id' => $warehouse->id])->fresh();

    expect(fn () => $purchaseOrder->update(['status' => PurchaseOrderStatus::Scanned]))
        ->toThrow(PurchaseOrderStatusLockedException::class);
});

it('refreshes deferred reservations for its products when the expected delivery date is edited', function () {
    [$user, $warehouse] = createStatusTransitionContext();

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
        'on_stock_quantity' => 0,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => 0,
    ]);

    $orderStatus = OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Reserved',
        'color' => '#22c55e',
        'reserve_stock' => true,
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'delivery_date' => '2026-11-01',
    ]);

    OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 10,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    $order->update(['order_statuses_id' => $orderStatus->id]);

    $purchaseOrder = PurchaseOrder::query()->create(['warehouse_id' => $warehouse->id])->fresh();
    app(PurchaseOrderProcessingService::class)->markPurchased($purchaseOrder, Carbon::parse('2026-12-01'));

    for ($i = 0; $i < 10; $i++) {
        PurchaseOrderProduct::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'barcode' => $product->barcode,
            'reference_code' => $product->reference_code,
            'product_title' => $product->name,
            'scanned' => false,
        ]);
    }

    app(OrderStatusTransitionService::class)->refreshReservedStockForProductIds([$product->id]);

    // Expected delivery date (2026-12-01) is after the order's delivery date
    // (2026-11-01), so it does not qualify yet: demand is fully reserved.
    expect($stockProduct->fresh()->reserved_quantity)->toBe(10);

    $this->actingAs($user);

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->fillForm(['expected_delivery_date' => '2026-10-01'])
        ->call('save');

    // Now the purchase order qualifies (its expected delivery date is before
    // the order's delivery date), so it should fully defer the reservation.
    expect($stockProduct->fresh()->reserved_quantity)->toBe(0);
});
