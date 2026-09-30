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
`StockLocationsTable`'s `->reorderable('rank')` drag-and-drop **or** the
Warehouse Map's **Edit Route** mode (see "Walking route" under
"Warehouse floor map" below). Both write the same `rank`, so the map route,
the Stock Locations table order and picklist sorting are always the same
order.

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
— in the **Settings** navigation group (`$navigationGroup = 'Settings'`,
same as `WarehouseResource`), icon
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

**New standalone page: `App\Filament\Pages\WarehouseMap`** (in the
**Settings** navigation group, next to Warehouses and Stock Locations, icon
`Heroicon::OutlinedMap`) — the first `Filament\Pages\Page`
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

**Floor size is editable from the map page itself**, not only from
`WarehouseResource`: the page has an `editFloorSize` header action
(`Heroicon::OutlinedArrowsPointingOut`, `md` modal) reusing
`FloorWidthInput`/`FloorHeightInput` with `->required()` added on top (the
resource form keeps them nullable). When dimensions are still null, the
empty-state notice renders the same action inline
(`{{ $this->editFloorSizeAction }}`) instead of linking away to the
Warehouse edit page. On submit, `updateFloorSize(float $floorWidth, float
$floorHeight)` saves both values and then **re-clamps every placed
location** in the tenant so none extends past the new bounds: width/height
shrink to at most the floor size, then x/y are pulled back so the rectangle
fits (unplaced locations are left alone). Each changed location gets its
normal History entry. The action then sends a success notification and
redirects to the page (`navigate: true`), because the canvas is
`wire:ignore`d and has to be rebuilt from fresh data.

**Editing is drag-only**: there are no numeric X/Y/Width/Height fields on
the Stock Location form. The map's canvas is hand-written vanilla JS
(`resources/js/warehouse-map.js`, Canvas 2D + Pointer Events — no
drag/canvas library is installed in this app, registered as its own Vite
input and loaded with `@vite` inside the page view). Interactions:

- **Select**: click a square. The selected square gets a primary-colored
  outline, a resize handle in its bottom-right corner and a red × button in
  its top-right corner. Click empty floor or press Esc to deselect. The
  canvas is focusable (`tabindex="0"`) so it can receive key presses.
- **Move / resize**: drag the square, or drag its bottom-right corner. The
  cursor changes to `move`/`nwse-resize`/`pointer` depending on what is
  under it.
- **Place**: drag an item from the **Unplaced Locations** sidebar list onto
  the floor. It gets a default size of ~60 canvas pixels converted to floor
  units (a fixed 1×1 unit default was invisible on large floors, e.g. 3px
  on a 200×200 floor) and is selected on drop.
- **Remove from map**: the × button, the Delete/Backspace key, or the
  **Remove From Map** button in the sidebar's **Selected Location** panel.
  This clears the location's `x`/`y`/`width`/`height` (it becomes unplaced
  and reappears in the sidebar) — **it never deletes the `StockLocation`
  record**; deleting stays on `StockLocationResource`.

The page calls two methods directly via `$wire.call(...)` (Livewire 3's
direct-call JS API — the first use of that pattern in this app; the
existing barcode-scanner script instead uses the
`Livewire.dispatch()`/`#[On(...)]` browser-event round-trip):

- `saveLocationLayout(int $id, float $x, float $y, float $width, float
  $height)` — on release of a move/resize/place gesture (only if the
  pointer actually moved). Clamps width/height to a 0.1 minimum and x/y so
  a square can never extend past the floor bounds (when set).
- `removeLocationFromMap(int $id)` — nulls all four layout columns.

Both call `$this->skipRender()`: the JS already holds the new state, and a
Livewire re-render would otherwise morph the page and wipe the
JS-managed sidebar/canvas state. The whole map area is also wrapped in
`wire:ignore` for the same reason. Both look the location up through the
tenant-scoped `findOrFail()`, so a forged id from another warehouse 404s.

**Layout**: a two-column grid — the **Floor Plan** section (heading shows
the floor size, a row of icon hints: click to select, drag to move, drag
corner to resize, × / Delete to remove) on the left, and a 20rem right
sidebar with the **Selected Location** panel (rank badge, name, remove
button; hidden while nothing is selected) and the **Unplaced Locations**
panel (explanatory callout that items are dragged onto the floor and that
removed items come back here, one row per location with a drag-handle icon
and rank badge, and an "All locations are placed on the map." empty state).
Sidebar rows are rendered server-side for every location and shown/hidden
by the JS via the `hidden` attribute. The floor is drawn with a light
grid, and the canvas colors follow Filament's dark mode (`.dark` on
`<html>`) and primary/danger color variables.

**Styling uses a scoped `<style>` block with `wm-*` classes, not Tailwind
utilities**: the admin panel has no custom Filament theme, so Tailwind
classes in app views are never compiled into Filament's CSS and silently do
nothing. The styles use Filament's CSS color variables (`--primary-500`,
`--gray-200`, …) so they follow the panel's colors. Icons use
`<x-filament::icon>` with `IconSize` (which Filament's CSS does style).
Adding a custom theme would allow switching to Tailwind classes.

The map also draws the walking-order: placed squares are connected by a
dashed line/arrow in ascending `rank` order, with each square labelled by
its rank number — a manager can visually sanity-check that the picking
route this feature already computes (see "Picklist walking-order
integration" above) matches the physical floor layout.

### Walking route

The route is **not** tied to creation order: a manager can set it on the
map (e.g. walk 1 → 3 → 2). The route **is** `rank` — there is no separate
map-only order — so saving a route immediately re-sorts every
not-yet-completed picklist via `Picklist::products()`'s live rank subquery.

**UI**: a **Walking Route** section at the top of the sidebar lists the
placed locations in route order (rank badge + name), with an **Edit Route**
button (disabled with fewer than two placed locations). Edit Route
switches the canvas into route mode:

- Clicking a placed square appends it to a draft route; clicking the last
  one again removes it. Undo, Backspace or Delete also remove the last one.
  Esc or **Cancel** throws the draft away.
- While editing, the dashed line follows the draft order, badges show the
  draft position, squares not yet clicked are drawn faded without a number,
  and a banner at the top of the canvas says "Click locations in walking
  order" (translated text passed in through the canvas'
  `data-route-hint` attribute). The sidebar list shows the draft, then the
  not-yet-clicked locations greyed out with a "–" badge.
- Move/resize/select/remove and dragging from the Unplaced list are
  disabled in route mode.
- **Save Route** calls `saveRoute(array $locationIds): array` and applies
  the returned `[id => rank]` map to the canvas data and to the rank badges
  in the Unplaced list (`[data-rank-label]`).

**`saveRoute()`**: puts the given locations first, in the given order,
followed by **all other locations of the warehouse** (unclicked placed
ones, unplaced ones and nested sub-locations) in their current rank order,
then renumbers everything 1..n. Ids are de-duplicated and looked up
through the tenant-scoped query; any unknown or foreign-warehouse id throws
`ModelNotFoundException` before anything is written. Because of the
`unique(['warehouse_id', 'rank'])` index (a signed `integer` column), ranks
can't simply be swapped one row at a time. Inside one transaction, the
method first moves every location whose rank changes to its **negative**
target rank with a raw query update (no model events, so no History entry),
then sets the final positive ranks through the model's `update()`. That
second step writes exactly one History entry per location whose rank
actually changed (old → new). Like the other map methods it calls
`skipRender()`.

No browser/DOM testing tool (Dusk, etc.) exists in this app, so the canvas
drawing and drag interactions are not unit-tested — only the
Livewire-reachable parts are covered in `tests/Feature/WarehouseMapTest.php`:
`saveLocationLayout()` (persistence, clamping, tenant-scoping),
`removeLocationFromMap()` (clears layout without deleting, tenant-scoping),
`saveRoute()` (clicked order first, remaining locations keep their
relative order, a foreign id is rejected without changing any rank),
the `editFloorSize` action (saves, requires both values, re-clamps placed
locations when the floor shrinks) and the server-rendered sidebar; the two
Warehouse form fields are covered by
`tests/Feature/WarehouseFloorDimensionsTest.php`. The drag/canvas behavior
itself is verified manually.

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
