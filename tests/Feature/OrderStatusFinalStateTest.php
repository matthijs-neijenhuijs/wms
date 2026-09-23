<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clients\Models\Client;
use Modules\Orders\Exceptions\OrderStatusLockedException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderStatus;

uses(RefreshDatabase::class);

function createFinalStateTestOrder(array $statusAttributes = []): Order
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "final-state-{$counter}",
        'name' => "Final State {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "FS{$counter} Warehouse",
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => "client-fs-{$counter}@example.com",
    ]);

    $status = OrderStatus::query()->create(array_merge([
        'warehouse_id' => $warehouse->id,
        'name' => 'Status '.$counter,
        'color' => '#22c55e',
    ], $statusAttributes));

    return Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
        'email' => $client->email,
        'order_statuses_id' => $status->id,
    ]);
}

it('treats completed as a final state', function () {
    $status = new OrderStatus(['completed' => true]);

    expect($status->isFinalState())->toBeTrue()
        ->and($status->canChangeStatus())->toBeFalse()
        ->and($status->canEditOrder())->toBeFalse();
});

it('does not treat a non-terminal status as final', function () {
    $status = new OrderStatus(['concepted' => true]);

    expect($status->isFinalState())->toBeFalse()
        ->and($status->canChangeStatus())->toBeTrue()
        ->and($status->canEditOrder())->toBeTrue();
});

it('throws and does not persist when changing status away from a delivered order', function () {
    $order = createFinalStateTestOrder(['delivered' => true]);
    $originalStatusId = $order->order_statuses_id;

    $newStatus = OrderStatus::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Cancelled',
        'color' => '#ef4444',
        'cancelled' => true,
    ]);

    expect(fn () => $order->update(['order_statuses_id' => $newStatus->id]))
        ->toThrow(OrderStatusLockedException::class);

    expect($order->fresh()->order_statuses_id)->toBe($originalStatusId);
});

it('throws and does not persist when changing status away from a cancelled order', function () {
    $order = createFinalStateTestOrder(['cancelled' => true]);
    $originalStatusId = $order->order_statuses_id;

    $newStatus = OrderStatus::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Concept',
        'color' => '#22c55e',
        'concepted' => true,
    ]);

    expect(fn () => $order->update(['order_statuses_id' => $newStatus->id]))
        ->toThrow(OrderStatusLockedException::class);

    expect($order->fresh()->order_statuses_id)->toBe($originalStatusId);
});

it('throws and does not persist when changing status away from a completed order', function () {
    $order = createFinalStateTestOrder(['completed' => true]);
    $originalStatusId = $order->order_statuses_id;

    $newStatus = OrderStatus::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Concept',
        'color' => '#22c55e',
        'concepted' => true,
    ]);

    expect(fn () => $order->update(['order_statuses_id' => $newStatus->id]))
        ->toThrow(OrderStatusLockedException::class);

    expect($order->fresh()->order_statuses_id)->toBe($originalStatusId);
});

it('allows changing status away from a non-terminal status', function () {
    $order = createFinalStateTestOrder(['concepted' => true]);

    $newStatus = OrderStatus::query()->create([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Confirmed',
        'color' => '#3b82f6',
    ]);

    $order->update(['order_statuses_id' => $newStatus->id]);

    expect($order->fresh()->order_statuses_id)->toBe($newStatus->id);
});

it('allows assigning a status to a freshly created order with no prior status', function () {
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'final-state-fresh',
        'name' => 'Final State Fresh',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Fresh Warehouse',
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => 'fresh-client@example.com',
    ]);

    $status = OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Concept',
        'color' => '#22c55e',
        'concepted' => true,
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
        'email' => $client->email,
    ]);

    $order->update(['order_statuses_id' => $status->id]);

    expect($order->fresh()->order_statuses_id)->toBe($status->id);
});
