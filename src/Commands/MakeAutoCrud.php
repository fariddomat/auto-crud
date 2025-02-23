<?php

namespace Fariddomat\AutoCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Fariddomat\AutoCrud\Services\CrudGenerator;
use Fariddomat\AutoCrud\Services\ControllerGenerator;
use Fariddomat\AutoCrud\Services\ViewGenerator;
use Fariddomat\AutoCrud\Services\MigrationGenerator;

class MakeAutoCrud extends Command
{
    protected $signature = 'make:auto-crud {name} {fields?*} {--api} {--dashboard}';
    protected $description = 'Generate a complete CRUD module with optional API support.';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $isApi = $this->option('api');
        $isDashboard = $this->option('dashboard');
        $fields = $this->argument('fields') ?: [];

        $crudGenerator = new CrudGenerator($name, $fields);
        $parsedFields = $crudGenerator->parseFields();

        $this->info("\033[34m Generating Auto CRUD for $name... \033[0m");

        // إنشاء الملفات المطلوبة
        $this->call('make:model', ['name' => $name]);
        ControllerGenerator::generate($name, $isApi, $isDashboard);
        MigrationGenerator::generate($name, $crudGenerator->getTableName(), $parsedFields);

        if (!$isApi) {
            ViewGenerator::generateBladeViews($name, $isDashboard, $parsedFields);
        }

        $this->info("\033[34m CRUD for $name has been created successfully! \033[0m");
    }
}
