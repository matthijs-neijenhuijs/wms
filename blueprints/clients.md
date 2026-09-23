# Clients Module Blueprint

> Part of the [WMS Blueprint](wms.md). See that file for tenancy/authorization
> rules shared across all modules.

## Purpose

Clients represent the customers placing orders — their contact, billing, and
delivery details. A client can have several stored addresses
(`client_addresses`), with one marked as their default delivery address and
one as their default bill address. Every order is tied to a client so the
warehouse knows who to invoice and where to ship, and repeat customers are
matched by email rather than re-entered.

**Order addresses are a copy, not a live reference**: an `Order` never points
at a `ClientAddresses` row by FK — it has its own `delivery_*`/`invoice_*`
columns (see [`orders.md`](orders.md)) that must be populated with a snapshot
of the address the client selected at the time the order was created. This
matters because a client's stored address can change later (they move,
correct a typo); an order must keep shipping to/billing the address that was
current when it was placed, not silently follow a future edit to the client's
address book. This mirrors the snapshot convention already used for
`OrderProduct` (copies `Product` fields rather than referencing them live —
see [`orders.md`](orders.md)).

Covers the `Modules\Clients` module.

**Models: `Modules\Clients\Models\{Client,ClientAddresses}`** — `clients` (`id`,
`active` bool, `email`, `vat_number`, `coc_number`, `debtor_number`,
`iban_number`, `comments` nullable, `company` nullable,
`delivery_client_address_id`/`bill_client_address_id` nullable FK → `client_addresses`
set-null, `warehouse_id` FK cascade; unique per warehouse: `email`),
`client_addresses` (`id` via `increments`, `client_id` FK cascade, `company`,
`gender` enum(male,female), `initials`, `name`, `address`, `zipcode`, `city`,
`region`, `country`, `telephone_number`, timestamps). `Client` uses
`BelongsToWarehouse` + `LogsActivity`; relations `warehouse()`, `addresses(): HasMany`,
`clientDeliveryAddress()`/`clientBillAddress(): BelongsTo`, `orders(): HasMany`.
Not deep-dived further in this pass (out of scope — no changes needed here); this
entry exists for completeness of the system map. **This is the pattern any future
Supplier model should mirror**, per the option not chosen in the Orders/Purchase
Order blueprint (see [`orders.md`](orders.md)).

The e-commerce Order Creation API (see [`orders.md`](orders.md) §5) finds or
creates a `Client` by `email` scoped to the warehouse as a side effect of order
creation — no changes are made to this module itself.

**Verified gap (documentation accuracy, not new work in this pass)**: as of
this checkout, nothing in the codebase actually copies a `ClientAddresses`
record onto an `Order`. The Filament admin panel's `OrderForm` has no address
fields at all (only `OrderStatusSelect`/`ClientSelect` — see
[`orders.md`](orders.md)), so orders created there get `null` delivery/invoice
fields. The E-commerce API's `OrderCreationService` populates
`delivery_*`/`invoice_*` directly from whatever the API caller sends in the
request body — it never reads `Client::clientDeliveryAddress()`/
`clientBillAddress()`. The "copy the client's selected address onto the
order" rule described above is the intended/correct behavior, not something
either code path currently implements.
