# Users Module Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Users are the staff who operate the WMS — warehouse managers and operators who
sign in to the admin panel to manage products, process orders, and run
picklists. Each user belongs to a subdomain (company/tenant) and is assigned
to one or more specific warehouses they're allowed to work in.

Covers the `Modules\Users` module.

**Model: `Modules\Users\Models\User`** — table `users` (`id`, `subdomain_id` FK
cascade, `name`, `email` unique, `email_verified_at`, `password`, MFA columns,
`has_email_authentication` bool, dead `super_user` bool column, timestamps).
Implements `FilamentUser` (`canAccessPanel()` → always `true`), `HasTenants`
(`getTenants()` scoped to current subdomain's warehouses, `canAccessTenant()`
checks `warehouses()->whereKey($tenant)->exists()`), TOTP app-auth and email-auth
MFA contracts. Relations: `subdomain(): BelongsTo`, `warehouses(): BelongsToMany`
(pivot `user_warehouses`: `id`, `user_id` nullable FK, `warehouse_id` nullable FK).

**Resource: `Modules\Users\Filament\Resources\Users\UserResource`**
- `$navigationGroup = 'Settings'`, `$isScopedToTenant = false`, scoped to current
  subdomain in `getEloquentQuery()`.
- Fields: `SubdomainSelect` (`Select`, options from `Subdomain::pluck('name','id')`,
  required, searchable), `NameInput` (`TextInput`, required, max:255),
  `EmailInput` (`TextInput`, `->email()`, required, max:255,
  `->unique(ignoreRecord: true)`), `PasswordInput` (`TextInput`, `->password()->revealable()`,
  required only on create, `dehydrateStateUsing` → `Hash::make`),
  `WarehousesSelect` (`Select`, `->relationship('warehouses','name')`, multiple,
  searchable, preload, scoped to current subdomain).
- Columns: `NameColumn`, `EmailColumn`, `SubdomainColumn`, `CreatedAtColumn`,
  `UpdatedAtColumn`.
- Actions: `EditAction` only on row (no row `DeleteAction`); `CreateAction` header;
  `DeleteAction` header on Edit page; `BulkActionGroup([DeleteBulkAction])`.
