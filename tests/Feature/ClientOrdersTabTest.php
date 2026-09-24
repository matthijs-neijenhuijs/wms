<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Clients\Filament\Resources\Clients\ClientResource;
use Modules\Clients\Filament\Resources\Clients\Pages\ManageClientOrders;
use Modules\Clients\Models\Client;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

function createClientOrdersTabTestContext(): Warehouse
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "client-orders-tab-{$counter}",
        'name' => "Client Orders Tab {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "COT{$counter} Warehouse",
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Warehouse Manager',
        'email' => "manager-cot-{$counter}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    return $warehouse;
}

it('registers the orders tab on ClientResource', function () {
    expect(ClientResource::getPages())->toHaveKey('orders');
    expect(ManageClientOrders::getRelatedResource())->toBe(OrderResource::class);
});

it('lists only the client\'s own orders on the orders tab', function () {
    $warehouse = createClientOrdersTabTestContext();

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'email' => 'client@example.com',
    ]);

    $otherClient = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'email' => 'other@example.com',
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
    ]);

    $otherOrder = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $otherClient->id,
    ]);

    Livewire::test(ManageClientOrders::class, ['record' => $client->getKey()])
        ->loadTable()
        ->assertCanSeeTableRecords([$order])
        ->assertCanNotSeeTableRecords([$otherOrder]);
});
