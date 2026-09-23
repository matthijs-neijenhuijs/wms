# Brands Module Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Brands classify products by manufacturer/label, giving the catalog a simple
grouping used for organizing and filtering products — no warehouse operations
(stock, picking, orders) depend on it directly.

Covers the `Modules\Brands` module.

**Model: `Modules\Brands\Models\Brand`** — table `brands` (`id`, `active` bool,
`reference_code`, `name`, `description` text not nullable, `warehouse_id` FK
cascade, timestamps; unique per warehouse: `reference_code`, `name`). Resource
`Modules\Brands\Filament\Resources\Brands\BrandResource` — `$navigationGroup = 'Settings'`,
no icon; fields Active/ReferenceCode/Name/Description; plain CRUD, no
sortable/searchable columns, no `->unique()` validation.
