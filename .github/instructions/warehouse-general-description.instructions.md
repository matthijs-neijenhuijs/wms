# Warehouse Description Instructions

This Laravel + Filament application is a multi-tenant warehouse management system with tenant isolation via `subdomains`.

## Core Tenancy Model
- `subdomains` is the tenant root.
- `warehouses` belongs to `subdomains`.
- `users` belongs to `subdomains`.
- User access to specific warehouses is controlled through `user_warehouses`.

## Operational Domain (Migration-Backed)
- Product catalog: `products`, `brands`, `vat_rates`, `attributes`, `attribute_groups`, `product_attributes`.
- Customer domain: `clients`, `client_addresses`.
- Order domain: `order_statuses`, `orders`, `order_products`.
- Fulfillment domain: `picklists`, `picklists_products`, `picklist_failed_products`.
- Inventory quantities: `stock_products`.

## AI/Data Features Present in Schema
- `agent_conversations`, `agent_conversation_messages` (AI conversation persistence).
- `dynamic_ai_charts` (warehouse-scoped persisted AI chart definitions and SQL metadata).

## Working Rules
- Treat migration field names as source of truth.
- Scope all business queries and writes by `warehouse_id` and tenant context.
- Do not assume missing entities exist (for example `purchase_orders`) unless corresponding migrations are added.

## Warehouse Subdomains Explanation
Each subdomain represents a tenant boundary. A subdomain can own multiple warehouses, and users are tied to a subdomain. Access and business operations must remain inside the active tenant and warehouse scope.



