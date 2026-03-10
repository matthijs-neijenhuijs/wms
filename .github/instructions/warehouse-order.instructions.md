# Orders  
This section provides instructions related to the management of orders within the warehouse management system. It covers various aspects of order processing, including purchase orders, sales orders, and the handling of packages associated with these orders. The instructions are designed to ensure efficient and accurate order fulfillment while maintaining clear communication between different stakeholders involved in the order management process.

# Order statuses
Orders in the warehouse management system can have various statuses that indicate their current state in the order processing workflow. These statuses help to track the progress of orders and ensure that they are handled appropriately at each stage. Common order statuses include:  

- **New** — Concept order; no stock reservation and no picklist generation yet.
- **Confirmed** — Order is approved; stock is reserved and a picklist can be generated.
- **Processing** — Order is being handled internally; stock remains reserved.
- **Shipped** — Order has left the warehouse.
- **Delivered** — Order is completed and marked as delivered.
- **On Hold** — Order is paused temporarily; stock can remain reserved.
- **Cancelled** — Order is cancelled and no further fulfillment actions are taken.

In order model there are two boolean fields that trigger some jobs/events when they are set to true:

- generate_picklist: when set to true, a picklist will be generated for the order. This typically happens when the order is confirmed and ready for fulfillment.
- reserve_stock: when set to true, the system will attempt to reserve the necessary stock for
- concepted: when set to true, the order is in a concept state, meaning it is not yet confirmed and no stock reservation or picklist generation has occurred.
- completed: when set to true, the order is completed and all necessary fulfillment actions have been taken. Product amounts are reduced from inventory and it will not be reserved.
- reduce_stock: when set to true, the system will reduce the stock levels for the products associated with the order. This typically happens when the order is completed and the products have been shipped or delivered.