# Stock Locations Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Stock Locations let a warehouse's physical layout (bins/shelves/aisles) be
modeled and ranked, so generated picklists can be sorted into a single
walking route instead of the previous placeholder alphabetical-by-barcode
order — see [`picklists.md`](picklists.md).

Covers `Modules\Products\Models\StockLocation` and the
`stock_location_product` pivot (lives in the `Modules\Products` module,
alongside `Product`/`StockProduct`, rather than a new module — the pivot's
`quantity` is placement data used only to choose a pick location, not a
second source of truth for stock levels).

**Model: `Modules\Products\Models\StockLocation`** — table `stock_locations`
(`id`, `warehouse_id` FK cascade, `parent_id` nullable FK self set-null,
`name`, `rank` integer, timestamps; unique per warehouse on `rank`). Uses
`BelongsToWarehouse`, `LogsActivity` (`useLogName('stock_location')`).
Relations: `warehouse()`, `parent()`/`children()` (self, `parent_id` —
organizational nesting only, e.g. "Shelf B is inside Aisle 3"; nesting does
**not** affect pick order), `products(): BelongsToMany` (pivot
`stock_location_product`, `withPivot('quantity')`).

**Rank is flat per warehouse**, not scoped per parent: every location, at
any nesting depth, has one integer unique within its warehouse
(`unique(['warehouse_id', 'rank'])`). `rank` auto-assigns to
`max(rank for warehouse) + 1` on create (`StockLocation::booted()`
`creating` hook) when not explicitly set, and is otherwise only changed via
`StockLocationsTable`'s `->reorderable('rank')` drag-and-drop.

**Pivot: `stock_location_product`** (`id`, `product_id` FK cascade,
`stock_location_id` FK cascade, `quantity` integer default 0, timestamps;
unique per `[product_id, stock_location_id]`). No dedicated Pivot model
class. `Product::stockLocations(): BelongsToMany` is the inverse side.
**`quantity` here is informational placement data only** — it is not
reconciled with `StockProduct.on_stock_quantity`/`reserved_quantity`/
`free_on_stock_quantity` (see [`products.md`](products.md)); a deliberate
scope decision so this feature doesn't touch
`App\Services\OrderStatusTransitionService::reduceStock()`/
`adjustStockLevels()`/`recalculateFreeStock()`.

**Resource: `Modules\Products\Filament\Resources\StockLocations\StockLocationResource`**
— no navigation group (matches Product/Brand/Picklist), icon
`Heroicon::OutlinedMapPin`, sub-navigation (General/History, same
`SubNavigationPosition::Top` + `ManageRelatedRecords` History-tab pattern as
[`wms.md`](wms.md) §2). Form: `ParentSelect` (self `Select`, `ignoreRecord:
true`, plus a closure validation rule rejecting the record's own id *and*
any of its descendants — `ignoreRecord` alone only excludes the record
itself, not a cycle further down the tree), `NameInput` (required, max
255). No `rank` field in the form (system/drag-assigned only) and no
`->unique()` Filament validation on `name` (DB-only enforcement, matching
`ProductForm`'s existing convention). Table: Name/Parent/`products_count`
columns, `->reorderable('rank')`, `->defaultSort('rank')`.

**`ProductResource` gets a new `StockLocationsRelationManager`**
(`stockLocations`, belongsToMany) so a product's location assignments with
quantity can be attached/edited/detached — an `AttachAction` with a
`quantity` pivot field alongside the record select, and `EditAction`
editing only `quantity` (per [`relationships.md`](../.claude/skills/planning-filament/relationships.md)'s
guidance: pivot data with an editable field beyond the association belongs
in a `RelationManager`, not a plain multi-select).

## Picklist walking-order integration

**New: `App\Services\StockLocationAllocator::allocate(int $productId, int $quantity): array`**
— resolves which location each unit of a product's picklist rows should be
assigned to: ascending by the location's `rank`, spilling to the
next-lowest-rank location once one is exhausted (reads
`stock_location_product.quantity`, **does not mutate it** — allocation is
read-only, consistent with locations being informational). A unit no
location has recorded quantity for gets `null` (unlocated).

**`OrderStatusTransitionService::generatePicklist()`** calls the allocator
once per `OrderProduct` and writes the resolved `stock_location_id` onto
each new `PicklistProduct` row (new nullable FK column on
`picklists_products`, `nullOnDelete()` so deleting a `StockLocation` later
never destroys picklist history — rows just become "unlocated"
retroactively).

**Location assignment is frozen at generation time; display order is
always live.** A `PicklistProduct.stock_location_id` never changes after
the row is created — reassigning a product to a different location only
affects picklists generated afterward. But
`Picklist::products(): HasMany` always sorts by the assigned location's
*current* `rank` via a live subquery
(`orderBy(StockLocation::select('rank')->whereColumn('id', 'picklists_products.stock_location_id'))`),
unlocated rows last (`orderByRaw('stock_location_id is null')`), barcode as
the final tiebreak — so re-ranking the warehouse layout immediately
reorders every not-yet-completed picklist that references those locations,
without touching a single `PicklistProduct` row.
`Picklist::productsCombined()` (barcode-grouped, used elsewhere) is
untouched — it's not read by the picking UI.

**`ViewPicklist`'s `products-table.blade.php`** gained a "Location" column
(first column) reading `$product->stockLocation?->name ?? '—'` — the only
UI surface that shows staff where to walk for each pick.

## Known adjacent bug (found, not fixed by this work)

`FailedProductsTable`/`ProductsTable`'s infolist `Entry::getState()`
resolution (`Filament\Schemas\Components\Concerns\HasState::getConstantState()`)
treats an **empty relation `Collection` as `blank()`** and falls back to a
`null` default state, which breaks `FailedProductsTable`'s `@forelse` for
*any* picklist with zero failed scans (the common case) — a pre-existing
bug unrelated to stock locations, discovered while writing
`tests/Feature/ViewPicklistLocationDisplayTest.php` (worked around there by
seeding one `PicklistFailedProduct` row rather than fixed, since it isn't
this feature's concern).
