# Picklists Module Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Picklists are the warehouse floor's to-do list: for each order that needs
items physically pulled from stock, the system generates a picklist that
staff work through by barcode-scanning each product, so the warehouse knows
exactly what to collect and can track scan progress in real time.

Covers the `Modules\Picklists` module.

**Models: `Modules\Picklists\Models\{Picklist,PicklistProduct,PicklistFailedProduct}`**
— `picklists` (`id`, `order_id` FK cascade, `warehouse_id` FK cascade, `completed`
bool default false, `back_order` bool default false, `comments` nullable,
`generated_year_picklist_id`, `generated_custom_picklist_id` unique per warehouse),
`picklists_products` (`id`, `picklist_id` FK cascade, `show_for_supplier` bool,
`barcode`, `reference_code` nullable, `color`/`size` nullable, `product_title`,
`scanned` bool default false), `picklist_failed_products` (`id` via `increments`,
`picklist_id` FK cascade, `barcode`/`reference_code` nullable,
`total_quantity_scanned` bigint default 0, `color`/`size`/`product_title`
nullable). **This exact schema/model shape was cloned to create the
`purchase_orders`/`purchase_orders_products`/`purchase_order_failed_products`
tables** — see [`orders.md`](orders.md), which completes that clone by porting
the scanning logic too.

**Resource: `Modules\Picklists\Filament\Resources\Picklists\PicklistResource`** —
no navigation group, icon `Heroicon::OutlinedClipboardDocumentList`,
`canCreate() → false` (system-generated only, via
`OrderStatusTransitionService::generatePicklist()`, see [`orders.md`](orders.md)).
`getPages()` registers only `index`/`view` (no create/edit route, though the page
classes exist unused on disk). Table: `GeneratedCustomPicklistIdColumn`, `OrderGeneratedCustomOrderIdColumn`
(links to the parent Order), `ProductsCountColumn`, `ScannedProductsCountColumn`.
Row action: `ViewAction` only.

**Scanning implementation (the pattern ported to Purchase Orders, see
[`orders.md`](orders.md))** —
`Modules\Picklists\Filament\Resources\Picklists\Pages\ViewPicklist`:
```php
#[On('picklist-barcode-scanned')]
public function handleScannedBarcode(string $barcode): void
{
    $this->scanProductBarcode($barcode);
}
```
`scanProductBarcode()` looks up
`PicklistProduct::where('picklist_id', $this->record->id)->where('barcode', $barcode)->orderBy('scanned')->first()`;
if not found or already `scanned` → creates/increments a `PicklistFailedProduct`
row (matched by `picklist_id` + `barcode`) and shows a danger `Notification`;
otherwise sets `scanned = true`, saves, and dispatches
`picklist-product-scan-success` (row highlight).

The scanner input itself is **not a Filament component** — it's a vanilla `<script>`
block in the custom infolist Blade view
(`app-modules/picklists/resources/views/filament/infolists/components/products-table.blade.php`)
using the `onscan.js` npm library (registered globally via `window.onScan` in
`resources/js/app.js` and as a Filament asset
`Js::make('onscan', base_path('node_modules/onscan.js/onscan.min.js'))` in
`AdminPanelProvider`). It attaches a global keyboard-wedge listener to `document`
that detects hardware-scanner-speed keystrokes and dispatches
`window.Livewire.dispatch('picklist-barcode-scanned', { barcode })`.

**Important existing gap, not fixed by this plan** (documented for awareness):
picklist scan completion never sets `Picklist.completed = true` and never touches
`StockProduct` (see [`products.md`](products.md)) — stock reduction is driven
solely by the *order's* status flags (`reduce_stock`), fully decoupled from
picklist scan progress. **The Purchase Order work in [`orders.md`](orders.md)
deliberately does something different** (scan completion *does* drive
`processed` + stock) per the user's explicit decision — this is a known, accepted
asymmetry between the two features, not an oversight.
