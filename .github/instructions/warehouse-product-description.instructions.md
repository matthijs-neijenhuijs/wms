Products are part of the warehouse model and represent the items that are stored and managed within the warehouse. In a warehouse application, the quantity of products is a crucial aspect to track and manage effectively. The quantity of products in the warehouse can be influenced by various factors, including incoming shipments, outgoing orders, and inventory adjustments.

To manage the quantity of products in the warehouse, we use the model StockProducts. This model is responsibe for the quantity on stock, reserved quantity, reserved on picklists, and free on stock quantity. The following fields are used to track the quantity of products in the warehouse:

- **quantity_on_stock**: This field represents the total quantity of a product that is currently available in the warehouse. It includes all the products that are physically present in the warehouse and ready for use.

- **reserved_quantity**: This field represents the quantity of a product that has been reserved for specific orders or purposes. It indicates the amount of stock that is set aside and cannot be used for other orders until the reservation is released.

- **reserved_on_picklists**: This field represents the quantity of a product that has been reserved on picklists. Picklists are used to manage the picking process for orders, and this field indicates the amount of stock that is reserved for picking.

- **free_on_stock_quantity**: This field represents the quantity of a product that is free and available for use in the warehouse. It is calculated by subtracting the reserved quantity and reserved on picklists from the total quantity on stock.

By tracking these fields, the warehouse application can effectively manage the inventory and ensure that there is sufficient stock available to fulfill orders while also accounting for reserved quantities. This allows for efficient inventory management and helps prevent stockouts or overstocking situations in the warehouse.

