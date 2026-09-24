<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Modules\Clients\Filament\Resources\Clients\Widgets\ClientOrderStatsOverview;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

function createClientOrderStatsTestContext(): Warehouse
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "client-order-stats-{$counter}",
        'name' => "Client Order Stats {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "COS{$counter} Warehouse",
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Warehouse Manager',
        'email' => "manager-cos-{$counter}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    return $warehouse;
}

it('computes total order count and average order amount', function () {
    $warehouse = createClientOrderStatsTestContext();

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'email' => 'client@example.com',
    ]);

    $orderA = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
    ]);

    OrderProduct::query()->create([
        'order_id' => $orderA->id,
        'name' => 'Widget',
        'quantity' => 2,
        'price' => 10,
    ]);

    $orderB = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
    ]);

    OrderProduct::query()->create([
        'order_id' => $orderB->id,
        'name' => 'Gadget',
        'quantity' => 1,
        'price' => 40,
    ]);

    Livewire::test(ClientOrderStatsOverview::class, ['record' => $client])
        ->assertSee('2')
        ->assertSee(Number::currency(30, in: 'EUR'));
});

it('handles a client with zero orders without a division-by-zero error', function () {
    $warehouse = createClientOrderStatsTestContext();

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'email' => 'no-orders@example.com',
    ]);

    Livewire::test(ClientOrderStatsOverview::class, ['record' => $client])
        ->assertSuccessful()
        ->assertSee('0');
});
