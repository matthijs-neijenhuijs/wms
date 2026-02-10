Lets create an instruction file for describing the warehouse. The instruction will specify the layout and organization of this warehouse application. The instruction will be applied to all files in the project.

# Warehouse Description Instructions

This warehouse application is written in Laravel with Filament. The application is organized into several key components:

1. **Models**: The models represent the core entities of the warehouse, such as `Product`, `Category`, `Supplier`, and `Inventory`. Each model is responsible for defining the attributes and relationships of the entity it represents.

2. **Controllers**: The controllers handle the business logic and interactions between the models and the views. They are responsible for processing user input, performing operations on the models, and returning appropriate responses.

3. **Views**: The views are responsible for presenting the data to the user. They are built using Blade templates and are organized into different sections based on the functionality they provide, such as product management, inventory tracking, and supplier management.

4. **Routes**: The routes define the endpoints for the application and map them to the corresponding controllers and actions. They are organized in a way that reflects the structure of the application, making it easy to navigate and understand.

5. Restfull API: The application also includes a RESTful API that allows external systems to interact with the warehouse data. The API endpoints are designed to follow best practices for RESTful design, making it easy for developers to integrate with the warehouse application.

6. **Database Migrations**: The database migrations are used to define the structure of the database tables and their relationships. They are organized in a way that reflects the entities they represent, making it easy to understand the database schema.

7. **Seeders**: The seeders are used to populate the database with initial data for testing and development purposes. They are organized based on the entities they represent, making it easy to manage and maintain the seed data.

8. **Tests**: The tests are organized into different categories based on the functionality they cover, such as unit tests for models and controllers, and feature tests for the overall application. They are designed to ensure the reliability and stability of the warehouse application.

9. **Configuration**: The configuration files are organized in a way that allows for easy management of application settings, such as database connections, API keys, and other environment-specific configurations.

10. **Documentation**: The documentation is organized to provide clear and concise information about the application, including how to set up the development environment, how to use the application, and how to contribute to the project.

11. **Redis queue**: The application uses a Redis queue to handle background tasks and improve performance. The queue is organized to manage different types of tasks, such as processing orders, updating inventory, and sending notifications. For example the product stock update is handled by a job that is dispatched to the Redis queue, allowing for efficient processing of stock updates without blocking the main application.

Overall, the warehouse application is designed to be modular and maintainable, with a clear separation of concerns between the different components. This organization allows for easy development and maintenance of the application, while also providing a solid foundation for future growth and expansion.

If a queue fails, the application will automatically retry the task a certain number of times before marking it as failed. This ensures that transient issues do not cause permanent failures in the application and allows for better handling of background tasks.

If a job is not completed within a certain time frame, it will be marked as failed and can be retried or investigated further. This helps to ensure that the application remains responsive and that background tasks are completed in a timely manner. No jobs can be lost, because in a warehouse application, it is crucial to ensure that all tasks are completed successfully to maintain the integrity of the inventory and order management processes. The use of a Redis queue allows for efficient handling of background tasks while also providing mechanisms for error handling and retries to ensure that no jobs are lost.

12. **History**: The application also includes a history feature that allows users to track changes and updates to the warehouse data. This feature is organized to provide a clear and concise view of the history of changes, including who made the change, when it was made, and what was changed. This allows for better accountability and transparency in the management of the warehouse data.




# Warehouse subdomains explanation
The warehouse application has a model called 'subdomain' which represents different warehouse names. A subdomain is a unique identifier for a specific warehouse, allowing the application to manage multiple warehouses within the same system. Users are part of a subdomain, and they can only access the data and functionality associated with their assigned subdomain. A subdomain have multiple warehouses to manage inventory, products, and suppliers. This structure allows for efficient organization and management of multiple warehouses within the application, while also ensuring that users have access to the relevant data and functionality based on their assigned subdomain.



