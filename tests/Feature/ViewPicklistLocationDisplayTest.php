<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;
use Modules\Picklists\Filament\Resources\Picklists\Pages\ViewPicklist;
use Modules\Picklists\Models\Picklist;
use Modules\Picklists\Models\PicklistFailedProduct;
use Modules\Picklists\Models\PicklistProduct;
use Modules\Products\Models\StockLocation;
use Modules\Users\Models\User;

it('shows the assigned location name for a located pick and a placeholder for an unlocated one', function () {
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'view-picklist-location',
        'name' => 'View Picklist Location',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'View Picklist Location Warehouse',
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => 'client-view-picklist@example.com',
    ]);

    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
        'client_id' => $client->id,
        'email' => $client->email,
    ]);

    $location = StockLocation::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Aisle 3 Shelf B',
        'rank' => 1,
    ]);

    $picklist = Picklist::query()->create([
        'warehouse_id' => $warehouse->id,
        'order_id' => $order->id,
    ]);

    PicklistProduct::query()->create([
        'picklist_id' => $picklist->id,
        'stock_location_id' => $location->id,
        'barcode' => 'BC-LOCATED',
        'product_title' => 'Located Product',
    ]);

    PicklistProduct::query()->create([
        'picklist_id' => $picklist->id,
        'stock_location_id' => null,
        'barcode' => 'BC-UNLOCATED',
        'product_title' => 'Unlocated Product',
    ]);

    // Filament's default Entry state resolution treats an empty relation
    // Collection as "blank" and falls back to a null default state, which
    // breaks the unrelated FailedProductsTable infolist entry's @forelse
    // for any picklist with zero failed scans (a pre-existing bug, not
    // something this feature touches). Seed one row so this test can
    // exercise the actual page instead of tripping over it.
    PicklistFailedProduct::query()->create([
        'picklist_id' => $picklist->id,
        'barcode' => 'BC-FAILED',
        'total_quantity_scanned' => 1,
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Picklist Viewer',
        'email' => 'picklist-viewer@example.com',
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    Livewire::test(ViewPicklist::class, ['record' => $picklist->getKey()])
        ->assertOk()
        ->assertSee('Aisle 3 Shelf B')
        ->assertSee('—');
});
