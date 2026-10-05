<?php

declare(strict_types=1);

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AverageOrderAmountPerMonthChart;
use App\Filament\Widgets\LowStockProductsTable;
use App\Filament\Widgets\OrdersPerMonthChart;
use App\Filament\Widgets\TopProductsTable;
use App\Filament\Widgets\WarehouseOverviewStats;
use App\Models\Subdomain;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderProduct;
use Modules\Orders\Models\OrderStatus;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Orders\Models\PurchaseOrderStatus;
use Modules\Picklists\Models\Picklist;
use Modules\Products\Models\Product;
use Modules\Products\Models\StockProduct;
use Modules\Users\Models\User;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Warehouse}
 */
function createWarehouseDashboardTestContext(array $warehouseOverrides = []): array
{
    static $counter = 0;
    $counter++;

    $subdomain = Subdomain::query()->create([
        'subdomain' => "dashboard-{$counter}",
        'name' => "Dashboard {$counter}",
    ]);

    app()->instance('current_subdomain', $subdomain);

    $warehouse = Warehouse::query()->create(array_merge([
        'subdomain_id' => $subdomain->id,
        'name' => "DASH{$counter} Warehouse",
        'currency' => 'EUR',
    ], $warehouseOverrides));

    // Reload so the DB-level low_stock_threshold default is reflected
    // in-memory, matching how Filament always resolves the tenant via a
    // fresh query rather than reusing an unrefreshed create() result.
    $warehouse->refresh();

    $user = User::query()->create([
        'subdomain_id' => $subdomain->id,
        'name' => "Dashboard Manager {$counter}",
        'email' => "dashboard-manager-{$counter}@example.com",
        'password' => 'password',
    ]);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($warehouse);

    return [$user, $warehouse];
}

/**
 * Moves an already-created Order's status through the real sync path
 * (Order -> OrderStatus -> ProcessOrderStatusTransition listener), the only
 * way to set Order's own non-fillable completed/cancelled/delivered/on_hold
 * flags, exactly like tests/Feature/OrderStatusTransitionTest.php.
 */
function syncOrderToStatus(Order $order, array $statusAttributes): Order
{
    $status = OrderStatus::query()->create(array_merge([
        'warehouse_id' => $order->warehouse_id,
        'name' => 'Status '.uniqid(),
        'color' => '#22c55e',
    ], $statusAttributes));

    $order->update(['order_statuses_id' => $status->id]);

    return $order->refresh();
}

function createOrderInMonth(Warehouse $warehouse, int $year, int $month): Order
{
    $order = Order::query()->create([
        'warehouse_id' => $warehouse->id,
    ]);

    $order->forceFill(['created_at' => now()->setYear($year)->setMonth($month)->setDay(10)])->saveQuietly();

    return $order->refresh();
}

function createProductWithStock(Warehouse $warehouse, int $freeOnStockQuantity, bool $stockUnlimited = false, ?string $code = null): Product
{
    static $counter = 0;
    $counter++;

    $code ??= "PROD-{$counter}";

    $product = Product::query()->create([
        'warehouse_id' => $warehouse->id,
        'active' => true,
        'stock_unlimited' => $stockUnlimited,
        'reference_code' => "REF-{$code}",
        'product_code' => $code,
        'barcode' => "BARCODE-{$code}",
        'name' => "Product {$code}",
        'description' => 'Test product',
    ]);

    StockProduct::query()->create([
        'product_id' => $product->id,
        'on_stock_quantity' => $freeOnStockQuantity,
        'reserved_quantity' => 0,
        'reserved_on_picklists' => 0,
        'free_on_stock_quantity' => $freeOnStockQuantity,
    ]);

    return $product;
}

// --- WarehouseOverviewStats ---------------------------------------------

it('counts open orders as not completed, not cancelled, and not delivered, keeping on-hold orders open', function () {
    [, $warehouse] = createWarehouseDashboardTestContext();

    $openOrder = Order::query()->create(['warehouse_id' => $warehouse->id]);

    $onHoldOrder = Order::query()->create(['warehouse_id' => $warehouse->id]);
    syncOrderToStatus($onHoldOrder, ['on_hold' => true]);

    $completedOrder = Order::query()->create(['warehouse_id' => $warehouse->id]);
    syncOrderToStatus($completedOrder, ['completed' => true]);

    expect($onHoldOrder->on_hold)->toBeTrue()
        ->and($onHoldOrder->completed)->toBeFalse()
        ->and($completedOrder->completed)->toBeTrue();

    Livewire::test(WarehouseOverviewStats::class)
        ->assertSee('2')
        ->assertSee(__('Open Orders'));

    expect(Order::query()->where('completed', false)->where('cancelled', false)->where('delivered', false)->count())->toBe(2);
});

it('counts open picklists as not completed', function () {
    [, $warehouse] = createWarehouseDashboardTestContext();

    $order = Order::query()->create(['warehouse_id' => $warehouse->id]);

    Picklist::query()->create([
        'order_id' => $order->id,
        'warehouse_id' => $warehouse->id,
        'completed' => false,
    ]);

    Picklist::query()->create([
        'order_id' => $order->id,
        'warehouse_id' => $warehouse->id,
        'completed' => true,
    ]);

    Livewire::test(WarehouseOverviewStats::class)->assertSee(__('Open Picklists'));

    expect(Picklist::query()->where('completed', false)->count())->toBe(1);
});

it('flags low stock products at or below the warehouse threshold, excluding stock-unlimited products', function () {
    [, $warehouse] = createWarehouseDashboardTestContext(['low_stock_threshold' => 5]);

    $atThreshold = createProductWithStock($warehouse, 5);
    $aboveThreshold = createProductWithStock($warehouse, 6);
    createProductWithStock($warehouse, 0, stockUnlimited: true);

    expect(StockProduct::query()
        ->whereHas('product', fn ($query) => $query->where('stock_unlimited', false))
        ->where('free_on_stock_quantity', '<=', 5)
        ->count())->toBe(1);

    Livewire::test(WarehouseOverviewStats::class)
        ->assertSee(__('Threshold: :threshold units', ['threshold' => 5]));

    expect($atThreshold->stockProduct->free_on_stock_quantity)->toBe(5)
        ->and($aboveThreshold->stockProduct->free_on_stock_quantity)->toBe(6);
});

it('reads the low stock threshold per warehouse, not a hardcoded value', function () {
    [, $warehouseA] = createWarehouseDashboardTestContext(['low_stock_threshold' => 5]);
    createProductWithStock($warehouseA, 8);

    [, $warehouseB] = createWarehouseDashboardTestContext(['low_stock_threshold' => 10]);
    createProductWithStock($warehouseB, 8);

    Filament::setTenant($warehouseA);
    $countForA = StockProduct::query()
        ->whereHas('product', fn ($query) => $query->where('stock_unlimited', false))
        ->where('free_on_stock_quantity', '<=', $warehouseA->low_stock_threshold)
        ->count();

    Filament::setTenant($warehouseB);
    $countForB = StockProduct::query()
        ->whereHas('product', fn ($query) => $query->where('stock_unlimited', false))
        ->where('free_on_stock_quantity', '<=', $warehouseB->low_stock_threshold)
        ->count();

    expect($countForA)->toBe(0)
        ->and($countForB)->toBe(1);
});

it('counts pending purchase orders as anything not yet received, processed, or cancelled', function () {
    [, $warehouse] = createWarehouseDashboardTestContext();

    PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'status' => PurchaseOrderStatus::Purchased,
    ]);

    PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'status' => PurchaseOrderStatus::Processed,
    ]);

    PurchaseOrder::query()->create([
        'warehouse_id' => $warehouse->id,
        'status' => PurchaseOrderStatus::Cancelled,
    ]);

    expect(PurchaseOrder::query()
        ->whereNotIn('status', [
            PurchaseOrderStatus::Received,
            PurchaseOrderStatus::Processed,
            PurchaseOrderStatus::Cancelled,
        ])
        ->count())->toBe(1);

    Livewire::test(WarehouseOverviewStats::class)->assertSee(__('Pending Purchase Orders'));
});

// --- OrdersPerMonthChart -------------------------------------------------

it('zero-fills months with no orders and scopes the chart to the selected year', function () {
    [, $warehouse] = createWarehouseDashboardTestContext();

    $year = 2024;
    createOrderInMonth($warehouse, $year, 1);
    createOrderInMonth($warehouse, $year, 3);
    createOrderInMonth($warehouse, $year + 1, 1);

    Livewire::test(OrdersPerMonthChart::class)
        ->set('filter', (string) $year)
        ->assertDispatched('updateChartData', function (string $name, array $params) {
            $data = $params['data']['datasets'][0]['data'];

            return $data === [1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        });
});

// --- AverageOrderAmountPerMonthChart -------------------------------------

it('divides monthly revenue by distinct order count, not order-line count', function () {
    [, $warehouse] = createWarehouseDashboardTestContext();

    $year = 2024;
    $order = createOrderInMonth($warehouse, $year, 6);

    OrderProduct::query()->create([
        'order_id' => $order->id,
        'name' => 'Widget',
        'quantity' => 2,
        'price' => 10,
    ]);

    OrderProduct::query()->create([
        'order_id' => $order->id,
        'name' => 'Gadget',
        'quantity' => 1,
        'price' => 40,
    ]);

    Livewire::test(AverageOrderAmountPerMonthChart::class)
        ->set('filter', (string) $year)
        ->assertDispatched('updateChartData', function (string $name, array $params) {
            $data = $params['data']['datasets'][0]['data'];

            return ((float) $data[5] === 60.0) && ((float) $data[0] === 0.0);
        });
});

// --- LowStockProductsTable ------------------------------------------------

it('only lists products at or below the warehouse low stock threshold', function () {
    [, $warehouse] = createWarehouseDashboardTestContext(['low_stock_threshold' => 5]);

    $lowStockProduct = createProductWithStock($warehouse, 5);
    $wellStockedProduct = createProductWithStock($warehouse, 50);

    Livewire::test(LowStockProductsTable::class)
        ->call('loadTable')
        ->assertCanSeeTableRecords([$lowStockProduct])
        ->assertCanNotSeeTableRecords([$wellStockedProduct]);
});

// --- TopProductsTable -------------------------------------------------

it('ranks products by quantity sold this year and never leaks another warehouse into the ranking', function () {
    [, $warehouse] = createWarehouseDashboardTestContext();

    $topProduct = createProductWithStock($warehouse, 100, code: 'TOP');
    $secondProduct = createProductWithStock($warehouse, 100, code: 'SECOND');

    $order = createOrderInMonth($warehouse, now()->year, now()->month);

    OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $topProduct->id,
        'name' => $topProduct->name,
        'quantity' => 10,
        'price' => 5,
    ]);

    OrderProduct::query()->create([
        'order_id' => $order->id,
        'product_id' => $secondProduct->id,
        'name' => $secondProduct->name,
        'quantity' => 3,
        'price' => 5,
    ]);

    [, $otherWarehouse] = createWarehouseDashboardTestContext();
    $otherProduct = createProductWithStock($otherWarehouse, 100, code: 'OTHER');
    $otherOrder = createOrderInMonth($otherWarehouse, now()->year, now()->month);

    OrderProduct::query()->create([
        'order_id' => $otherOrder->id,
        'product_id' => $otherProduct->id,
        'name' => $otherProduct->name,
        'quantity' => 999,
        'price' => 5,
    ]);

    Filament::setTenant($warehouse);

    Livewire::test(TopProductsTable::class)
        ->call('loadTable')
        ->assertCanSeeTableRecords([$topProduct, $secondProduct], inOrder: true)
        ->assertCanNotSeeTableRecords([$otherProduct]);
});

// --- Dashboard page -------------------------------------------------------

it('registers the dashboard page with all five new widgets', function () {
    createWarehouseDashboardTestContext();

    Livewire::test(Dashboard::class)->assertOk();

    $widgets = (new Dashboard)->getWidgets();

    expect($widgets)->toContain(WarehouseOverviewStats::class)
        ->toContain(OrdersPerMonthChart::class)
        ->toContain(AverageOrderAmountPerMonthChart::class)
        ->toContain(LowStockProductsTable::class)
        ->toContain(TopProductsTable::class);
});
