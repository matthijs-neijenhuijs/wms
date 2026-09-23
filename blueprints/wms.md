# WMS Filament Blueprint

## Context

This is a Filament Blueprint (per the `planning-filament` skill) for the entire WMS
Filament admin panel. It has two purposes:

1. **Reference specification** of every existing domain (Warehouses/Tenancy, Users,
   Products, Clients, Orders, Picklists) as currently implemented, so the document is
   a complete as-built map of the system, not just the new work.
2. **Full implementable spec** for the one significant gap found during exploration:
   the `PurchaseOrder` feature is scaffolded (migrations, models, Filament resource,
   CSV import) but has no working business logic — nothing wires up barcode
   scanning, nothing increments stock, and nothing implements the domain rule in
   `.github/instructions/warehouse-purchase-orders.instructions.md` that a client
   order's stock shouldn't be reserved while it's covered by an incoming purchase
   order due to arrive before the order's own delivery date.

**Decisions locked in with the user before this blueprint was written**:
- No `Supplier` concept is built in this pass (purchase orders stay supplier-less).
- `purchase_orders_products` gets a new `product_id` FK (resolved at import time),
  so stock mutation is deterministic instead of re-matching by barcode.
- The order/purchase-order stock dependency is **implicit by product + timing** — no
  explicit link table between `order_products` and `purchase_orders`. Accepted
  limitation: allocation is ambiguous under heavy contention for the same incoming
  quantity; the plan resolves this with a deterministic priority rule (earliest
  `orders.delivery_date` served first).
- Scan completion (100% of a PO's rows scanned) is what sets `processed = true` and
  increments stock. `completed` is a separate, manual "Mark Received" confirmation
  with no further stock effect.

**Verification method used for Filament APIs**: `search-docs` was not invoked live
in this planning pass; every Filament API surface below (`#[On]` Livewire
attributes, `Filament\Actions\Action` header actions, `TextEntry`/`TextColumn`
component chains, `FileUpload`, `DatePicker`) is verified against **resolved
installed application source** — the exact same API surface already working in
`ViewPicklist`, `PurchaseOrderResource`, and `ProductResource` in this codebase
(file paths cited throughout). Filament version installed: `filament/filament
v4.13.0` (confirmed via `composer.lock`, not the `^4.0` constraint). Doc URLs
below follow the `https://filamentphp.com/docs/4.x/...` pattern already used in
this project's own `.claude/skills/planning-filament` reference files — these were
not re-fetched live in this pass; treat them as best-effort references, not
verified-live links.

---

## 1. Tenancy & Authorization Foundations (existing — reference only)

These rules apply across every module documented in this Blueprint.

- **Two-layer multi-tenancy**:
  - Subdomain (tenant root): `App\Models\Subdomain` (`id`, `subdomain` unique, `name`
    unique). Resolved per-request by `App\Http\Middleware\IdentifySubdomain`, which
    binds `app()->instance('current_subdomain', $subdomain)`. `App\Models\Scopes\TenantScope`
    applies `where subdomain_id = current_subdomain.id` — used by `Warehouse` and
    `Modules\Users\Models\User`.
  - Warehouse (Filament tenant): configured in `app/Providers/Filament/AdminPanelProvider.php`
    via `->tenant(App\Models\Warehouse::class, ownershipRelationship: 'warehouse', slugAttribute: 'name')`.
    `App\Models\Scopes\WarehouseScope` applies `warehouse_id = Filament::getTenant()->getKey()`
    to every model using the `App\Models\Concerns\BelongsToWarehouse` trait (Product,
    Client, Order, PurchaseOrder, Brand, VatRate, AttributeGroup, ApiKey).
- **No formal Policy classes exist anywhere in this codebase** (confirmed by
  repo-wide search). Authorization is done via: (a) Eloquent global scopes for
  tenant/warehouse isolation, (b) static `canCreate()/canDelete()` overrides on
  Resource classes, (c) domain helper methods on models (e.g.
  `OrderStatus::canEditOrder()/canDeleteOrder()/canModifyProducts()`) consumed
  directly by Filament tables/pages. **New work in this plan follows this same
  convention** — no new Policy classes are introduced.
- `User::canAccessPanel()` always returns `true` — there is no role/permission
  package installed (no `spatie/laravel-permission`), so "Authorization: All
  authenticated users" is the correct spec wherever a module blueprint doesn't say
  otherwise.
- Navigation groups: only one is registered,
  `NavigationGroup::make()->label('Settings')`, in `AdminPanelProvider`. Resources
  either omit `$navigationGroup` (ungrouped: Order, PurchaseOrder, Picklist,
  Product, Brand) or set the exact string `protected static string|UnitEnum|null
  $navigationGroup = 'Settings';` (User, Warehouse, ApiKey, AttributeGroup,
  VatRate). **Do not use `?string` for this property** — that is the likely source
  of the recurring "NavigationGroup error" called out in
  `.github/instructions/warehouse-packages.instructions.md`.

---

## Module Blueprints

Each existing domain and its new work is documented in its own file:

- [`settings.md`](settings.md) — Warehouses, Subdomains, API Keys, VAT Rates, Attributes
- [`users.md`](users.md) — Users
- [`products.md`](products.md) — Products, Stock
- [`brands.md`](brands.md) — Brands
- [`clients.md`](clients.md) — Clients
- [`orders.md`](orders.md) — Orders, Order Statuses, Purchase Orders (implemented), E-commerce Order API (planned)
- [`picklists.md`](picklists.md) — Picklists
- [`platform.md`](platform.md) — AI persistence, cross-cutting findings

The Purchase Order feature and the E-commerce Order Creation API — the two
pieces of new work described in the Context above — are both documented in
[`orders.md`](orders.md), since `PurchaseOrder` lives in `Modules\Orders` and
the order-creation API is entirely order-scoped.
