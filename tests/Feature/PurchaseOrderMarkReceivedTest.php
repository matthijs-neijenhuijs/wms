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
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: PurchaseOrder}
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

    $purchaseOrder = PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'expected_delivery_date' => now()->addDay()->toDateString(),
    ]);

    return [$user, $purchaseOrder];
}

it('cannot be marked received before it has been processed', function () {
    [, $purchaseOrder] = createMarkReceivedContext();

    expect($purchaseOrder->canMarkReceived())->toBeFalse();
});

it('can be marked received once processed, and not again after completion', function () {
    [, $purchaseOrder] = createMarkReceivedContext();

    $purchaseOrder->update(['processed' => true]);
    expect($purchaseOrder->fresh()->canMarkReceived())->toBeTrue();

    $purchaseOrder->update(['completed' => true]);
    expect($purchaseOrder->fresh()->canMarkReceived())->toBeFalse();
});

it('marks the purchase order completed without touching stock', function () {
    [, $purchaseOrder] = createMarkReceivedContext();

    $purchaseOrder->update(['processed' => true]);

    app(PurchaseOrderProcessingService::class)->markReceived($purchaseOrder);

    expect($purchaseOrder->fresh()->completed)->toBeTrue();
});

it('hides the Mark Received action until the purchase order is processed', function () {
    [$user, $purchaseOrder] = createMarkReceivedContext();

    $this->actingAs($user);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->assertActionHidden('markReceived');

    $purchaseOrder->update(['processed' => true]);

    Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getKey()])
        ->assertActionVisible('markReceived');
});
