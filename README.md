# Auto-Crud

Auto-Crud is a Laravel package that automatically generates a complete CRUD (Create, Read, Update, Delete) functionality for your models. It simplifies and accelerates the process of creating CRUD modules, including controllers, views, migrations, and routes with optional API support. It also supports customizable fields, including various data types like strings, decimals, text, selects, booleans, and file uploads.
 
# Installation

You can install Auto-Crud via Composer by running the following command:

    composer require fariddomat/auto-crud:dev-main

Once installed, you need to publish the necessary views by running:

    php artisan vendor:publish --provider="Fariddomat\AutoCrud\AutoCrudServiceProvider" --tag="autocrud-views"

This will publish the Blade views for CRUD operations (create, edit, index) into your resources/views directory.
Usage
Create a CRUD Module

You can generate a complete CRUD module using the make:auto-crud Artisan command. This will create a model, controller, migration, and views, all in one command.
Example 1: With Dashboard Prefix

If you want to generate a CRUD module with the dashboard prefix, you can run:

    php artisan make:auto-crud Farid customer_name:string total_price:decimal status:string is_active:boolean logo:image attachments:images --dashboard

This command will generate:

    Model: Farid
    Controller: FaridController
    Migration: For fields customer_name, total_price, status, is_active, logo, and attachments
    Views: Create, Edit, and Index views
    Routes: Automatically registered routes with the dashboard prefix
    Image Upload Handling: Uses ImageHelper to handle image uploads

Example 2: Without Dashboard Prefix

To create a module without the dashboard prefix:

    php artisan make:auto-crud Item name:string price:decimal description:text purchase_category_id:select is_available:boolean document:file 

This will generate:

    Model: Item
    Controller: ItemController
    Migration: For fields name, price, description, purchase_category_id, is_available, and document
    Views: Create, Edit, and Index views with proper field types
    Routes: Automatically registered routes 
    File Upload Handling: Uses ImageHelper for file storage

Fields

    You can define various field types in the make:auto-crud command:
    Field Type	Description
    string	Simple text inputs
    decimal	Decimal numbers (e.g., prices)
    text	Longer text fields
    select	Dropdown fields with options (supports relations)
    boolean	Checkbox (true/false values)
    file	For single file uploads (PDF, docs, etc.)
    image	For single image uploads, processed using ImageHelper
    images	For multiple image uploads, processed using ImageHelper
    Example Usage


    php artisan make:auto-crud Product name:string price:decimal description:text category_id:select is_featured:boolean thumbnail:image gallery:images manual:file

This command will generate a Product model with:

    name (string)
    price (decimal)
    description (text)
    category_id (select dropdown)
    is_featured (boolean checkbox)
    thumbnail (image upload)
    gallery (multiple images upload)
    manual (file upload)

# Features

✔ Automatic CRUD Generation: Create models, controllers, migrations, views, and routes with a single command.<br>
✔ Customizable Fields: Supports multiple data types, including boolean and file uploads.<br>
✔ Dashboard Support: Generates routes prefixed with dashboard for admin panels.<br>
✔ API Support: Optionally generate routes for API endpoints.<br>
✔ Blade Views: Generate create, edit, and index views with automatic form fields.<br>
✔ Image & File Uploads: Supports single and multiple file/image uploads using ImageHelper.<br>
Image & File Handling<br>
Image Upload (Single Image)<br>
<br>

For image fields, the generated controller will automatically use the ImageHelper to store and process images.

    use App\Helpers\ImageHelper;

    $imagePath = ImageHelper::storeImageInPublicDirectory($request->file('thumbnail'), 'uploads/products');
    $product->thumbnail = $imagePath;
    $product->save();

Multiple Image Uploads

For images fields, the controller will process multiple files.

    $galleryPaths = [];
    foreach ($request->file('gallery') as $image) {
        $galleryPaths[] = ImageHelper::storeImageInPublicDirectory($image, 'uploads/products/gallery');
    }
    $product->gallery = json_encode($galleryPaths);
    $product->save();

File Upload (Documents, PDFs, etc.)

For file fields, files will be stored using Laravel's storage system.

    $filePath = $request->file('manual')->store('uploads/manuals', 'public');
    $product->manual = $filePath;
    $product->save();

# Configuration

You can further customize the generated code by modifying the controller, views, or migration files according to your project's needs.
Contribution

Feel free to submit issues, bug fixes, or pull requests. Contributions are always welcome!
License

This package is licensed under the MIT License. See the LICENSE file for more information.