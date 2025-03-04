<?php

namespace Fariddomat\AutoCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Fariddomat\AutoCrud\Services\CrudGenerator;
use Fariddomat\AutoCrud\Services\ControllerGenerator;
use Fariddomat\AutoCrud\Services\ViewGenerator;
use Fariddomat\AutoCrud\Services\MigrationGenerator;
use Fariddomat\AutoCrud\Services\RouteGenerator;

class MakeAutoCrud extends Command
{
    protected $signature = 'make:auto-crud';
    protected $description = 'Generate a complete CRUD module interactively with optional API and dashboard support.';

    public function handle()
    {
        $this->info("\033[34m Welcome to AutoCRUD Generator! Let's create your CRUD module step-by-step. \033[0m");

        // Step 1: Ask for the model name with validation
        $name = $this->ask("\033[33m What is the name of your model? (e.g., Post, User; must start with a capital letter) \033[0m");
        if (empty($name)) {
            $this->error("\033[31m Model name is required. Aborting. \033[0m");
            return 1; // Exit with error code
        }

        // Validate: Must start with a capital letter and be a valid PHP class name
        if (!preg_match('/^[A-Z][a-zA-Z0-9_]*$/', $name)) {
            $this->error("\033[31m Invalid model name '$name'. It must start with a capital letter and contain only letters, numbers, or underscores. Aborting. \033[0m");
            return 1; // Exit with error code
        }
        $name = Str::studly($name); // Ensure proper casing (e.g., "post" -> "Post")

        // Step 2: Ask for fields
        $fields = [];
        $this->info("\033[36m Let's define the fields for $name. Format: name:type:modifiers (e.g., title:string:nullable, user_id:select). Leave blank to finish. \033[0m");
        while (true) {
            $field = $this->ask("\033[33m Enter a field (or press Enter to skip): \033[0m");
            if (empty($field)) {
                break;
            }
            $fields[] = $field;
        }
        if (empty($fields)) {
            $this->warn("\033[33m No fields provided. Proceeding with no additional fields. \033[0m");
        }

        // Step 3: Ask if API or Web
        $isApi = $this->confirm("\033[33m Generate an API controller instead of a web controller? (Default: No) \033[0m", false);

        // Step 4: Ask if dashboard
        $isDashboard = !$isApi && $this->confirm("\033[33m Place the CRUD under a dashboard prefix? (Default: No) \033[0m", false);

        // Step 5: Ask about soft deletes
        $softDeletes = $this->confirm("\033[33m Enable soft deletes for $name? (Default: No) \033[0m", false);

        // Step 6: Ask about overwriting
        $force = $this->confirm("\033[33m Force overwrite existing files if they exist? (Default: No) \033[0m", false);

        // Step 7: Ask about middleware
        $middlewareInput = $this->ask("\033[33m Enter middleware (comma-separated, e.g., auth,admin) or leave blank for none: \033[0m");
        $middleware = !empty($middlewareInput) ? array_filter(array_map('trim', explode(',', $middlewareInput))) : [];

        // Display summary
        $this->info("\033[36m Generating CRUD with the following settings: \033[0m");
        $this->line("  \033[32m Model: \033[0m $name");
        $this->line("  \033[32m Fields: \033[0m " . (empty($fields) ? 'None' : implode(', ', $fields)));
        $this->line("  \033[32m Type: \033[0m " . ($isApi ? 'API' : 'Web'));
        $this->line("  \033[32m Dashboard: \033[0m " . ($isDashboard ? 'Yes' : 'No'));
        $this->line("  \033[32m Soft Deletes: \033[0m " . ($softDeletes ? 'Yes' : 'No'));
        $this->line("  \033[32m Force Overwrite: \033[0m " . ($force ? 'Yes' : 'No'));
        $this->line("  \033[32m Middleware: \033[0m " . (empty($middleware) ? 'None' : implode(', ', $middleware)));

        // Confirm to proceed
        if (!$this->confirm("\033[33m Proceed with these settings? \033[0m", true)) {
            $this->info("\033[31m Generation cancelled. \033[0m");
            return 0; // Exit gracefully
        }

        $this->info("\033[34m Generating Auto CRUD for $name... \033[0m");

        // Generate CRUD components
        $crudGenerator = new CrudGenerator($name, $fields, $this);
        $parsedFields = $crudGenerator->parseFields();
        $viewParseFields = $crudGenerator->viewParseFields();

        $crudGenerator->generateModel($force, $softDeletes);
        MigrationGenerator::generate($name, $crudGenerator->getTableName(), $parsedFields, $this, $softDeletes);
        ControllerGenerator::generate($name, $isApi, $isDashboard, $parsedFields, $this, $softDeletes, $middleware);

        if (!$isApi) {
            ViewGenerator::generateBladeViews($name, $isDashboard, $viewParseFields);
        }

        $routeGenerator = new RouteGenerator();
        $routeGenerator->create(
            $name,
            $isApi ? $name . 'ApiController' : $name . 'Controller',
            $isApi ? 'api' : 'web',
            $isDashboard,
            $this,
            $softDeletes,
            $middleware
        );

        $this->info("\033[34m CRUD for $name has been created successfully! \033[0m");
    }
}
