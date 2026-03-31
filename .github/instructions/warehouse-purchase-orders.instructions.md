# Purchase orders

Purchase orders are conceptually important for replenishment, but there is currently no `purchase_orders` migration/table in this codebase.

Before implementing purchase-order features, first add explicit schema support (for example: `purchase_orders`, `purchase_order_items`, status/history tables) via migrations.

Until that exists, inventory inflow should be treated as custom domain logic around existing product and stock tables, not as persisted purchase-order records.
