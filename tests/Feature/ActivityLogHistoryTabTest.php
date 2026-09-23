<?php

declare(strict_types=1);

use App\Models\ApiKey;
use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Brands\Filament\Resources\Brands\Pages\ManageBrandActivities;
use Modules\Brands\Models\Brand;
use Modules\Clients\Filament\Resources\Clients\Pages\ManageClientActivities;
use Modules\Clients\Models\Client;
use Modules\Orders\Filament\Resources\Orders\Pages\ManageOrderActivities;
use Modules\Orders\Filament\Resources\OrderStatuses\Pages\ManageOrderStatusActivities;
use Modules\Orders\Filament\Resources\PurchaseOrders\Pages\ManagePurchaseOrderActivities;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderStatus;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Picklists\Filament\Resources\Picklists\Pages\ManagePicklistActivities;
use Modules\Picklists\Models\Picklist;
use Modules\Products\Filament\Resources\Products\Pages\ManageProductActivities;
use Modules\Products\Filament\Resources\Products\Pages\ManageStockActivities;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Settings\Filament\Resources\ApiKeys\Pages\ManageApiKeyActivities;
use Modules\Settings\Filament\Resources\AttributeGroups\Pages\ManageAttributeGroupActivities;
use Modules\Settings\Filament\Resources\VatRates\Pages\ManageVatRateActivities;
use Modules\Settings\Filament\Resources\Warehouses\Pages\ManageWarehouseActivities;
use Modules\Settings\Models\AttributeGroup;
use Modules\Settings\Models\VatRate;
use Modules\Users\Filament\Resources\Users\Pages\ManageUserActivities;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Warehouse}
 */
function createActivityLogHistoryContext(): array
{
    $subdomain = Subdomain::query()->create([
        'subdomain' => 'activity-history',
        'name' => 'Activity History',
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'Main Warehouse',
        'currency' => 'EUR',
    ]);

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => 'History Viewer',
        'email' => 'history@example.com',
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setTenant($warehouse);

    return [$user, $warehouse];
}

it('renders the History tab without error for every audited resource', function (string $pageClass, Closure $makeRecord) {
    [$user, $warehouse] = createActivityLogHistoryContext();

    $record = $makeRecord($warehouse, $user);

    Livewire::test($pageClass, ['record' => $record->getKey()])
        ->assertOk();
})->with([
    'Order' => [ManageOrderActivities::class, fn (Warehouse $warehouse) => Order::query()->create([
        'warehouse_id' => $warehouse->id,
    ])],
    'Product' => [ManageProductActivities::class, fn (Warehouse $warehouse) => Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'REF-001',
        'product_code' => 'PROD-001',
        'barcode' => '1111111111111',
        'name' => 'Demo Product',
        'description' => 'Demo description',
    ])],
    'Client' => [ManageClientActivities::class, fn (Warehouse $warehouse) => Client::query()->create([
        'warehouse_id' => $warehouse->id,
        'email' => 'client@example.com',
    ])],
    'PurchaseOrder' => [ManagePurchaseOrderActivities::class, fn (Warehouse $warehouse) => PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'expected_delivery_date' => now()->addDay()->toDateString(),
    ])],
    'Warehouse' => [ManageWarehouseActivities::class, fn (Warehouse $warehouse) => $warehouse],
    'ApiKey' => [ManageApiKeyActivities::class, fn (Warehouse $warehouse) => ApiKey::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Storefront Key',
        'key_hash' => hash('sha256', 'secret-token'),
    ])],
    'VatRate' => [ManageVatRateActivities::class, fn (Warehouse $warehouse) => VatRate::query()->forceCreate([
        'warehouse_id' => $warehouse->id,
        'name' => 'Standard',
        'rate' => 21,
    ])],
    'AttributeGroup' => [ManageAttributeGroupActivities::class, fn (Warehouse $warehouse) => AttributeGroup::query()->forceCreate([
        'warehouse_id' => $warehouse->id,
        'name' => 'Size',
    ])],
    'User' => [ManageUserActivities::class, fn (Warehouse $warehouse, User $user) => $user],
    'Brand' => [ManageBrandActivities::class, fn (Warehouse $warehouse) => Brand::query()->forceCreate([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'BRAND-001',
        'name' => 'Acme',
        'description' => 'Demo brand',
    ])],
    'Picklist' => [ManagePicklistActivities::class, fn (Warehouse $warehouse) => Picklist::query()->create([
        'warehouse_id' => $warehouse->id,
        'order_id' => Order::query()->create(['warehouse_id' => $warehouse->id])->id,
    ])],
    'OrderStatus' => [ManageOrderStatusActivities::class, fn (Warehouse $warehouse) => OrderStatus::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Concept',
        'color' => '#ffffff',
    ])],
    'StockProduct (via Product Stock History tab)' => [ManageStockActivities::class, function (Warehouse $warehouse) {
        $product = Product::query()->create([
            'warehouse_id' => $warehouse->id,
            'active' => true,
            'reference_code' => 'REF-STOCK-001',
            'product_code' => 'PROD-STOCK-001',
            'barcode' => '2222222222222',
            'name' => 'Stock Demo Product',
            'description' => 'Demo description',
        ]);

        StockProduct::query()->create([
            'product_id' => $product->id,
            'on_stock_quantity' => 5,
            'reserved_quantity' => 0,
            'reserved_on_picklists' => 0,
            'free_on_stock_quantity' => 5,
        ]);

        return $product;
    }],
]);

it('surfaces StockProduct activity on the Stock History tab, separately from Product activity', function () {
    [, $warehouse] = createActivityLogHistoryContext();

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'reference_code' => 'REF-STOCK-002',
        'product_code' => 'PROD-STOCK-002',
        'barcode' => '3333333333333',
        'name' => 'Stock Demo Product Two',
        'description' => 'Demo description',
    ]);

    $stockProduct = StockProduct::query()->create([
        'product_id' => $product->id,
        'on_stock_quantity' => 5,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => 5,
    ]);

    $stockProduct->update(['on_stock_quantity' => 10, 'free_on_stock_quantity' => 10]);

    $stockActivities = $product->fresh()->stockActivities;

    expect($stockActivities)->toHaveCount(2) // created + updated
        ->and($stockActivities->pluck('subject_type')->unique()->all())->toBe([StockProduct::class])
        ->and($product->fresh()->activitiesAsSubject()->count())->toBe(1); // only Product's own "created" event

    Livewire::test(ManageStockActivities::class, ['record' => $product->getKey()])
        ->assertOk();
});

it('excludes the api key hash from logged attribute changes', function () {
    [, $warehouse] = createActivityLogHistoryContext();

    $apiKey = ApiKey::query()->create([
        'warehouse_id' => $warehouse->id,
        'name' => 'Storefront Key',
        'key_hash' => hash('sha256', 'secret-token'),
    ]);

    $apiKey->update(['key_hash' => hash('sha256', 'rotated-token'), 'name' => 'Rotated Key']);

    $activity = $apiKey->activitiesAsSubject()->latest('id')->first();

    expect($activity->attribute_changes->get('attributes', []))
        ->not->toHaveKey('key_hash')
        ->toHaveKey('name');
});

it('excludes the password from logged attribute changes', function () {
    [, $warehouse] = createActivityLogHistoryContext();

    $user = User::query()->create([
        'subdomain_id' => $warehouse->subdomain_id,
        'name' => 'Secret Agent',
        'email' => 'agent@example.com',
        'password' => 'password',
    ]);

    $user->update(['password' => 'new-password', 'name' => 'Secret Agent Updated']);

    $activity = $user->activitiesAsSubject()->latest('id')->first();

    expect($activity->attribute_changes->get('attributes', []))
        ->not->toHaveKey('password')
        ->toHaveKey('name');
});
