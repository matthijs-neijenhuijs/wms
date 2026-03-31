Products belong to a warehouse and are persisted in `products`.

Stock is persisted in `stock_products` with one row per product (`unique(product_id)`). Use the exact migration field names:

- `on_stock_quantity`: physical stock currently present.
- `reserved_quantity`: stock reserved for orders.
- `reserved_on_picklists`: stock reserved while actively on picklists.
- `free_on_stock_quantity`: available stock after reservation logic.

## Product Constraints
- Product uniqueness is warehouse-scoped:
	- `unique(warehouse_id, product_code)`
	- `unique(warehouse_id, barcode)`
	- `unique(warehouse_id, name)`
- Optional relations:
	- `vat_rate_id` -> `vat_rates.id`
	- `brand_id` -> `brands.id`

## Implementation Notes
- Always scope product and stock operations to `warehouse_id`.
- Keep stock calculations deterministic and derived from order + picklist transitions.
- Do not introduce alternative stock column names in code or prompts; use migration-aligned names only.

