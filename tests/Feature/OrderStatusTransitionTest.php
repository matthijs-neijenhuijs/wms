<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Picklists\Filament\Resources\Picklists\PicklistResource;
use Modules\Picklists\Models\Picklist;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;

uses(RefreshDatabase::class);

function createOrderWorkflowContext(): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'status-workflow',
        'name' => 'Status Workflow',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Main Warehouse',
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => 'client@example.com',
    ]);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'REF-001',
        'product_code' => 'PROD-001',
        'barcode' => '1234567890123',
        'name' => 'Demo Product',
        'description' => 'Demo description',
    ]);

    $stockProduct = StockProduct::query()->create([
        'product_id' => $product->id,
        'on_stock_quantity' => 10,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => 10,
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
        'email' => $client->email,
    ]);

    OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'quantity' => 3,
        'barcode' => $product->barcode,
        'reference_code' => $product->reference_code,
        'price' => 9.99,
    ]);

    return [$order, $stockProduct];
}

it('generates a picklist, reserves stock, and syncs order flags when the status changes', function () {
    [$order, $stockProduct] = createOrderWorkflowContext();

    $status = OrderStatus::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Ready to Pick',
        'color' => '#22c55e',
        'generate_picklist' => true,
        'reserve_stock' => true,
        'completed' => true,
    ]);

    $order->update([
        'order_statuses_id' => $status->id,
    ]);

    $order->refresh();
    $stockProduct->refresh();

    expect(Picklist::query()->where('order_id', $order->id)->exists())->toBeTrue();
    expect($stockProduct->reserved_quantity)->toBe(3);
    expect($stockProduct->free_on_stock_quantity)->toBe(7);
    expect($order->completed)->toBeTrue();
    expect($order->cancelled)->toBeFalse();
    expect($order->on_hold)->toBeFalse();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'order_workflow',
        'event' => 'status_changed',
        'description' => 'Order status changed to Ready to Pick',
        'subject_type' => $order->getMorphClass(),
        'subject_id' => $order->id,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'order_workflow',
        'event' => 'picklist_generated',
        'description' => 'Picklist generated for order',
        'subject_type' => $order->getMorphClass(),
        'subject_id' => $order->id,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'order_workflow',
        'event' => 'stock_reserved',
        'description' => 'Stock reserved for order',
        'subject_type' => $order->getMorphClass(),
        'subject_id' => $order->id,
    ]);
});

it('reduces stock and marks the order as picked when the status requires stock reduction', function () {
    [$order, $stockProduct] = createOrderWorkflowContext();

    $status = OrderStatus::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Picked',
        'color' => '#3b82f6',
        'reduce_stock' => true,
    ]);

    $order->update([
        'order_statuses_id' => $status->id,
    ]);

    $order->refresh();
    $stockProduct->refresh();

    expect($stockProduct->on_stock_quantity)->toBe(7);
    expect($stockProduct->free_on_stock_quantity)->toBe(7);
    expect($order->picked)->toBeTrue();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'order_workflow',
        'event' => 'stock_reduced',
        'description' => 'Stock reduced for order',
        'subject_type' => $order->getMorphClass(),
        'subject_id' => $order->id,
    ]);
});

it('releases reserved stock when the order is moved to a cancelled status', function () {
    [$order, $stockProduct] = createOrderWorkflowContext();

    $reserveStatus = OrderStatus::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Reserved',
        'color' => '#f59e0b',
        'reserve_stock' => true,
    ]);

    $cancelledStatus = OrderStatus::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Cancelled',
        'color' => '#ef4444',
        'cancelled' => true,
    ]);

    $order->update([
        'order_statuses_id' => $reserveStatus->id,
    ]);

    $stockProduct->refresh();
    expect($stockProduct->reserved_quantity)->toBe(3);

    $order->update([
        'order_statuses_id' => $cancelledStatus->id,
    ]);

    $order->refresh();
    $stockProduct->refresh();

    expect($stockProduct->reserved_quantity)->toBe(0);
    expect($stockProduct->free_on_stock_quantity)->toBe(10);
    expect($order->cancelled)->toBeTrue();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'order_workflow',
        'event' => 'stock_released',
        'description' => 'Reserved stock released for cancelled order',
        'subject_type' => $order->getMorphClass(),
        'subject_id' => $order->id,
    ]);
});

it('does not allow manual picklist creation from the resource', function () {
    expect(PicklistResource::canCreate())->toBeFalse();
    expect(PicklistResource::getPages())->not->toHaveKey('create');
});
