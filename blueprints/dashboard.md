# Dashboard Module Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

The tenant panel's root page (`/{warehouse}/`) should give a warehouse
manager at-a-glance operational visibility — order backlog, picking backlog,
monthly sales trend, stock health, inbound replenishment status, and
best-selling products — instead of forcing them to browse individual resource
lists. This is new work: no dashboard widgets exist today.

Covers `App\Filament\Pages\Dashboard`, five new widgets under
`App\Filament\Widgets`, and one new field on `App\Models\Warehouse`. Not tied
to a single `Modules\*` app-module — it reads across Orders, Picklists,
Products, and Purchase Orders (see [`orders.md`](orders.md),
[`picklists.md`](picklists.md), [`products.md`](products.md)).

**Decisions locked in with the user before this blueprint was written**:
- "Open orders" = `completed = false AND cancelled = false AND delivered =
  false`. On-hold orders still count as open.
- Low-stock threshold is **per-warehouse configurable** (e.g. 5 for one
  warehouse, 10 for another) — no such field exists anywhere in the schema
  today, so `warehouses` gets a new `low_stock_threshold` column.
- The two monthly charts filter by year using each `ChartWidget`'s own
  built-in filter dropdown, not a shared dashboard-wide filter form.
- Extra widgets approved: pending inbound purchase orders (folded into the
  stats widget, not a separate table) and a top-products-by-quantity table.
  Explicitly **not** approved, do not add: an on-hold-orders stat, a
  backorder-count stat, or a stock-location-utilization widget (the schema
  has no capacity/volume field to compute real utilization from).

## Bug fixed — Dashboard page was never registered

`App\Filament\Pages\Dashboard` (extends `Filament\Pages\Dashboard`, strips
`AccountWidget`/`FilamentInfoWidget` from `getWidgets()`, sets a `['md' => 2,
'xl' => 3]` column layout) already existed on disk but was **never added** to
`AdminPanelProvider::panel()->pages([...])` — that array only listed
`WarehouseMap::class`. Filament's `Panel` starts `$pages` as an empty array
(no implicit default `Dashboard::class` is injected anywhere), so the tenant
root route had no page bound to it at all before this fix. Fixed by adding
`Dashboard::class` to that array (`app/Providers/Filament/AdminPanelProvider.php`),
alongside a `use App\Filament\Pages\Dashboard;` import. Because
`Dashboard::getWidgets()` returns `parent::getWidgets()` (= `Filament::getWidgets()`,
which already includes everything under `->discoverWidgets(in:
app_path('Filament/Widgets'), for: 'App\Filament\Widgets')`), every widget
below is picked up automatically with no further edit to `Dashboard.php`.

## New field — `Warehouse.low_stock_threshold`

**Model: `App\Models\Warehouse`** gains `low_stock_threshold` (integer,
`$fillable`, cast `'integer'` in `casts()` — stays the last method per
project convention). New migration
`app-modules/settings/database/migrations/{timestamp}_add_low_stock_threshold_to_warehouses_table.php`
(no `down()`, per project rule): `$table->unsignedInteger('low_stock_threshold')->default(5)->after('floor_height')`.
A DB-level default (not `nullable()`) is required because ~20 existing test
files create warehouses via bare `Warehouse::query()->create([...])` with no
`WarehouseFactory` to patch centrally — a default keeps every one of those
call sites working unchanged.

**Resource: `Modules\Settings\Filament\Resources\Warehouses\WarehouseResource`**
gets a new form field, following this module's one-class-per-field convention
(same shape as the existing `Inputs/FloorWidthInput.php`): new
`Inputs/LowStockThresholdInput.php` —
`TextInput::make('low_stock_threshold')->label('Low stock threshold')->numeric()->integer()->minValue(0)->default(5)->required()`
— added to `Schemas/WarehouseForm.php`'s `components()` array, after
`FloorHeightInput::make()`, before `CompletedPicklistStatusSelect::make()`.
No table-column change — this is an edit-page-only setting.

## Widgets

All five live in `App\Filament\Widgets` (Dashboard-only, no `$record`
binding). They rely on the tenant scoping already described in
[`wms.md`](wms.md) §1 (`WarehouseScope` via `BelongsToWarehouse`, keyed off
`Filament::getTenant()`) for `Order`, `Picklist`, `Product`, and
`PurchaseOrder` — no manual `where('warehouse_id', ...)` needed on those.
`StockProduct` has **no** `warehouse_id` column and does not use
`BelongsToWarehouse`; it's only reached through its `product()` relation (or
a join on `products`), which is itself tenant-scoped via `Product`'s own
global scope — sufficient on its own, confirmed by the existing
`whereHas('product', ...)` pattern used below.

**`WarehouseOverviewStats`** (`StatsOverviewWidget`,
`https://filamentphp.com/docs/4.x/widgets/stats-overview`) — four `Stat`s:
- *Open Orders* — `Order::query()->where('completed', false)->where('cancelled', false)->where('delivered', false)->count()`, icon `Heroicon::OutlinedShoppingCart`, color `primary`.
- *Open Picklists* — `Picklist::query()->where('completed', false)->count()`, icon `Heroicon::OutlinedClipboardDocumentList`, color `info`.
- *Low Stock Products* — `$tenant = Filament::getTenant(); $threshold = ($tenant instanceof Warehouse ? $tenant->low_stock_threshold : null) ?? 5;` then `StockProduct::query()->whereHas('product', fn ($q) => $q->where('stock_unlimited', false))->where('free_on_stock_quantity', '<=', $threshold)->count()`; color `success` when zero, else `warning`; icon `Heroicon::OutlinedExclamationTriangle`; description shows the threshold used. (`whereHas` builds its subquery via `Product::query()`, so `Product`'s `WarehouseScope` applies automatically — no extra filter needed. The `instanceof Warehouse` narrowing, rather than a bare `?->`, is required for Larastan level 8 to resolve the `low_stock_threshold` property; the trailing `?? 5` also covers an in-memory `Warehouse` whose attribute was never reloaded after a bare `create()`, which otherwise reads as `null` even though the DB column defaults to `5`.)
- *Pending Purchase Orders* — `PurchaseOrder::query()->whereNotIn('status', [PurchaseOrderStatus::Received, PurchaseOrderStatus::Processed, PurchaseOrderStatus::Cancelled])->count()`, icon `Heroicon::OutlinedTruck`, color `info`.

**`OrdersPerMonthChart`** (`ChartWidget`, bar,
`https://filamentphp.com/docs/4.x/widgets/charts`) — heading "Orders per
Month". `mount()` calls `parent::mount()` then `$this->filter ??= (string)
now()->year;` so the year dropdown defaults to the current year.

**Portability note (differs from the original plan)**: production runs MySQL
but the test suite runs SQLite (`phpunit.xml`), and SQLite has no
`MONTH()`/`YEAR()` SQL functions — a raw `selectRaw('MONTH(created_at)...')`
approach (as `ClientOrderStatsOverview` gets away with for plain
`SUM()`/`COUNT()`, which both drivers support) crashes under the test suite.
Month/year extraction is done in PHP instead: `getFilters()` fetches
`Order::query()->get(['created_at'])`, maps each to
`$order->created_at === null ? 0 : $order->created_at->year` (the explicit
null check, not `?->`, is required — Larastan infers `created_at` as
non-nullable `Carbon` from the cast and flags `?->` as dead code even though
an unpersisted/edge-case model could still have a null timestamp), then
`->filter()->unique()->sortDesc()`, falling back to `[now()->year]` if empty.
`getData()` resolves `$year = (int) ($this->filter ?? now()->year)`, fetches
`Order::query()->whereYear('created_at', $year)->get(['created_at'])`
(`whereYear()` is Eloquent's portable helper, safe on both drivers), groups
in PHP by the same null-checked `->month` closure, zero-fills all 12 months,
and returns one dataset labelled `__('Orders')` against `Jan..Dec` labels.

**`AverageOrderAmountPerMonthChart`** (`ChartWidget`, line,
`https://filamentphp.com/docs/4.x/widgets/charts`) — heading "Average Order
Amount per Month". Same `mount()`/`getFilters()` year-dropdown pattern as
`OrdersPerMonthChart` (duplicated directly — two call sites don't earn a
shared trait), same PHP-side month grouping for the same portability reason.
`getData()` fetches `Order::query()->whereYear('created_at', $year)->with('products')->get()`,
groups by month in PHP, and for each month with at least one order computes
revenue the same way `OrderResource`'s `TotalPriceColumn` does —
`$order->products->sum(fn ($p) => ($p->quantity ?? 0) * ($p->price ?? 0))` —
summed across that month's orders, divided by the distinct order count:
`round($totalRevenue / $orderCount, 2)`, or `0.0` for a month with no orders.
`order_products.price` is `decimal:4`, never cents — no `/100`. v1 shows
plain numeric amounts, no currency-symbol axis formatting (cosmetic polish,
easy to add later).

**`LowStockProductsTable`** (`TableWidget`,
`https://filamentphp.com/docs/4.x/widgets/overview#table-widgets`) — heading
"Low Stock Products". `table()` builds
`Product::query()->where('products.stock_unlimited', false)->join('stock_products', 'stock_products.product_id', '=', 'products.id')->where('stock_products.free_on_stock_quantity', '<=', $threshold)->orderBy('stock_products.free_on_stock_quantity')->select('products.*')->with('stockProduct')`
(an explicit `join()`, not `whereHas`, so the result can be ordered by the
related `free_on_stock_quantity` column directly — Filament's
`defaultSort()` can't reach a related table by dot notation). Columns:
`name` ("Product", searchable), `reference_code` ("Reference"),
`stockProduct.on_stock_quantity` ("On Stock"), `stockProduct.reserved_quantity`
("Reserved"), `stockProduct.free_on_stock_quantity` ("Free Stock", badge,
`danger` at ≤0 else `warning`). Paginated `[5, 10, 25]`.

**`TopProductsTable`** (`TableWidget`,
`https://filamentphp.com/docs/4.x/widgets/overview#table-widgets`) — heading
"Top Products This Year". Top 5 products by total quantity sold in the
current calendar year (`now()->year`) — a deliberate v1 simplification, no
extra filter control. `table()` builds
`Product::query()->select('products.*')->selectRaw('COALESCE(SUM(order_products.quantity), 0) as quantity_sold')->join('order_products', 'order_products.product_id', '=', 'products.id')->join('orders', 'orders.id', '=', 'order_products.order_id')->where('orders.warehouse_id', Filament::getTenant()?->getKey())->whereYear('orders.created_at', now()->year)->groupBy('products.id')->orderByDesc('quantity_sold')->limit(5)`.
**The explicit `where('orders.warehouse_id', ...)` is required**, unlike the
widgets above: `orders`/`order_products` are joined in as raw table names
here, not through an Eloquent relation, so `Order`'s own `WarehouseScope`
global scope does **not** apply to this join automatically — only
`Product`'s scope (on `products.warehouse_id`) does. Without this line, a
product could show sales pulled from another warehouse's orders if such
cross-warehouse rows ever existed. Columns: `name` ("Product"),
`reference_code` ("Reference"), `quantity_sold` ("Quantity Sold", numeric).
`paginated(false)` — the query itself is already `limit(5)`.

## Tests

New file `tests/Feature/WarehouseDashboardWidgetsTest.php`, reusing the exact
tenant-context setup already proven in
`tests/Feature/ClientOrderStatsOverviewTest.php` (create a `Subdomain`, bind
`app()->instance('current_subdomain', $subdomain)`, create a `Warehouse`,
create + `actingAs()` a `User`, then
`Filament::setCurrentPanel(Filament::getPanel('admin'))` +
`Filament::setTenant($warehouse)`).

- Open Orders counts an on-hold-but-not-completed/cancelled/delivered order
  as open, and excludes a completed one — proves the exact boolean
  combination, not just "any flag true excludes it".
- Open Picklists excludes `completed = true` rows.
- Low Stock Products: boundary case `free_on_stock_quantity == threshold` is
  included (≤, not <); a `stock_unlimited = true` product at 0 stock is
  excluded; two warehouses with different `low_stock_threshold` values (5 vs
  10) against identical stock levels produce different counts, proving the
  threshold is read per-warehouse, not hardcoded.
- Pending Purchase Orders counts `Purchased`/`Concept` but not
  `Received`/`Processed`/`Cancelled`.
- `OrdersPerMonthChart`: orders in Jan and Mar of year Y zero-fill the other
  10 months correctly; an order in year Y+1 is excluded when `filter` is
  still `Y`.
- `AverageOrderAmountPerMonthChart`: one order with two `order_products`
  (`quantity=2,price=10` and `quantity=1,price=40`, total 60) must show
  `60.0` for that month, not `30.0` (division by distinct order count, not
  product-line count) — same fixture shape as `ClientOrderStatsOverviewTest`;
  a month with zero orders yields `0.0`, not a division-by-zero error.
- `LowStockProductsTable`: `assertCanSeeTableRecords`/`assertCanNotSeeTableRecords`
  split at the threshold.
- `TopProductsTable`: a higher-quantity product from a **different**
  warehouse must not outrank the current tenant's own top product — the
  discriminating test for the explicit `orders.warehouse_id` filter; also
  assert ordering between a quantity-10 and a quantity-3 product.
- Dashboard page: now that `Dashboard::class` is registered in `->pages()`,
  visiting it renders successfully and its widget list includes all five new
  classes.

## Verification

1. `php artisan migrate` — new column present, default `5`, no error on
   existing rows.
2. `vendor/bin/pest tests/Feature/WarehouseDashboardWidgetsTest.php` — all
   scenarios above pass.
3. `compose analyse`, `compose lint`, `vendor/bin/pint --dirty --format agent`,
   `php artisan lang:extract` — mandatory project checks.
4. Manual check via Herd: visit the tenant dashboard URL, confirm all five
   widgets render, the two charts' year dropdowns default to the current
   year and switching years updates the data, and editing a warehouse's
   "Low stock threshold" changes which products appear in the Low Stock
   Products table after refresh.
5. Ask the user to run the full suite: `php artisan test --compact`.
