# Orders
Order flow is driven by two related tables: `orders` and `order_statuses`.

## Description
Orders are a list of products that a client wants to purchase. Each order has a status that defines the workflow behavior of the order. The status is defined in `order_statuses` and is linked to `orders` via `orders.order_statuses_id`. The `order_statuses` table defines the behavior of the order in terms of stock reservation, picklist generation, and other workflow-related flags.

A order has a relation to clients and client addresses. The order also has a relation to `order_products` which stores the line items of the order. Each line item has a relation to `products` and `vat_rates` for price and tax calculations.

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