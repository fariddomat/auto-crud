# Auto-Crud

Auto-Crud is a Laravel package that automatically generates a complete CRUD (Create, Read, Update, Delete) functionality for your models. It simplifies and accelerates the process of creating CRUD modules, including controllers, views, migrations, and routes with optional API support. It also supports customizable fields, including various data types like strings, decimals, text, and selects.

## Installation

You can install Auto-Crud via Composer by running the following command:

```bash
composer require fariddomat/auto-crud:dev-main
```
Once installed, you need to publish the necessary views by running:
```bash
php artisan vendor:publish --provider="Fariddomat\AutoCrud\AutoCrudServiceProvider" --tag="autocrud-views"
```
This will publish the Blade views for CRUD operations (create, edit, index) into your resources/views directory.
Usage
Create a CRUD Module

You can generate a complete CRUD module using the make:auto-crud Artisan command. This will create a model, controller, migration, and views, all in one command.
Example 1: With Dashboard Prefix

If you want to generate a CRUD module with the dashboard prefix, you can run:
```bash
php artisan make:auto-crud Farid customer_name:string total_price:decimal status:string --dashboard
```
This command will generate:

    Model: Farid
    Controller: FaridController
    Migration: For fields customer_name, total_price, and status
    Views: Create, Edit, and Index views
    Routes: Automatically registered routes with the dashboard prefix

Example 2: Without Dashboard Prefix

To create a module without the dashboard prefix:

php artisan make:auto-crud Item --dashboard name:string price:decimal description:text purchase_category_id:select

This will generate:

    Model: Item
    Controller: ItemController
    Migration: For fields name, price, description, and purchase_category_id
    Views: Create, Edit, and Index views with proper field types
    Routes: Automatically registered routes with the dashboard prefix.

Fields

You can define various field types in the make:auto-crud command:

    string: For simple text inputs.
    decimal: For decimal numbers (e.g., prices).
    text: For longer text fields.
    select: For dropdown fields with options. You can use foreign key relations here.

For example:

php artisan make:auto-crud Product name:string price:decimal description:text category_id:select

This command will generate a Product model with a name field (string), a price field (decimal), a description field (text), and a category_id field (select).
Features

    Automatic CRUD Generation: Create models, controllers, migrations, views, and routes with a single command.
    Customizable Fields: Specify fields with various data types, including string, decimal, text, and select.
    Dashboard Support: Automatically generate routes prefixed with dashboard for admin panels.
    API Support: Optionally generate routes for API endpoints.
    Blade Views: Generate create, edit, and index Blade views for displaying and interacting with records.

Configuration

You can further customize the generated code by modifying the generated controller, views, or migration files according to your project's needs.
Contribution

Feel free to submit issues, bug fixes, or pull requests. Contributions are always welcome!
License

This package is licensed under the MIT License. See the LICENSE file for more information.