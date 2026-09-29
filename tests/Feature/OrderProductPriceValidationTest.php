<?php

declare(strict_types=1);

use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Clients\Models\Client;
use Modules\Orders\Filament\Resources\Orders\Pages\EditOrder;
use Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProductRelationManager;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Products\Models\Product;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

it('accepts a decimal price when adding an order product', function () {
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'order-product-price',
        'name' => 'Order Product Price',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Price Warehouse',
        'currency' => 'EUR',
    ]);

    $client = Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'email' => 'client-price@example.com',
    ]);

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'REF-PRICE-1',
        'product_code' => 'PROD-PRICE-1',
        'barcode' => '5000000001',
        'name' => 'Priced Product',
        'description' => 'Priced product description',
        'price' => 19.99,
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
        'order_statuses_id' => $status->id,
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Warehouse Manager',
        'email' => 'manager-price@example.com',
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    Livewire::test(OrderProductRelationManager::class, [
        'ownerRecord' => $order,
        'pageClass' => EditOrder::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: [
            'product_id' => $product->id,
            'quantity' => 2,
            'name' => $product->name,
            'reference_code' => $product->reference_code,
            'barcode' => $product->barcode,
            'price' => $product->price,
        ])
        ->assertHasNoFormErrors();

    expect(OrderProduct::query()->where('order_id', $order->id)->where('product_id', $product->id)->exists())->toBeTrue();
});
