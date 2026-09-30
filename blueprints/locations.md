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

## Warehouse floor map

Gives Stock Locations a spatial representation: each warehouse gets a
fixed floor size, each location gets a position/size rectangle on that
floor, and a canvas page renders every placed location as a square
(labelled with name + rank) connected by a line in walking-order sequence,
so staff can see exactly where to walk and in what order.

**`App\Models\Warehouse`** gained `floor_width`/`floor_height`
(`decimal(8,2)`, nullable — null means "not yet set", the map page then
shows a prompt to set them instead of rendering). Added to `$fillable` and
a new `casts()` method (this model previously had none). Already covered
by the existing `logFillable()` History tab — no activity-log changes
needed.

**`Modules\Products\Models\StockLocation`** gained `x`/`y`/`width`/`height`
(`decimal(8,2)`, nullable each — a location is "unplaced" until a manager
drags it onto the map; all four are always written together, never
partially). Added to `$fillable`/`casts()`. A completed drag (move or
resize) adds one History entry via the existing `logFillable()` config —
acceptable since the persistence call only fires once per drag gesture
(on release), not continuously during the drag.

**`WarehouseResource`** form gained two fields, `FloorWidthInput`/
`FloorHeightInput` (`TextInput`, nullable, `->numeric()->step(0.01)
->minValue(0.1)`), after `CurrencySelect`. No real-world unit is enforced
or labelled (no "m"/"ft") — the numbers only need to be internally
consistent with the locations placed within them.

**New standalone page: `App\Filament\Pages\WarehouseMap`** (top-level nav,
no group, icon `Heroicon::OutlinedMap`) — the first `Filament\Pages\Page`
subclass in this app that isn't `Dashboard`. **Registered via an explicit
`->pages([WarehouseMap::class])` call in `AdminPanelProvider`, not
`discoverPages()`** — `app/Filament/Pages/` already contains two broken,
empty stub files (`Profile.php`, `ProfileModal.php`, pre-existing and
unrelated to this feature) that a directory-scanning `discoverPages()`
call would fatal-error on trying to reflect. `AdminPanelProvider` had no
page registration of any kind before this (confirmed
`App\Filament\Pages\Dashboard`'s widget-overriding subclass was itself
dead code, never wired in — a separate pre-existing gap, not fixed here).

Data loads through the page's own `StockLocation::query()`/`Warehouse`
tenant accessor — both already globally scoped to the current Filament
tenant via the existing `BelongsToWarehouse`/`WarehouseScope` machinery, so
the map needs no manual tenant filtering, and a forged location id from
another warehouse simply 404s through `findOrFail()`.

**Editing is drag-only**: there are no numeric X/Y/Width/Height fields on
the Stock Location form. The map's canvas is hand-written vanilla JS
(Canvas 2D + Pointer Events — no drag/canvas library is installed in this
app) that lets a manager drag a square to move it, drag its corner to
resize it, or drag an unplaced location in from a sidebar list onto the
floor; on release, it calls the page's `saveLocationLayout(int $id, float
$x, float $y, float $width, float $height)` method directly via
`$wire.call(...)` (Livewire 3's direct-call JS API — the first use of that
specific pattern in this app; the existing barcode-scanner script instead
uses the `Livewire.dispatch()`/`#[On(...)]` browser-event round-trip).
Server-side, that method clamps width/height to a 0.1 minimum and clamps
x/y so a square can never extend past the warehouse's floor bounds (when
set).

The map also draws the walking-order: placed squares are connected by a
line/arrow in ascending `rank` order, with each square labelled by its
rank number — a manager can visually sanity-check that the picking route
this feature already computes (see "Picklist walking-order integration"
above) matches the physical floor layout.

No browser/DOM testing tool (Dusk, etc.) exists in this app, so the canvas
drawing and drag interactions are not unit-tested — only the
Livewire-reachable `saveLocationLayout()` method (persistence, clamping,
tenant-scoping) and the two new Warehouse form fields are covered by
automated tests; the drag/canvas behavior itself is verified manually.

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
