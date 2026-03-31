# Orders
Order flow is driven by two related tables: `orders` and `order_statuses`.

## Schema Facts
- `orders` stores customer/order header data (client, generated IDs, invoice/delivery fields, and flags like `completed`, `picked`, `cancelled`, `delivered`, `on_hold`).
- `orders.order_statuses_id` links to `order_statuses` and is nullable.
- `order_products` stores line-item snapshots (`name`, `quantity`, `price`, `vat_rate`, `barcode`, `reference_code`) and optional links to `products` and `vat_rates`.
- Pick execution is tracked in `picklists`, `picklists_products`, and `picklist_failed_products`.

## Status Behavior
The workflow booleans that trigger business behavior are defined on `order_statuses` (not on `orders`):
- `generate_picklist`
- `reserve_stock`
- `reduce_stock`
- `concepted`
- `completed`
- `on_hold`
- `delivered`
- `cancelled`

When implementing status transitions, use `order_statuses` as the source of truth and keep the `orders` boolean flags synchronized with the selected status.

## Identifier Rules
- `orders.generated_custom_order_id` is globally unique.
- `orders.custom_order_id` is unique per warehouse (`unique(warehouse_id, custom_order_id)`).

## Implementation Notes
- Always scope order queries by `warehouse_id`.
- Keep all stock-reservation and stock-reduction logic aligned with status booleans and picklist completion.
- There is no `purchase_orders` migration in this project; do not assume purchase-order persistence exists unless a migration is added first.