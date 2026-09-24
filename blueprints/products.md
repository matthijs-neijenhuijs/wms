# Products Module Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Products is the catalog and stock-tracking core of the WMS: what items a
warehouse holds, their identifying data (barcode, reference code) used for
scanning and picking, and the real-time on-hand/reserved/free stock
quantities that every other module (orders, picklists, purchase orders) reads
and updates to decide what can be sold, reserved, or fulfilled.

Covers the `Modules\Products` module: Products and Stock.

## Products

**Model: `Modules\Products\Models\Product`** — table `products` (`id`, `active`
bool default false, `reference_code`, `price` decimal(12,4) nullable,
`product_code`, `stock_unlimited` bool default false, `barcode`, `name`, `weight`,
`height`, `length`, `hs_code`, `country_of_origin`, `description` text not
nullable, `warehouse_id` FK cascade, `vat_rate_id` nullable FK set-null, `brand_id`
nullable FK set-null, `image` nullable, timestamps). Unique per warehouse:
`product_code`, `barcode`, `name`. Uses `BelongsToWarehouse`, `LogsActivity`
(`useLogName('product')`). **Known mismatch** (flag, not part of this plan's
scope): `$fillable` includes `product_category_id` and the model exposes
`productCategory(): BelongsTo`, but no `product_category_id` column or
`ProductCategory` migration exists anywhere in the codebase — likely a dropped or
never-added migration. No `casts()`, no Scout methods currently defined (deviates
from the project's "every model MUST define casts()/getSearchableSettings()" rule
— pre-existing gap, out of scope for this plan unless the user asks to fix it).
Relations: `warehouse()`, `productCategory()`, `brand(): BelongsTo` (`Modules\Brands\Models\Brand`,
see [`brands.md`](brands.md)), `vatRate(): BelongsTo` (`Modules\Settings\Models\VatRate`,
see [`settings.md`](settings.md)), `productAttributes(): HasMany`,
`attributes(): BelongsToMany` (pivot `product_attributes`), `stockProduct(): HasOne`.

**Fixed — History tab was broken**: `ProductResource`'s History tab
originally (`Pages\ManageProductActivities`) set `$relationship = 'activities'`, but
`Product` only has `LogsActivity`, which provides `activitiesAsSubject()`,
not `activities()` — visiting the tab threw `BadMethodCallException`. Fixed
to `$relationship = 'activitiesAsSubject'`. See [`wms.md`](wms.md) §2 for the
full pattern and the shared regression test (`tests/Feature/ActivityLogHistoryTabTest.php`)
that covers this. This page has since been replaced by the combined
`Pages\ManageProductHistory` — see "Combined History tab" below.

**Resource: `Modules\Products\Filament\Resources\Products\ProductResource`** — no
navigation group, icon `Heroicon::OutlinedPhoto`, sub-navigation (General/Stock/History).
- Form (`ProductForm`, one `Section` columns=2): `ActiveToggle`, `ReferenceCodeInput`,
  `BarcodeInput`, `NameInput` (all required `TextInput`/`Toggle`), `VatRateSelect`
  (`->relationship('vatRate','name')`, not required), `BrandSelect` (same shape),
  `AttributesSelect` (`->multiple()->relationship('attributes','name')->preload()->searchable()`).
  No `->unique()` validation on any field (DB-only enforcement).
- Stock sub-page form (`ProductStockForm`, relationship `stockProduct`):
  `OnStockQuantityInput`, `ReservedQuantityInput`, `ReservedOnPicklistsInput` (all
  `TextInput->numeric()->minValue(0)->required()->live()`), `FreeOnStockQuantityInput`
  (`->disabled()->dehydrated()`) — all four call
  `Modules\Products\Filament\Resources\Products\Inputs\FreeStockUpdater::update($set, $get)`
  on change, which computes **the canonical formula**:
  `free_on_stock_quantity = max(0, on_stock_quantity - reserved_quantity - reserved_on_picklists)`.
  This exact formula is duplicated in `App\Services\OrderStatusTransitionService::recalculateFreeStock()`
  (see [`orders.md`](orders.md)) — both must stay in sync if ever changed (they
  are the two independent implementations of the same canonical rule referenced
  throughout the Orders/Purchase Order blueprint).
- Table (`ProductsTable`, eager loads `brand`,`stockProduct`,`attributes.attributeGroup`):
  `ActiveColumn` (`ToggleColumn`), `ReferenceCodeColumn`, `NameWithAttributesColumn`
  (custom HTML column showing brand + attribute badges), `StockSummaryColumn`
  (custom HTML column showing Stock/Res/Pick/Free badges). No filters, no
  sortable/searchable columns except via the importer/relation manager.
- Custom import: `Modules\Products\Filament\Actions\ImportWithXlsxAction` (extends
  `Filament\Actions\ImportAction`, auto-converts `.xlsx` uploads to CSV before
  Filament's import pipeline runs) + `Modules\Products\Filament\Imports\ProductImporter`.
- Barcode image generation: `App\Http\Controllers\ProductBarcodeDownloadController`
  (route `{tenant}/products/{product}/barcode.png`) uses
  `AgeekDev\Barcode\Facades\Barcode::imageType('png')->generate($barcodeValue)`
  (package is actually `ageekdev/laravel-barcode`, not `geekdev/laravel-barcode` as
  named in project docs — flag this naming discrepancy).

## Stock

**Model: `Modules\Products\Models\StockProduct`** — table `stock_products` (`id`,
`product_id` unique FK cascade, `on_stock_quantity`, `reserved_quantity`,
`reserved_on_picklists`, `free_on_stock_quantity`, all integer default 0,
timestamps). Uses `LogsActivity` (log name `stock_product`). No `warehouse_id`
column — scoped transitively through `product_id → products.warehouse_id`.
`free_on_stock_quantity` is a **plain stored column, not a DB-generated column or
accessor** — it must be explicitly recalculated and saved by application code
(see canonical formula above). Reservation/deferral logic against incoming
Purchase Orders is documented in [`orders.md`](orders.md).

**Implemented, then combined — Stock History tab**: `StockProduct`'s own
activity log (`subject_type = StockProduct`) is distinct from `Product`'s
(`subject_type = Product`) — visiting Product's own History tab never showed
stock quantity changes. A `Product::stockActivities(): HasManyThrough`
relation (`Product` → `StockProduct` → `Spatie\Activitylog\Models\Activity`,
filtered to `subject_type = StockProduct`) was added, initially wired to its
own separate "Stock History" sub-navigation tab (`Pages\ManageStockActivities`,
same `ManageRelatedRecords` shape as [`wms.md`](wms.md) §2). That tab has
since been merged into Product's main History tab — see "Combined History
tab" below; `stockActivities()` itself is unchanged and still used by that
combined page. No `ActivityLogResource` tenant-scoping change was needed —
`StockProduct` was already scoped there via `whereHas('product', ...)`.

**Combined History tab**: `Pages\ManageProductHistory` replaces both
`ManageProductActivities` and `ManageStockActivities` with a single "History"
tab showing both a Product's own field changes and its `StockProduct`'s
quantity changes together, newest first. There is no single Eloquent
relationship spanning both `subject_type` values, so unlike every other
module's History tab (which are all `ManageRelatedRecords` pages per
[`wms.md`](wms.md) §2), this is the one exception: it implements
`Filament\Tables\Contracts\HasTable` + `InteractsWithRecord` directly (the
same composition `ListRecords`/`EditRecord` are built from) with a manually
combined `Activity::query()->where(subject=Product)->orWhere(subject=StockProduct)`,
reusing `AlizHarb\ActivityLog\Resources\ActivityLogs\Tables\ActivityLogTable::configure()`
for its base columns/filters/actions.

For stock-quantity rows specifically, three extra columns
(`App\Filament\Resources\ActivityLogs\Columns\StockMutationColumns`) show a
increase/decrease direction icon, the signed quantity delta, and a link to
the Order or Purchase Order that caused the change. This requires the
mutation to actually be attributable, which the raw `StockProduct` dirty-diff
log never was (see [`orders.md`](orders.md) "Stock mutation attribution").
