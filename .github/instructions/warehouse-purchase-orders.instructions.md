# Purchase orders

This instruction file provides guidance on the purchase order domain in the warehouse management system. It covers the schema, relationships, and implementation notes related to purchase orders.

## description
Purchase orders are orders placed to suppliers for restocking products. A purchase order has a delivery date when the ordered products are expected to arrive. Purchase orders has a relation to `orders`. When the delivery date of an order is after the date of the purchase order, it indicates that the order is dependent on the arrival of the purchase order. So the stock of a product can be free for other client orders until the delivery date of the purchase order is reached. Once the purchase order is delivered, the stock becomes available for all orders that are dependent on it.


so when a client order for example 100 of a product is placed and the delivery date is after the date of the purchase order the stock dont needs to be reserved for the client order until the delivery date of the purchase order is reached. This allows for more efficient stock management and reduces the chances of over-reserving stock for client orders that are dependent on incoming purchase orders.

A purchase order can be scanned like a piclklist and can be adjust to processed. When a purchase order is processed, the stock of the products in the purchase order becomes available for client orders that are dependent on it. When a purchase order is processed, the stock of the products are increased with the quantity of the products in the purchase order. When a purchase order is completed, it means that the products in the purchase order have been received and the stock has been updated accordingly.