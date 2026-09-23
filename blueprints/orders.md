# Orders Module Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Orders is the fulfillment core of the WMS. A customer order (placed in the
admin panel or via the API) moves through configurable order statuses whose
flags decide whether stock gets reserved, a picklist is generated for
warehouse staff to pick the items, and stock is reduced once fulfilled.
Purchase Orders are the inbound counterpart — incoming stock from a supplier
that gets scanned in and added to on-hand inventory, and can defer an order's
stock reservation when it's expected to arrive in time to cover it.

Every order is placed against one of the client's stored addresses (see
[`clients.md`](clients.md)) — a client picks (or is defaulted to) a delivery
address and a bill address when the order is created. Critically, the order
does **not** keep a live link to that `ClientAddresses` row: it stores its own
copy of the address fields (`delivery_*`/`invoice_*` on `orders` itself), so
the order's shipping/billing details stay frozen as of the moment it was
placed even if the client's stored address is edited afterwards.

Covers the `Modules\Orders` module: Orders & Order Statuses (existing), the
Purchase Order feature (new work, implemented), and the E-commerce Order
Creation API (new work, planned).

## Orders & Order Statuses (existing — reference only)

**Model: `Modules\Orders\Models\OrderStatus`** — table `order_statuses` (`id`,
`warehouse_id` FK cascade, `name`, `color`, booleans `generate_picklist`,
`reserve_stock`, `reduce_stock`, `concepted`, `completed`, `on_hold`, `delivered`,
`cancelled`, all default false). This is the **configurable-status-with-boolean-flags
pattern** used throughout the app (not a fixed PHP enum — `OrderStatusEnum` exists
but is unreferenced dead code, flag only). Domain helper methods:
`canEditOrder()`, `canDeleteOrder()`, `canModifyProducts()`, `canChangeStatus()`,
`isLocked()`, `requiresConfirmation()`, `isFinalState()` — these are the de facto
authorization layer, consumed directly by `OrderResource`/`OrderProductRelationManager`.

**Resource: `Modules\Orders\Filament\Resources\OrderStatuses\OrderStatusResource`**
— no `$navigationGroup` property set, but `getNavigationGroup()` overridden to
return `__('Settings')` (translated — the one resource that needs this pattern
instead of the plain string). Form: `Grid(2)` of `NameInput`/`ColorInput`
(`Filament\Forms\Components\ColorPicker`), plus `Fieldset('Status Flags')
columns(3)` of 8 `Toggle`s (one per boolean). Table: `NameColumn` (searchable,
sortable), `StatusColorColumn` (`Filament\Tables\Columns\ColorColumn`), 8
`IconColumn::boolean()` columns (one per flag), `CreatedAtColumn`/`UpdatedAtColumn`
(toggleable, hidden by default). Full CRUD (index/create/edit).

**Model: `Modules\Orders\Models\Order`** — table `orders` (`id`, `warehouse_id` FK,
`client_id` nullable FK set-null, `order_statuses_id` nullable FK set-null,
`generated_year_order_id`, `generated_custom_order_id` unique, `discount`
decimal(12,4) nullable, `custom_order_id` (unique per warehouse), booleans
`completed`/`picked`/`cancelled`/`delivered`/`on_hold`,
`invoice_name`/`invoice_address`/`invoice_zipcode`/`invoice_region`/`invoice_city`/`invoice_country`
and `delivery_name`/`delivery_address`/`delivery_zipcode`/`delivery_region`/`delivery_city`/`delivery_country`,
`telephone_number`, `email`, `comments`, `delivery_date` date, timestamps).
Uses `BelongsToWarehouse` + `LogsActivity`. Relations: `warehouse()`, `client()`
(`Modules\Clients\Models\Client`, see [`clients.md`](clients.md)),
`orderStatus(): BelongsTo(OrderStatus::class,'order_statuses_id')`,
`products(): HasMany(OrderProduct)`.

**No FK to `client_addresses`**: `Order` has no `client_address_id`,
`delivery_client_address_id`, or `bill_client_address_id` column — its
`invoice_*`/`delivery_*` fields are plain strings on the `orders` table
itself. They are meant to be a **copy** of the `ClientAddresses` row the
client selected at order-creation time (see [`clients.md`](clients.md) for
why: an order must not silently change address if the client's stored
address is edited later), not a live reference to it.

**Verified gap (documentation accuracy, not new work in this pass)**: as of
this checkout, no code path actually performs that copy.
`Modules\Orders\Filament\Resources\Orders\Schemas\OrderForm` has only
`OrderStatusSelect` and `ClientSelect` — no address fields, no
`afterStateUpdated` copying from `clientDeliveryAddress()`/`clientBillAddress()`
(`ClientSelect` eager-loads `clientDeliveryAddress` only to build its option
label, it writes nothing). `CreateOrder::mutateFormDataBeforeCreate()` only
sets `warehouse_id`. So an order created in the admin panel today has `null`
`delivery_*`/`invoice_*` fields. The E-commerce API's `OrderCreationService`
(§5.5 below) does populate these fields, but straight from whatever the API
caller puts in the request body — it never reads the client's stored
`ClientAddresses`. The rule described above ("always copy the client's
selected address onto the order") is the intended, correct behavior; it is
not yet implemented anywhere.

**Model: `Modules\Orders\Models\OrderProduct`** — table `order_products` (`id`,
`order_id` FK cascade, `product_id` nullable FK set-null, `vat_rate_id` nullable FK
set-null, `name`, `quantity` bigInteger nullable, `weight` bigInteger nullable,
`price` decimal(12,4), `vat_rate` decimal(12,4), `barcode`, `reference_code`,
timestamps). Relations: `order()`, `product()` (see [`products.md`](products.md)),
`vatRate()` (see [`settings.md`](settings.md)).

**Fixed — History tab was broken**: `OrderResource`'s History tab
(`Pages\ManageOrderActivities`) set `$relationship = 'activities'`, but
`Order` only has `LogsActivity`, which provides `activitiesAsSubject()`, not
`activities()` — visiting the tab threw `BadMethodCallException`. Fixed to
`$relationship = 'activitiesAsSubject'`. See [`wms.md`](wms.md) §2 for the
full pattern and the shared regression test (`tests/Feature/ActivityLogHistoryTabTest.php`)
that covers this.

**Implemented — Activity Log History Tab for `OrderStatus`**: `OrderStatus` has
no `LogsActivity` and `OrderStatusResource` has no History tab today. Add
`LogsActivity` + `getActivitylogOptions()` (`useLogName('order_status')->logFillable()->logOnlyDirty()->dontLogEmptyChanges()`,
`warehouse_id` has a direct FK so no tenant-scoping special case is needed —
see [`wms.md`](wms.md) §2). Add `Pages\ManageOrderStatusActivities`
(same shape as `ManageOrderActivities`, already-fixed relationship name) and
wire `getRecordSubNavigation()` → `[EditOrderStatus::class, ManageOrderStatusActivities::class]`
plus the `'history'` route in `getPages()`.

**Resource: `Modules\Orders\Filament\Resources\Orders\OrderResource`** — no
navigation group, icon `Heroicon::OutlinedShoppingCart`, sub-navigation
(General/History). `canDelete()` → `$record->orderStatus?->canDeleteOrder() ?? true`.
- Form (`OrderForm`, `Section columns(2)`): `OrderStatusSelect`
  (`->relationship('orderStatus','name')->required()`), `ClientSelect`
  (`->relationship('client', modifyQueryUsing: eager-load + order by company/email)`,
  custom option labels, `->searchable(['company','email'])`).
- Table (`OrdersTable`): `GeneratedCustomOrderIdColumn` (searchable),
  `OrderStatusColumn` (custom HTML badge via `formatStateUsing`, searchable,
  sortable), `TotalQuantityColumn` (`->sum('products','quantity')`, sortable),
  `TotalPriceColumn` (computed state, `->money()`, sortable), `ClientEmailColumn`
  (sortable). No filters. Row action: `EditAction` labeled "Edit"/"View" based on
  `canEditOrder()`. Bulk delete only.
- Relation manager: `OrderProductRelationManager` (relationship `products`) — form
  `ProductSelect` (`->relationship('product','name')->searchable()->preload()->live()`,
  `afterStateUpdated` auto-fills vat_rate/barcode/price/name/reference_code from the
  selected Product), `QuantityInput` (`numeric()->minValue(1)->default(1)->required()`),
  read-only display fields (Name/ReferenceCode/Barcode/Price/VatRate all
  `->disabled()->dehydrated()`). Header/row actions gated by
  `$this->getOwnerRecord()->orderStatus?->canModifyProducts() ?? true`.
- Pages: `ListOrders`, `CreateOrder` (sets `warehouse_id` from tenant),
  `EditOrder` (disables whole schema when `!canEditOrder()`), `ManageOrderActivities`
  (history, via `App\Filament\Resources\ActivityLogs\ActivityLogResource`).

**Order status transition service (existing, will be extended in §3)**:
`App\Services\OrderStatusTransitionService` — orchestrates
`generatePicklist()`, `reserveStock()`, `reduceStock()`, `releaseReservedStock()`,
`refreshReservedStock(Order $order)`, `calculateReservedQuantity(int $productId)`,
`recalculateFreeStock(StockProduct $stockProduct)`, `syncOrderFlags()`, all
triggered from `Modules\Orders\Events\OrderStatusChanged` (dispatched by
`Modules\Orders\Observers\OrderObserver::updated()` when `order_statuses_id`
changes) via `App\Listeners\ProcessOrderStatusTransition`. Stock mutations use
`DB::transaction()` + `StockProduct::query()->lockForUpdate()`. Workflow steps are
logged via `activity('order_workflow')->event(...)->log(...)` with duplicate-guard
checks.

---

## 3. New Work — Completing the Purchase Order Feature

### 3.1 Current state (before this plan)

- `Modules\Orders\Models\PurchaseOrder` — table `purchase_orders` (`id`,
  `warehouse_id` FK cascade, `completed` bool default false, `processed` bool
  default false, `comments` text nullable, `expected_delivery_date` date NOT
  nullable, `generated_year_purchase_order_id`,
  `generated_custom_purchase_order_id` unique per warehouse, timestamps). Uses
  `BelongsToWarehouse` only (no `LogsActivity`, no Scout methods).
- `Modules\Orders\Models\PurchaseOrderProduct` — table `purchase_orders_products`
  (`id`, `purchase_order_id` FK cascade, `show_for_supplier` bool, `barcode`,
  `reference_code`/`color`/`size` nullable, `product_title`, `scanned` bool
  default false). **No `product_id` and no quantity column** — one row per unit,
  matched by barcode only.
- `Modules\Orders\Models\PurchaseOrderFailedProduct` — table
  `purchase_order_failed_products`, identical shape to `picklist_failed_products`
  (see [`picklists.md`](picklists.md)).
- `Modules\Orders\Observers\PurchaseOrderObserver` generates
  `generated_custom_purchase_order_id` on `creating()` (clone of
  `PicklistObserver`).
- `Modules\Orders\Filament\Resources\PurchaseOrders\PurchaseOrderResource` — List
  (`ViewAction` row only)/Create/View pages, no Edit. `CreatePurchaseOrder` wraps
  creation + `App\Services\PurchaseOrderImportService::import()` in
  `DB::transaction()`. The `ViewPurchaseOrder` page has **`getHeaderActions() → []`
  and no scan handler at all** — the infolist Blade views
  (`purchase-order-products-table.blade.php`,
  `purchase-order-failed-products-table.blade.php`) render static tables with no
  `onscan.js` wiring.
- `App\Services\PurchaseOrderImportService::import()` reads CSV/XLSX
  (barcode+quantity rows), matches `Product` by `barcode` + `warehouse_id`,
  expands each row into one `PurchaseOrderProduct` per unit, but **never stores
  `product_id`**.
- Nothing anywhere sets `processed = true`, nothing increments `on_stock_quantity`,
  and no order/purchase-order stock-dependency logic exists.

### 3.2 Commands

Run before writing code:

```bash
php artisan make:migration add_product_id_to_purchase_orders_products_table --path=app-modules/orders/database/migrations --no-interaction
php artisan make:class Services/PurchaseOrderProcessingService --no-interaction
php artisan make:test PurchaseOrderScanningTest --pest --no-interaction
php artisan make:test PurchaseOrderStockDeferralTest --pest --no-interaction
php artisan make:test PurchaseOrderMarkReceivedTest --pest --no-interaction
```

(`make:test --pest` writes into `tests/Feature/`, matching this project's
existing convention of keeping real tests outside `app-modules/*/tests/`, which
are `.gitkeep` placeholders only.)

### 3.3 Migration

New migration `add_product_id_to_purchase_orders_products_table` (no `down()`,
per project rule):

```php
Schema::table('purchase_orders_products', function (Blueprint $table) {
    $table->foreignId('product_id')
        ->nullable()
        ->after('purchase_order_id')
        ->constrained('products')
        ->cascadeOnUpdate()
        ->nullOnDelete();
});
```

This mirrors `order_products.product_id` (nullable, set-null on delete) rather
than `purchase_orders.warehouse_id` (cascade) — a purchase order's history should
survive a product being deleted, matching the `OrderProduct` convention exactly.

### 3.4 Model changes

**`PurchaseOrder`** (`app-modules/orders/src/Models/PurchaseOrder.php`):
- Add `use Spatie\Activitylog\Models\Concerns\LogsActivity;` and
  `getActivitylogOptions(): LogOptions` (`useLogName('purchase_order')->logFillable()->logOnlyDirty()->dontLogEmptyChanges()`),
  matching `Order`'s convention — this is a **required compliance fix** (project
  rule: every model must define Scout methods; `Order`, the sibling parent-level
  model, already does this, so `PurchaseOrder` should too rather than diverging).
- **Correction (verified by direct source read)**: `PurchaseOrder`,
  `PurchaseOrderProduct`, and `PurchaseOrderFailedProduct` **already define**
  `getSearchableSettings()`/`toSearchableArray()` — an earlier pass of this
  Blueprint incorrectly claimed these were missing. No Scout-method work is
  needed on any of the three models. (Separately: `laravel/scout` itself is not
  installed and `config/scout.php` does not exist anywhere in this project, so
  these methods are currently inert on every model that has them, including in
  the `orders` module — registering the package/config is a distinct,
  project-wide gap, not part of this Purchase Order work.)
- Add two domain helper methods (matching the `OrderStatus::can*()` convention —
  no Policy class):
  ```php
  public function isFullyScanned(): bool
  {
      return $this->products()->where('scanned', false)->doesntExist();
  }

  public function canMarkReceived(): bool
  {
      return $this->processed && ! $this->completed;
  }
  ```
- `casts()` stays last in the class per project rule; add nothing new to it
  (`expected_delivery_date`/`completed`/`processed` are already cast).

**`PurchaseOrderProduct`** (`app-modules/orders/src/Models/PurchaseOrderProduct.php`):
- Add `'product_id'` to `$fillable`.
- Add relation: `public function product(): BelongsTo { return $this->belongsTo(Product::class); }`
  (`Modules\Products\Models\Product`, see [`products.md`](products.md)).

**`App\Services\PurchaseOrderImportService`** (`app/Services/PurchaseOrderImportService.php`):
- When resolving a matched `Product` by barcode + warehouse, also set
  `'product_id' => $product?->id` on each created `PurchaseOrderProduct` row
  (alongside the existing `product_title`/`reference_code`/etc. copy). Rows for
  unmatched barcodes keep `product_id = null` and `product_title = 'Unknown product'`
  (existing behavior, unchanged).
- After the import loop finishes (still inside the same transaction as
  `CreatePurchaseOrder::handleRecordCreation()` — do not add a second nested
  transaction here, just return the affected product IDs), collect the distinct
  non-null `product_id`s that were created and call
  `app(App\Services\OrderStatusTransitionService::class)->refreshReservedStockForProductIds($productIds)`
  **after** the transaction commits (a new incoming PO can make previously-reserved
  demand deferrable — see §3.5 — so reservations must be recalculated whenever new
  incoming supply appears, not only when it lands).

### 3.5 Service layer — stock rules

**Cross-requirement consistency (per skill's required check)**:

- **Rule (canonical, one formula, reused everywhere)**:
  `free_on_stock_quantity = max(0, on_stock_quantity - reserved_quantity - reserved_on_picklists)`,
  implemented once in `OrderStatusTransitionService::recalculateFreeStock()` and
  never re-implemented by the new Purchase Order code — the new service always
  calls back into `OrderStatusTransitionService` for this step, per below. This
  is the same formula documented in [`products.md`](products.md).
- **Stages**: (1) a client order is placed with a `delivery_date` — no reservation
  decision needed yet; (2) `OrderStatusTransitionService::refreshReservedStock()`
  runs whenever an order's status changes to/from `reserve_stock = true` — this is
  where the deferral decision is made; (3) a purchase order is imported (new
  incoming supply appears) — deferral eligibility can change for products it
  covers, so reservations must be recalculated; (4) a purchase order finishes
  scanning (`processed = true`, stock physically increases) — deferred demand that
  was waiting on this specific incoming batch now either converts to a normal
  reservation (stock covers it) or continues waiting on a later PO.
- **Consistency check (discriminating case, matches the domain doc's own
  example)**: order for 100 units of product X, `delivery_date` = 2026-11-01. A
  purchase order for the same product, `expected_delivery_date` = 2026-10-15
  (before the order's date), not yet processed, has ≥100 incoming units, and
  current `on_stock_quantity` = 0. Expected: the order's 100 units are **not**
  added to `reserved_quantity` (deferred), `free_on_stock_quantity` is unaffected
  by this order. Once that PO is fully scanned (`processed = true`,
  `on_stock_quantity += 100`), recalculation must show the order's 100 units now
  reserved from the newly-arrived stock (assuming no other order got there first
  by having an earlier `delivery_date` — see priority rule below).
- **Roundtrip case**: if the PO import is later followed by a second, competing
  order for the same product with an *earlier* `delivery_date` than the first, the
  second order must claim the incoming quantity first (priority = earliest
  `delivery_date` first) — the first order falls back to being reserved anyway
  (legacy behavior) if there isn't enough stock/incoming supply left for it. This
  is the accepted ambiguity limitation from the "implicit by product + timing"
  decision.
- **Boundary case**: with **no purchase orders at all** for a product, the new
  algorithm must produce byte-identical `reserved_quantity` results to the current
  simple `SUM(quantity)` implementation — this is a hard regression requirement,
  verified by re-running the existing `tests/Feature/OrderStatusTransitionTest.php`
  scenarios unchanged (see §3.7).

**`App\Services\OrderStatusTransitionService` — replace `calculateReservedQuantity()`**:

```php
protected function calculateReservedQuantity(int $productId): int
{
    $orderLines = DB::table('order_products')
        ->join('orders', 'orders.id', '=', 'order_products.order_id')
        ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_statuses_id')
        ->where('order_products.product_id', $productId)
        ->where('order_statuses.reserve_stock', true)
        ->where('orders.picked', false)
        ->where('orders.cancelled', false)
        ->orderBy('orders.delivery_date') // earliest delivery date served first
        ->get(['order_products.quantity', 'orders.delivery_date']);

    $onStockQuantity = StockProduct::where('product_id', $productId)->value('on_stock_quantity') ?? 0;
    $remainingStock = $onStockQuantity;

    // Outstanding (not-yet-processed) incoming purchase order quantity, grouped
    // by expected_delivery_date, earliest first.
    $incomingBatches = DB::table('purchase_orders_products')
        ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_orders_products.purchase_order_id')
        ->where('purchase_orders_products.product_id', $productId)
        ->where('purchase_orders.processed', false)
        ->select('purchase_orders.expected_delivery_date')
        ->get()
        ->groupBy('expected_delivery_date')
        ->map(fn ($rows) => $rows->count())
        ->sortKeys()
        ->map(fn (int $qty, string $date) => (object) ['expected_delivery_date' => $date, 'remaining' => $qty])
        ->values();

    $reserved = 0;

    foreach ($orderLines as $line) {
        $quantity = (int) $line->quantity;

        $fromStock = min($quantity, $remainingStock);
        $remainingStock -= $fromStock;
        $reserved += $fromStock;

        $needed = $quantity - $fromStock;

        if ($needed > 0) {
            foreach ($incomingBatches as $batch) {
                if ($needed <= 0) {
                    break;
                }

                if ($batch->remaining <= 0 || $batch->expected_delivery_date > $line->delivery_date) {
                    continue; // this incoming batch won't arrive in time for this order
                }

                $take = min($needed, $batch->remaining);
                $batch->remaining -= $take;
                $needed -= $take;
            }
        }

        // Anything not covered by current stock nor a timely incoming PO is
        // still reserved (this matches today's behavior for every product that
        // has no purchase orders at all — required for the regression boundary).
        $reserved += $needed;
    }

    return $reserved;
}
```

Add a new public method (called by the Purchase Order side, since it has no
`Order` context to key off of):

```php
public function refreshReservedStockForProductIds(array $productIds): void
{
    foreach (array_unique(array_filter($productIds)) as $productId) {
        DB::transaction(function () use ($productId): void {
            $stockProduct = StockProduct::query()
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stockProduct) {
                return;
            }

            $stockProduct->reserved_quantity = $this->calculateReservedQuantity($productId);
            $this->recalculateFreeStock($stockProduct);
            $stockProduct->save();
        });
    }
}
```

No visibility change to `recalculateFreeStock()` is needed — it stays
`protected`; it's only ever called from within `OrderStatusTransitionService`
itself (including from the new method above), never from
`PurchaseOrderProcessingService` directly.

**No scheduled/cron job is added** to force reservation purely because an
`expected_delivery_date` passed without the PO being processed. Per the domain
doc, "once delivered" (i.e. `processed = true`) is the actual trigger — a late,
unprocessed PO simply keeps the dependent order deferred until it's scanned or
manually addressed. This is a stated assumption, not a question left open.

### 3.6 New service: `App\Services\PurchaseOrderProcessingService`

```php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Models\PurchaseOrder;
use Modules\Products\Models\StockProduct;

class PurchaseOrderProcessingService
{
    public function __construct(
        private readonly OrderStatusTransitionService $orderStatusTransitionService,
    ) {}

    public function processScanCompletion(PurchaseOrder $purchaseOrder): void
    {
        $productCounts = $purchaseOrder->products()
            ->where('scanned', true)
            ->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id');

        DB::transaction(function () use ($purchaseOrder, $productCounts): void {
            foreach ($productCounts as $productId => $quantity) {
                $stockProduct = StockProduct::query()
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $stockProduct) {
                    continue;
                }

                $stockProduct->on_stock_quantity += $quantity;
                $stockProduct->save();
            }

            $purchaseOrder->processed = true;
            $purchaseOrder->save();
        });

        $this->orderStatusTransitionService->refreshReservedStockForProductIds(
            $productCounts->keys()->all()
        );
    }

    public function markReceived(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->completed = true;
        $purchaseOrder->save();
    }
}
```

Behavior notes for the implementer:
- `processScanCompletion()` is idempotent-safe to call only once (it's invoked
  from exactly one call site — see §3.7 — guarded by "all products scanned").
  Products with `product_id = null` (unmatched barcodes) are excluded from the
  stock increment but still count toward "all scanned" for triggering this method
  (a PO can complete even if one line was an unrecognized barcode — physical
  receipt still happened, it just can't be attributed to a `Product`).
  Extra scan-completion detection or an idempotency guard is not being added here
  because this method has exactly one call site in this plan (no retry path).
- `markReceived()` does not touch stock at all, per the resolved decision — it is
  a pure status confirmation.
- `LogsActivity` on `PurchaseOrder` (added in §3.4) automatically records the
  `processed`/`completed` flips as activity-log "updated" events via
  `logOnlyDirty()` — no manual `activity()->log()` call is needed here, reusing
  the existing convention instead of introducing a parallel logging mechanism.

### 3.7 Filament changes

**`Modules\Orders\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder`**
(`app-modules/orders/src/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php`)
— add the scan handler, mirroring `ViewPicklist` exactly (see
[`picklists.md`](picklists.md)) but against
`PurchaseOrderProduct`/`PurchaseOrderFailedProduct` and a distinct event name:

```php
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Modules\Orders\Models\PurchaseOrderFailedProduct;
use Modules\Orders\Models\PurchaseOrderProduct;
use App\Services\PurchaseOrderProcessingService;

#[On('purchase-order-barcode-scanned')]
public function handleScannedBarcode(string $barcode): void
{
    $this->scanProductBarcode($barcode);
}

public function scanProductBarcode(string $barcode): void
{
    $barcode = trim($barcode);

    $product = PurchaseOrderProduct::query()
        ->where('purchase_order_id', $this->record->id)
        ->where('barcode', $barcode)
        ->orderBy('scanned')
        ->first();

    if (! $product || $product->scanned) {
        $failedProduct = PurchaseOrderFailedProduct::query()
            ->where('purchase_order_id', $this->record->id)
            ->where('barcode', $barcode)
            ->first();

        if ($failedProduct) {
            $failedProduct->increment('total_quantity_scanned');
        } else {
            PurchaseOrderFailedProduct::query()->create([
                'purchase_order_id' => $this->record->id,
                'barcode' => $barcode,
                'reference_code' => $product?->reference_code,
                'color' => $product?->color,
                'size' => $product?->size,
                'product_title' => $product?->product_title,
                'total_quantity_scanned' => 1,
            ]);
        }

        Notification::make()
            ->title($product ? __('Product already scanned') : __('Unknown barcode'))
            ->danger()
            ->send();

        return;
    }

    $product->scanned = true;
    $product->save();

    $this->dispatch('purchase-order-product-scan-success', productId: $product->id);

    Notification::make()->title(__('Product scanned'))->success()->send();

    $purchaseOrder = $this->record->fresh();

    if ($purchaseOrder->isFullyScanned()) {
        app(PurchaseOrderProcessingService::class)->processScanCompletion($purchaseOrder);

        Notification::make()
            ->title(__('Purchase order fully scanned — stock updated'))
            ->success()
            ->send();
    }
}

protected function getHeaderActions(): array
{
    return [
        Action::make('markReceived')
            ->label(__('Mark Received'))
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(fn () => $this->record->canMarkReceived())
            ->requiresConfirmation()
            ->modalDescription(__('Are you sure you want to mark this purchase order as received? This does not change stock.'))
            ->action(function () {
                app(PurchaseOrderProcessingService::class)->markReceived($this->record);
                $this->record->refresh();

                Notification::make()->title(__('Purchase order marked as received'))->success()->send();
            }),
    ];
}
```

**Action: Mark Received** (full spec per required format)
- Component: `Filament\Actions\Action`
- Docs: https://filamentphp.com/docs/4.x/actions/overview
- Location: page header (`ViewPurchaseOrder`)
- Icon: `Heroicon::CheckCircle`
- Color: `success`
- Visibility: only when `$record->processed === true && $record->completed === false`
  (i.e. `$record->canMarkReceived()`)
- Authorization: all authenticated users (no policy layer exists in this app —
  matches project convention, see [`wms.md`](wms.md) §1)
- Confirmation: "Are you sure you want to mark this purchase order as received?
  This does not change stock."
- Behavior:
  - Call `PurchaseOrderProcessingService::markReceived($record)`
  - Refresh the record on the page
- Notification: "Purchase order marked as received"

**No manual override/force-complete action is added** for the scan-completion →
`processed` transition — that stays fully automatic per the resolved decision. If
a warehouse ever needs to force-complete a PO with missing/lost scans, that is a
follow-up decision for the user, not assumed here.

**Blade view**:
`app-modules/orders/resources/views/filament/infolists/components/purchase-order-products-table.blade.php`
— add the same `onscan.js` wiring pattern as
`app-modules/picklists/resources/views/filament/infolists/components/products-table.blade.php`
(see [`picklists.md`](picklists.md)), with two changes: dispatch event name
`purchase-order-barcode-scanned` (not `picklist-barcode-scanned`), and listen for
`purchase-order-product-scan-success` (not `picklist-product-scan-success`) for
the row-highlight behavior. Reuse the existing row id convention
(`#purchase-order-product-row-{id}`, already present in the static table markup
per the exploration report) so the highlight script can target it.

Aside from the History tab (§3.9a below), no other Filament resource files
need to change. `PurchaseOrderResource`'s `infolist()`/table/form stay as-is —
the existing `ProcessedEntry`/`CompletedEntry` already display the two
booleans this plan now actually drives.

### 3.8 Authorization

- `PurchaseOrderResource` gets no new `canCreate`/`canDelete`/`canEdit` overrides —
  none of the new work changes who can view/import a purchase order.
- The "Mark Received" action's visibility rule (`canMarkReceived()`) is a workflow
  gate, not an authorization rule — per `checklist.md`'s guidance, "Authorization:
  All authenticated users" applies here since no role/permission system exists in
  this app (see [`wms.md`](wms.md) §1).

### 3.9 Tests

All new Pest tests live in `tests/Feature/` (project convention — module
`tests/` directories are `.gitkeep` placeholders only), using the existing
`createOrderWorkflowContext()`-style manual fixture setup from
`tests/Feature/OrderStatusTransitionTest.php` (`Subdomain` →
`app()->instance('current_subdomain', ...)` → `Warehouse` → `Product` →
`StockProduct` → `Order`/`OrderProduct`), extended with `PurchaseOrder`/
`PurchaseOrderProduct` fixtures.

**`tests/Feature/PurchaseOrderScanningTest.php`**:
- Scanning a known barcode marks the matching `PurchaseOrderProduct.scanned = true`
  and dispatches no failed-product row.
- Scanning an unknown barcode creates a `PurchaseOrderFailedProduct` row
  (`total_quantity_scanned = 1`); scanning the same unknown barcode again
  increments it to `2`.
- Scanning an already-scanned barcode creates/increments a
  `PurchaseOrderFailedProduct` row copying the matched product's
  reference_code/color/size/product_title.
- Scanning the last remaining unscanned row sets `PurchaseOrder.processed = true`
  and increases the linked `StockProduct.on_stock_quantity` by exactly the number
  of scanned rows for that `product_id` (assert via `Livewire::test(ViewPurchaseOrder::class, ['record' => $purchaseOrder])->call('scanProductBarcode', $barcode)`,
  following this codebase's first `Livewire::test(...)` usage — there's no prior
  precedent, so this establishes the pattern for future Livewire-page tests here).
- A row with `product_id = null` (unmatched-at-import barcode) still counts toward
  "fully scanned" but contributes no stock increment.

**`tests/Feature/PurchaseOrderStockDeferralTest.php`** (the cross-requirement
consistency case from §3.5):
- Order for 100 units of a product with `delivery_date` in the future; an
  outstanding (unprocessed) purchase order for the same product with
  `expected_delivery_date` before that order's `delivery_date` and ≥100 incoming
  units; `on_stock_quantity = 0`. Assert: `StockProduct.reserved_quantity` does
  **not** include this order's 100 units after
  `OrderStatusTransitionService::refreshReservedStockForProductIds()` runs (or the
  equivalent order-status-change trigger).
  Confirm this is a genuine behavioral change vs the boundary case below.
- Same setup, but after the purchase order is fully scanned
  (`processScanCompletion()` called), assert `on_stock_quantity` increased by the
  scanned quantity and `reserved_quantity` now includes the order's 100 units
  (stock now covers it).
- Two competing orders for the same product, different `delivery_date`s, and an
  incoming PO that can only cover one of them — assert the order with the
  *earlier* `delivery_date` is the one deferred (gets priority for the incoming
  batch); the later order falls back to being reserved anyway.
- **Regression assertion**: re-run (or replicate) the existing scenarios from
  `tests/Feature/OrderStatusTransitionTest.php` with no purchase orders involved
  at all, and assert `reserved_quantity` is unchanged from current behavior (pure
  `SUM(quantity)` equivalence) — this must pass without modification to that
  existing test file.

**`tests/Feature/PurchaseOrderMarkReceivedTest.php`**:
- `PurchaseOrder::canMarkReceived()` is `false` before `processed = true`, `true`
  after, and `false` again once `completed = true`.
- Calling `PurchaseOrderProcessingService::markReceived()` sets `completed = true`
  and does not change any `StockProduct` values.
- The "Mark Received" header action is hidden/absent on the `ViewPurchaseOrder`
  page when `processed = false`, and visible when `processed = true && completed = false`
  (assert via `Livewire::test(ViewPurchaseOrder::class, ...)->assertActionHidden('markReceived')` /
  `->assertActionVisible('markReceived')`).

**Update `tests/Feature/PurchaseOrderImportTest.php`**: add an assertion that
imported rows matched to a known product now also populate `product_id` (not just
`product_title`), and that unmatched ("Unknown product") rows keep `product_id`
null.

### 3.9a Implemented — Activity Log History Tab for Purchase Orders

`PurchaseOrder` already has `LogsActivity` (§3.4 above) — only the Filament
side is missing. Add `Pages\ManagePurchaseOrderActivities` (same shape as
`ManageOrderActivities`, with the relationship bug already fixed —
`$relationship = 'activitiesAsSubject'`, see [`wms.md`](wms.md) §2) and wire
`PurchaseOrderResource::getRecordSubNavigation()` →
`[ViewPurchaseOrder::class, ManagePurchaseOrderActivities::class]` (no Edit
page exists for Purchase Orders, so `ViewPurchaseOrder` is the companion
"General" tab, not `EditPurchaseOrder`), plus the `'history'` route in
`getPages()`. `PurchaseOrder` already appears in
`ActivityLogResource::scopeEloquentQueryToTenant()`'s subject list, so no
tenant-scoping change is needed for this one.

---

## 4. Verification Steps

1. `php artisan migrate` (or `--pretend` first) to confirm the new `product_id`
   column migration applies cleanly against the existing `purchase_orders_products`
   table.
2. Run the new focused tests:
   `php artisan test --compact --filter=PurchaseOrderScanningTest`,
   `--filter=PurchaseOrderStockDeferralTest`, `--filter=PurchaseOrderMarkReceivedTest`,
   `--filter=PurchaseOrderImportTest`.
3. Re-run `php artisan test --compact --filter=OrderStatusTransitionTest` to confirm
   the `calculateReservedQuantity()` rewrite is a true no-op for products with no
   purchase orders (regression boundary case from §3.5/§3.9).
4. Manually verify in the browser (Herd, `https://wms.test` or the relevant
   subdomain): create a Purchase Order with a CSV import, open its View page,
   confirm the onscan.js scanner script fires `purchase-order-barcode-scanned` and
   the row highlights/marks scanned; scan all rows and confirm the "Mark Received"
   action appears and the linked product's Stock tab
   (`ProductResource`'s `EditProductStock` page) shows the increased
   `on_stock_quantity`/recalculated `free_on_stock_quantity`.
5. Mandatory project checks: `php artisan lang:extract`, `vendor/bin/pint --dirty --format agent`,
   `compose analyse`, `compose lint`.

**Implementation status**: §3 has been implemented and verified in this checkout
— all listed model/service/Filament changes are in place, the four new/updated
test files pass (18 tests, 54 assertions), the existing `OrderStatusTransitionTest`
regression check passes unchanged, `vendor/bin/phpstan analyse` and
`vendor/bin/pint --dirty --format agent` are clean, and `php artisan migrate --pretend`
confirms the migration's SQL. Two pre-existing bugs were found and fixed as
necessary enablers (not part of the original plan, discovered during
implementation): `Order::$fillable` was missing `delivery_date` entirely and had
a stray `'telephone'` entry instead of the real `telephone_number` column
(blocking, since the stock-deferral algorithm depends on `delivery_date` being
settable); and both purchase-order infolist Blade views crashed
(`foreach() on null`) whenever `products`/`failedProducts` was empty, fixed with
`?? []`. §5 below assumes both fixes are in place — they are.

---

## 5. New Work — E-commerce Order Creation API

### 5.1 Context and decisions locked in with the user

Each shop (warehouse) needs an API, authenticated by a token generated in the
Settings panel, so an external storefront/frontend can browse products, place
an order, and have that order processed through the existing picklist/stock
pipeline — the core "buy a product → place an order → order gets processed"
e-commerce flow.

**What already exists (confirmed by direct source read, no changes needed)**:
- Token generation in Settings already works exactly as required:
  `Modules\Settings\Filament\Resources\ApiKeys\Pages\CreateApiKey::mutateFormDataBeforeCreate()`
  generates a random 64-char token via `Str::random(64)`, stores only
  `hash('sha256', $token)` on `ApiKey.key_hash`, and `afterCreate()` shows the
  plaintext token exactly once via a persistent Filament notification. This is
  the "generate a token in the setting section" requirement — already built (see
  [`settings.md`](settings.md)).
- `App\Http\Middleware\AuthenticateApiKey` (aliased `api.key` in
  `bootstrap/app.php`) already authenticates every request via `X-Api-Key`
  header or bearer token, checks `is_active`/`expires_at`/`allowed_ips`,
  cross-checks the key's warehouse against the current subdomain, and stamps
  `$request->attributes->get('warehouse')` — this is the warehouse-scoping root
  for everything below.
- `routes/api.php` already has read-only `GET /v1/products`, `/v1/products/{id}`,
  `/v1/clients`, `/v1/clients/{id}`, `/v1/orders`, `/v1/orders/{id}`, each backed
  by `App\Http\Controllers\Api\{Product,Client,Order}Controller` and
  `App\Http\Resources\{Product,Client,Order,OrderProduct}Resource` — these cover
  "browse products" already. No changes to these GET endpoints or their response
  shapes.
- `Order::$fillable` includes `delivery_date` and `telephone_number` (fixed as
  part of the Purchase Order work in §3 — previously `delivery_date` was
  missing entirely and `telephone_number` was misspelled as `telephone`). The
  request validation in §5.4 relies on this already being true.

**Decisions made with the user for this new work**:
- **No rename**: keep `Client`/`client_id` exactly as-is everywhere (considered
  and explicitly declined a `Client → Customer` rename; see [`clients.md`](clients.md)).
- **Customer identification on order creation**: inline customer data on the
  order payload; the API finds an existing `Client` by `email` **scoped to the
  warehouse** (matches the existing `unique(warehouse_id, email)` constraint) or
  creates one. No separate `POST /v1/clients` endpoint is being added — creating
  a `Client` only happens as a side effect of creating an order.
- **Warehouse stays the scoping root** for everything (already true via the
  `ApiKey`/`AuthenticateApiKey` pattern — no change needed there).
- **Order processing trigger**: the order-create payload selects an
  `order_status_id`; if that status's flags (`generate_picklist`,
  `reserve_stock`, `reduce_stock`, etc.) are set, the **existing**
  `OrderObserver` → `OrderStatusChanged` → `ProcessOrderStatusTransition` →
  `OrderStatusTransitionService` pipeline must fire — reusing it exactly as-is,
  no parallel/duplicate pipeline in the new API code. Orders placed through this
  API also automatically benefit from the §3 stock-deferral algorithm
  (`calculateReservedQuantity()`) as long as `delivery_date` is supplied.
- **API keys stay all-or-nothing per warehouse** — no new scope/permission field
  on `ApiKey`, no schema change to it. A valid, active key can read and now also
  write within its own warehouse.

**Cross-requirement consistency — verified lifecycle ordering (critical, not
obvious from a surface reading of the Observer)**: `Modules\Orders\Observers\OrderObserver`
only implements `creating()` (ID generation) and `updated()` (dispatches
`OrderStatusChanged`, guarded by `$order->wasChanged('order_statuses_id')`).
**There is no `created()` hook.** This means creating an `Order` with
`order_statuses_id` already populated in the same `create()` call will
**silently not fire the pipeline** — `wasChanged()` has nothing to compare
against on a fresh `INSERT`. Verified by direct read of
`app-modules/orders/src/Observers/OrderObserver.php:50-72`.

- **Rule**: the only way to trigger the pipeline is a genuine Eloquent `update()`
  that changes `order_statuses_id` from one value (including `null`) to another.
- **Stages**: (1) create the `Order` row with `order_statuses_id = null`; (2)
  immediately `update()` it to the requested `order_statuses_id` as a distinct,
  second write, in the same DB transaction; (3) `OrderObserver::updated()` fires
  genuinely, dispatches `OrderStatusChanged`; (4) `ProcessOrderStatusTransition`
  (queued, `ShouldQueueAfterCommit`) picks it up **after the transaction
  commits** and runs `OrderStatusTransitionService::process()` exactly as it
  does for orders edited in the Filament admin.
- **Consistency check**: an order created via the API with a status that has
  `generate_picklist = true` must end up with exactly one `Picklist` row, the
  same as manually editing an `Order`'s status field in `EditOrder` today — same
  canonical pipeline, two entry points.
- **Verification**: see §5.5 — assert a `Picklist` exists and `StockProduct`
  reflects reservation only after the queued listener runs (`Queue::fake()` then
  assert pushed, or run the listener synchronously in the test, matching this
  project's `QUEUE_CONNECTION=sync` testing config — see `phpunit.xml`).
- **Operational note (not a code change)**: because the listener is
  `ShouldQueueAfterCommit`, picklist/stock effects happen asynchronously after
  the API responds with `201`. The response body reflects the order as created,
  not post-processing state. A queue worker must be running in production for
  this to happen at all (already true today for admin-panel edits — not a new
  operational requirement, just newly relevant to an external API consumer).

**Stock-availability decision**: to "work great for the e-commerce flow of
buying a product," the API must not let a storefront oversell. For every line
item whose `Product.stock_unlimited` is `false`, requested `quantity` must not
exceed that product's current `StockProduct.free_on_stock_quantity` (see
[`products.md`](products.md)) — enforced as a **precondition check before any
write**, independent of whether the chosen `order_status`'s `reserve_stock` flag
is set (a shop should not be able to create an order with more units than are
free, even against a "concept" status). Partial fulfillment/backorder is out of
scope for this pass — an insufficient-stock item rejects the whole request.

**Idempotency decision**: `orders` already has `unique(warehouse_id,
custom_order_id)`. `POST /v1/orders` treats `custom_order_id` as an idempotency
key when supplied: if an order with that `custom_order_id` already exists for
the warehouse, return the **existing** order with `200 OK` instead of creating
a duplicate or raising a DB unique-constraint error. This is required for
real storefront integrations (Shopify/Woo-style webhook retries). If
`custom_order_id` is omitted, every request creates a new order (no dedup
possible).

### 5.2 Commands

```bash
php artisan make:request Api/StoreOrderRequest --no-interaction
php artisan make:class Services/OrderCreationService --no-interaction
php artisan make:test Api/CreateOrderEndpointTest --pest --no-interaction
```

### 5.3 Route change

`routes/api.php` — add `store` to the existing `orders` resource (no change to
`products`/`clients`):

```php
Route::middleware('api.key')
    ->prefix('v1')
    ->group(function () {
        Route::apiResource('products', ProductController::class)->only(['index', 'show']);
        Route::apiResource('clients', ClientController::class)->only(['index', 'show']);
        Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
    });
```

### 5.4 Request: `POST /v1/orders`

**Component**: `App\Http\Requests\Api\StoreOrderRequest` (`Illuminate\Foundation\Http\FormRequest`)
**Docs**: https://laravel.com/docs/12.x/validation#form-request-validation
**Location**: `App\Http\Controllers\Api\OrderController::store(StoreOrderRequest $request)`

`authorize()`: return `true` — authorization is already fully handled by the
`api.key` middleware before the controller runs; no per-field ability check is
needed (matches this project's existing convention of no formal Policy layer,
see [`wms.md`](wms.md) §1).

`rules()` — resolve `$warehouse = $this->attributes->get('warehouse')` (set by
`AuthenticateApiKey`) and reference it in scoped `exists` rules:

```php
return [
    'custom_order_id' => ['nullable', 'string', 'max:255'],
    'order_status_id' => [
        'required',
        'integer',
        Rule::exists('order_statuses', 'id')->where('warehouse_id', $warehouse->id),
    ],
    'discount' => ['nullable', 'numeric'],
    'comments' => ['nullable', 'string'],
    'delivery_date' => ['nullable', 'date_format:Y-m-d'],
    'telephone_number' => ['nullable', 'string', 'max:255'],
    'email' => ['nullable', 'email', 'max:255'],

    'client' => ['required', 'array'],
    'client.email' => ['required', 'email', 'max:255'],
    'client.company' => ['nullable', 'string', 'max:255'],
    'client.vat_number' => ['nullable', 'string', 'max:255'],
    'client.coc_number' => ['nullable', 'string', 'max:255'],
    'client.debtor_number' => ['nullable', 'string', 'max:255'],
    'client.iban_number' => ['nullable', 'string', 'max:255'],
    'client.comments' => ['nullable', 'string'],

    'delivery_name' => ['required', 'string', 'max:255'],
    'delivery_address' => ['required', 'string', 'max:255'],
    'delivery_zipcode' => ['required', 'string', 'max:255'],
    'delivery_region' => ['nullable', 'string', 'max:255'],
    'delivery_city' => ['required', 'string', 'max:255'],
    'delivery_country' => ['required', 'string', 'max:255'],

    'invoice_name' => ['nullable', 'string', 'max:255'],
    'invoice_address' => ['nullable', 'string', 'max:255'],
    'invoice_zipcode' => ['nullable', 'string', 'max:255'],
    'invoice_region' => ['nullable', 'string', 'max:255'],
    'invoice_city' => ['nullable', 'string', 'max:255'],
    'invoice_country' => ['nullable', 'string', 'max:255'],

    'items' => ['required', 'array', 'min:1'],
    'items.*.product_id' => [
        'required',
        'integer',
        Rule::exists('products', 'id')->where('warehouse_id', $warehouse->id),
    ],
    'items.*.quantity' => ['required', 'integer', 'min:1'],
];
```

**Validation**: exactly the rules above — every `delivery_*` field is required
(an order with no shippable address is not a valid e-commerce order); every
`invoice_*` field is optional and falls back to the matching `delivery_*` value
when omitted (see §5.5 step 4); `items.*.product_id`/`quantity` are the only
per-line fields a client may supply — **price, VAT rate, name, barcode, and
reference_code are never accepted from the request body**; they are always
derived server-side from the current `Product`/`VatRate` record at creation
time, exactly mirroring
`Modules\Orders\Filament\Resources\Orders\RelationManagers\OrderProducts\Inputs\ProductSelect`'s
`afterStateUpdated` snapshot behavior in the admin panel. This is a deliberate
security decision: an external, token-authenticated caller must not be able to
set its own price for a line item.

### 5.5 Behavior: `OrderController::store()` → `App\Services\OrderCreationService`

**Component**: new class, `App\Services\OrderCreationService`
**Location**: `app/Services/OrderCreationService.php`
**Called from**: `App\Http\Controllers\Api\OrderController::store(StoreOrderRequest $request): JsonResponse`

Controller method:
```php
public function store(StoreOrderRequest $request, OrderCreationService $service): JsonResponse
{
    $warehouse = $this->resolveWarehouse($request);

    $order = $service->create($warehouse, $request->validated());

    return (new OrderResource($order->load(['products', 'client'])))
        ->response()
        ->setStatusCode($order->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
}
```

`OrderCreationService::create(Warehouse $warehouse, array $data): Order` steps:

1. **Idempotency check**: if `$data['custom_order_id']` is present, look up
   `Order::withoutGlobalScopes()->where('warehouse_id', $warehouse->id)->where('custom_order_id', $data['custom_order_id'])->first()`.
   If found, return it immediately — no further steps run, no duplicate is
   created.
2. **Stock precondition** (before any write): for each item, load its `Product`
   with `stockProduct`. If `! $product->stock_unlimited` and
   `$data['quantity'] > ($product->stockProduct->free_on_stock_quantity ?? 0)`,
   abort the whole request with **422** (see §5.6) — no partial order is
   created.
3. **Find-or-create `Client`** (see [`clients.md`](clients.md)):
   `Client::withoutGlobalScopes()->where('warehouse_id', $warehouse->id)->where('email', $data['client']['email'])->first()`;
   if not found, create one with the warehouse-scoped `email` plus any of
   `company`/`vat_number`/`coc_number`/`debtor_number`/`iban_number`/`comments`
   supplied. **If found, do not overwrite any existing field** on the matched
   `Client` — an order payload must never silently clobber existing CRM data for
   a returning customer.
4. **`DB::transaction()`**:
   a. Create the `Order` with `warehouse_id`, `client_id`, `order_statuses_id => null`
      (deliberately deferred — see §5.1's lifecycle note), `custom_order_id`,
      `discount`, `comments`, `delivery_date`, `telephone_number`, `email`, and
      all `delivery_*`/`invoice_*` fields — where an `invoice_*` field is
      `null`, copy the corresponding `delivery_*` value into it before saving
      (single canonical fallback rule, applied once here, not re-derived
      anywhere else).
      `generated_year_order_id`/`generated_custom_order_id` are set by the
      existing `OrderObserver::creating()` — no change needed there.
   b. For each item: load the `Product` (already loaded in step 2), create an
      `OrderProduct` with `order_id`, `product_id`, `quantity`, and
      `name`/`price`/`barcode`/`reference_code`/`vat_rate_id`/`vat_rate` copied
      from the `Product`/its `vatRate` at this moment — identical snapshot
      fields to what `OrderProductRelationManager`'s `ProductSelect` copies in
      the admin panel (`Inputs/ProductSelect.php`), reused here as the same
      canonical snapshot rule rather than a second, independently-written copy.
   c. `$order->update(['order_statuses_id' => $data['order_status_id']])` — a
      **separate** call from (a), so `wasChanged('order_statuses_id')` is `true`
      and `OrderObserver::updated()` genuinely fires.
5. Return the created `Order`. (`OrderStatusChanged` → `ProcessOrderStatusTransition`
   run after the transaction commits, per `ShouldQueueAfterCommit` — outside
   this method's synchronous return.)

### 5.6 Responses and error cases

| Case | Status | Body |
|---|---|---|
| Order created | `201 Created` | `OrderResource` (existing shape — `id`, `client_id`, `order_status_id`, address fields, `client: {...}`, `items: [OrderProductResource, ...]`, timestamps) |
| Idempotent replay (`custom_order_id` already exists) | `200 OK` | Same `OrderResource` shape, for the **existing** order |
| Validation failure (missing/invalid fields) | `422 Unprocessable Entity` | Laravel's standard `{"message": "...", "errors": {"field": ["..."]}}` shape (unchanged framework default) |
| Insufficient stock on one or more items | `422 Unprocessable Entity` | `{"message": "Insufficient stock for one or more items.", "errors": {"items": [{"product_id": 123, "requested": 5, "available": 2}]}}` — distinct shape from validation errors, since this is a business-rule rejection, not a malformed-input rejection |
| Missing/invalid/expired/IP-blocked API key | `401 Unauthorized` | `{"message": "..."}` — unchanged, already handled by `AuthenticateApiKey` |

No new authorization layer is introduced — the `api.key` middleware plus
warehouse-scoped `exists` validation rules are the complete authorization
surface, consistent with this project's no-Policy-classes convention (see
[`wms.md`](wms.md) §1).

### 5.7 Tests

`tests/Feature/Api/CreateOrderEndpointTest.php` (Pest, `RefreshDatabase`, using
the same manual `Subdomain`→`app()->instance('current_subdomain', ...)`→`Warehouse`→`Product`→`StockProduct`
fixture convention as `tests/Feature/OrderStatusTransitionTest.php` and the new
§3 test files, plus an `ApiKey` fixture with a known plaintext token for the
`X-Api-Key` header):

- Valid payload → `201`, `orders` row created, `order_products` rows match the
  request's `items`, and **price/name/barcode on the created `OrderProduct` come
  from the `Product` record, not from any value the test tries to smuggle in
  the request body** (discriminating test: send a request with a fabricated
  `items.0.price`; assert the persisted `OrderProduct.price` equals the
  `Product`'s real price, not the fabricated one).
- Repeating the exact same request with the same `custom_order_id` → `200`,
  asserts **no second row** was created in `orders` (`assertDatabaseCount`).
- Line item quantity exceeding `free_on_stock_quantity` for a non-`stock_unlimited`
  product → `422` with the structured `items` error shape; asserts **no**
  `orders`/`order_products`/`clients` rows were created (precondition check
  happens before any write).
- `stock_unlimited = true` product with quantity far exceeding stock → `201`
  succeeds (unlimited bypasses the check) — discriminates the flag actually
  being read, not just always passing.
- New client email → a `Client` row is created scoped to the request's
  warehouse; existing client email → **no new `Client` row**, and an existing
  field (e.g. `company`) already set on that client is asserted **unchanged**
  after the order is placed with a different `company` value in the payload
  (discriminates "find or create" from "find or overwrite").
- Chosen `order_status_id` has `generate_picklist = true` → after running the
  queued listener (this project's `QUEUE_CONNECTION=sync` test config means it
  runs inline; assert directly, no `Queue::fake()` needed — matches how
  `OrderStatusTransitionTest` and the §3 tests already work), assert exactly
  one `Picklist` row exists for the new order — this is the discriminating test
  for the §5.1 lifecycle-ordering fix (create-then-update); a naive
  single-`create()` implementation would fail this test since
  `OrderObserver::updated()` would never fire.
- `order_status_id` belonging to a **different** warehouse → `422` (the scoped
  `exists` rule rejects it) — asserts warehouse isolation is enforced at
  validation time, not just by the `warehouse_id` column on the created row.
- Missing/invalid API key → `401`, no rows created (regression check against
  the existing `AuthenticateApiKey` behavior, unchanged by this work).

### 5.8 Explicitly out of scope for this pass

- No `POST /v1/clients` endpoint — client creation only happens as a side
  effect of order creation (§5.1 decision).
- No API endpoint to read `Picklist` data or order-status history — only
  product/client/order GET (unchanged) and order POST are being added.
- No scoped/read-vs-write `ApiKey` permissions — keys remain all-or-nothing per
  warehouse (§5.1 decision).
- No partial fulfillment/backorder handling — insufficient stock rejects the
  whole order.
- No changes to `OrderObserver`, `OrderStatusTransitionService`, or any
  Filament resource — this work only adds a new route, request, controller
  method, and service that all go through the existing, unmodified pipeline.
