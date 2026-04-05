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


## Order Statuses events descrtiption
When an order status is updated, the following events should be triggered based on the boolean flags of the new status:
- If `generate_picklist` is true, generate a picklist for the order.
- If `reserve_stock` is true, reserve the stock for the products in the order.
- If `reduce_stock` is true, reduce the stock for the products in the order.
- If `concepted` is true, mark the order as concepted.
- If `completed` is true, mark the order as completed.
- If `on_hold` is true, mark the order as on hold.
- If `delivered` is true, mark the order as delivered.
- If `cancelled` is true, mark the order as cancelled and release any reserved stock for the order. 

Events should be triggered in the order of the boolean flags as listed above. For example, if an order status has `generate_picklist` and `reserve_stock` set to true, the picklist should be generated before reserving the stock. This ensures that the workflow is consistent and predictable based on the defined statuses. When implementing the status update logic, always refer to the `order_statuses` table to determine which events to trigger based on the new status. 

Use Laravel events for every order status change to trigger the corresponding business logic for each boolean flag. This allows for a clean separation of concerns and makes it easier to maintain and extend the order workflow in the future.