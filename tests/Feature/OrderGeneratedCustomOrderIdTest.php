<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;

uses(RefreshDatabase::class);

it('generates unique custom order ids for warehouses sharing the same name prefix', function () {
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'shared-prefix',
        'name' => 'Shared Prefix',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouseOne = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'phae1',
        'currency' => 'EUR',
    ]);

    $warehouseTwo = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'phae2',
        'currency' => 'EUR',
    ]);

    $clientOne = Client::query()->create([
        'warehouse_id' => $warehouseOne->id,
        'active' => true,
        'email' => 'client-one@example.com',
    ]);

    $clientTwo = Client::query()->create([
        'warehouse_id' => $warehouseTwo->id,
        'active' => true,
        'email' => 'client-two@example.com',
    ]);

    $orderOne = Order::query()->create([
        'warehouse_id' => $warehouseOne->id,
        'client_id' => $clientOne->id,
        'email' => $clientOne->email,
    ]);

    $orderTwo = Order::query()->create([
        'warehouse_id' => $warehouseTwo->id,
        'client_id' => $clientTwo->id,
        'email' => $clientTwo->email,
    ]);

    expect($orderOne->generated_custom_order_id)
        ->not->toBe($orderTwo->generated_custom_order_id);
});
