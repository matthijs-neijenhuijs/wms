# Settings Module Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Settings holds the shared configuration every other module depends on: which
warehouses (physical locations) exist and which subdomain/tenant they belong
to, the API keys external systems use to integrate with a warehouse, the VAT
rates applied to products and orders, and the attribute/attribute-group
taxonomy used to describe product variants (e.g. size, color).

Covers the `Modules\Settings` module: Warehouses, Subdomains, API Keys, VAT
Rates, and Attributes.

## Warehouses, Subdomains, API Keys

**Model: `App\Models\Subdomain`** — table `subdomains` (`id`, `subdomain` unique,
`name` unique, timestamps). Relations: `users(): HasMany`, `warehouses(): HasMany`.
No Filament resource exists (managed only via seeders/DB).

**Model: `App\Models\Warehouse`** — table `warehouses` (`id`, `subdomain_id` FK
cascade, `name`, `currency` default `EUR`, `order_statuses_id_completed_picklist`
nullable FK → `order_statuses`, timestamps). Relations: `subdomain(): BelongsTo`,
`users(): BelongsToMany` (pivot `user_warehouses`), `completedPicklistOrderStatus(): BelongsTo`.
Uses `TenantScope` (not `BelongsToWarehouse` — it *is* the warehouse).

**Resource: `Modules\Settings\Filament\Resources\Warehouses\WarehouseResource`**
(`app-modules/settings/src/Filament/Resources/Warehouses/`)
- `$navigationGroup = 'Settings'`, `$isScopedToTenant = false`, `getEloquentQuery()`
  scoped to `current_subdomain`.
- Fields: `NameInput` (`Filament\Forms\Components\TextInput`, required, max:255),
  `CurrencySelect` (`Filament\Forms\Components\Select`, options
  EUR/USD/GBP/JPY/CHF/CAD/AUD/CNY, required, default `EUR`, searchable),
  `CompletedPicklistStatusSelect` (`Select`, `->relationship('completedPicklistOrderStatus','name')`,
  nullable, placeholder "None").
- Columns: `NameColumn`, `CurrencyColumn`, `DomainColumn` (`subdomain.name`),
  `CreatedAtColumn`, `UpdatedAtColumn` — all `Filament\Tables\Columns\TextColumn`.
- Actions: `EditAction`, `DeleteAction` (row); `CreateAction` (header, on
  `ListWarehouses`); `BulkActionGroup([DeleteBulkAction])`.
- Pages: `ListWarehouses`, `CreateWarehouse`, `EditWarehouse` (all redirect to index).

**Model: `App\Models\ApiKey`** — table `api_keys` (`id`, `warehouse_id` FK cascade,
`name`, `key_hash` unique(64), `is_active` default true, `last_used_at`,
`expires_at`, `allowed_ips` json nullable, timestamps). Uses `BelongsToWarehouse`.
Enforced by `App\Http\Middleware\AuthenticateApiKey` (header/bearer key → SHA-256
match → active/expiry/IP-allowlist/tenant checks).

**Resource: `Modules\Settings\Filament\Resources\ApiKeys\ApiKeyResource`**
(`$navigationGroup = 'Settings'`, icon `Heroicon::OutlinedRectangleStack`) — fields
Name/IsActiveToggle/AllowedIpsInput/ExpiresAtInput; columns
Name/IsActive/LastUsedAt/ExpiresAt/CreatedAt (sorted `created_at desc`); row
`EditAction` only, bulk delete.

## VAT Rates

**Model: `Modules\Settings\Models\VatRate`** — table `vat_rates` (`id`, `name`,
`rate` decimal(12,4) — **field is `rate`, not `percentage`**, `warehouse_id` FK
cascade; unique per warehouse: `name`, `rate`). Resource `VatRateResource`
(`$navigationGroup = 'Settings'`) — fields Name/Rate (neither marked `->required()` —
flag as a likely oversight, out of scope to fix unless asked), plain CRUD.

## Attributes

**Models: `Modules\Settings\Models\{Attribute,AttributeGroup}`,
`Modules\Products\Models\ProductAttribute`** — `attribute_groups` (`id`, `name`,
`warehouse_id` nullable FK cascade), `attributes` (`id`, `name`,
`attribute_group_id` FK cascade, no `warehouse_id`), `product_attributes` pivot
(`id` via `increments`, `product_id` FK cascade, `attribute_id` FK cascade, **no
unique constraint on the pair** — duplicates possible, flag only). There is no
separate `AttributeResource`; individual attributes are managed exclusively via
`Modules\Settings\Filament\Resources\AttributeGroups\RelationManagers\AttributesRelationManager`
under `AttributeGroupResource` (`$navigationGroup = 'Settings'`).
